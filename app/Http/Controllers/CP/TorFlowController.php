<?php

namespace App\Http\Controllers\CP;

use App\Mail\TorInvitationMail;
use App\Mail\TorSignerRegisterMail;
use App\Jobs\Tor\ProcessTorSignerCsvImport;
use App\Models\TorFlow;
use App\Models\TorInvitation;
use App\Services\Platform\CpOperatorScope;
use App\Services\Tor\TorSignerRegisterService;
use App\Support\Tor\TorFlowBlueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Statamic\CP\PublishForm;

class TorFlowController
{
    public function index(Request $request): View
    {
        $ids = app(CpOperatorScope::class)->organisationIds($request->user());
        $flows = TorFlow::query()
            ->with('organisation')
            ->withCount([
                'invitations',
                'invitations as signed_invitations_count' => fn ($query) => $query->where('status', 'signed'),
            ])
            ->whereIn('organisation_id', $ids)
            ->latest()
            ->get();

        return view('tor.cp.index', [
            'flows' => $flows,
            'ongoingCount' => $flows->where('status', 'active')->count(),
            'completedCount' => $flows->filter(fn (TorFlow $flow) => $flow->invitations_count > 0 && $flow->invitations_count === $flow->signed_invitations_count)->count(),
        ]);
    }

    public function create(Request $request)
    {
        $organisations = app(CpOperatorScope::class)->availableOrganisations($request->user());

        return PublishForm::make(TorFlowBlueprint::make($organisations))
            ->title('Create TOR flow')
            ->icon('document-certificate')
            ->values(['organisation_id' => (string) $organisations->first()?->id])
            ->submittingTo(cp_route('tor-flows.store'), 'POST')
            ->toResponse($request);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $organisations = app(CpOperatorScope::class)->availableOrganisations($request->user());
        $data = PublishForm::make(TorFlowBlueprint::make($organisations))->submit($request->all());
        abort_unless(in_array((int) $data['organisation_id'], app(CpOperatorScope::class)->organisationIds($request->user()), true), 403);
        $recipients = collect(preg_split('/[\s,;]+/', $data['internal_recipients'] ?? ''))->filter()->map(fn ($email) => strtolower($email))->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))->values()->all();
        $sections = $this->normaliseSignerSections($data['signer_fields'] ?? []);
        $flow = TorFlow::create(['organisation_id' => $data['organisation_id'], 'title' => $data['title'], 'content_html' => $data['content_html'], 'content_bard' => null, 'internal_recipients' => $recipients, 'email_copy' => $this->emailCopy($data), 'signer_fields' => $sections, 'status' => ! empty($data['activate']) ? 'active' : 'draft', 'activated_at' => ! empty($data['activate']) ? now() : null, 'signing_opens_at' => $this->signingDate($data, 'signing_opens_at'), 'signing_deadline' => $this->signingDate($data, 'signing_deadline')]);

        if ($request->wantsJson()) {
            return response()->json(['redirect' => cp_route('tor-flows.show', $flow)]);
        }

        return redirect(cp_route('tor-flows.show', $flow))->with('success', 'TOR flow created.');
    }

    public function edit(Request $request, TorFlow $torFlow)
    {
        $this->allow($request, $torFlow);
        $organisations = app(CpOperatorScope::class)->availableOrganisations($request->user());

        return PublishForm::make(TorFlowBlueprint::make($organisations, editing: true, allowActivation: $torFlow->status === 'draft'))
            ->title('Edit TOR flow')
            ->icon('document-certificate')
            ->values([
                'organisation_id' => (string) $torFlow->organisation_id,
                'title' => $torFlow->title,
                'content_html' => $torFlow->content_html,
                'signer_fields' => $torFlow->signerSections(),
                'internal_recipients' => implode(', ', $torFlow->internal_recipients ?? []),
                'invitation_email_copy' => $torFlow->email_copy['invitation'] ?? '',
                'signer_completion_email_copy' => $torFlow->email_copy['signer_completion'] ?? '',
                'organisation_completion_email_copy' => $torFlow->email_copy['organisation_completion'] ?? '',
                'activate' => $torFlow->status === 'active',
                'signing_opens_at' => $torFlow->signing_opens_at?->toIso8601String(),
                'signing_deadline' => $torFlow->signing_deadline?->toIso8601String(),
            ])
            ->submittingTo(cp_route('tor-flows.update', $torFlow), 'PATCH')
            ->toResponse($request);
    }

    public function update(Request $request, TorFlow $torFlow): JsonResponse|RedirectResponse
    {
        $this->allow($request, $torFlow);
        $organisations = app(CpOperatorScope::class)->availableOrganisations($request->user());
        $data = PublishForm::make(TorFlowBlueprint::make($organisations, editing: true, allowActivation: $torFlow->status === 'draft'))->submit($request->all());
        abort_unless(in_array((int) $data['organisation_id'], app(CpOperatorScope::class)->organisationIds($request->user()), true), 403);
        abort_if($torFlow->invitations()->exists() && (int) $data['organisation_id'] !== (int) $torFlow->organisation_id, 422, 'The company cannot change after signers have been imported.');

        $recipients = collect(preg_split('/[\s,;]+/', $data['internal_recipients'] ?? ''))->filter()->map(fn ($email) => strtolower($email))->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))->values()->all();
        $activate = $torFlow->status === 'draft' && ! empty($data['activate']);
        $opensAt = $this->signingDate($data, 'signing_opens_at');
        $deadline = $this->signingDate($data, 'signing_deadline');
        $reopen = $torFlow->status === 'closed' && ($deadline?->isFuture() ?? false);
        $torFlow->update([
            'organisation_id' => $data['organisation_id'],
            'title' => $data['title'],
            'content_html' => $data['content_html'],
            'internal_recipients' => $recipients,
            'email_copy' => $this->emailCopy($data),
            'signer_fields' => $this->normaliseSignerSections($data['signer_fields'] ?? []),
            'status' => $activate || $reopen ? 'active' : $torFlow->status,
            'activated_at' => $activate || $reopen ? ($torFlow->activated_at ?? now()) : $torFlow->activated_at,
            'signing_opens_at' => $opensAt,
            'signing_deadline' => $deadline,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['redirect' => cp_route('tor-flows.show', $torFlow)]);
        }

        return redirect(cp_route('tor-flows.show', $torFlow))->with('success', 'TOR flow updated at '.now()->format('d M Y, H:i').'.');
    }

    public function show(Request $request, TorFlow $torFlow): View
    {
        $this->allow($request, $torFlow);
        $torFlow->load('organisation');
        $invitations = $torFlow->invitations()->latest()->get();

        return view('tor.cp.show', [
            'flow' => $torFlow,
            'invitations' => $invitations,
            'stats' => [
                'authorised' => $invitations->count(),
                'delivered' => $invitations->whereNotNull('delivered_at')->count(),
                'clicked' => $invitations->whereNotNull('clicked_at')->count(),
                'signed' => $invitations->where('status', 'signed')->count(),
            ],
            'pendingCount' => $invitations->where('status', 'pending')->count(),
            'importStatus' => $this->importStatus($torFlow),
        ]);
    }

    public function import(Request $request, TorFlow $torFlow): RedirectResponse
    {
        $this->allow($request, $torFlow);
        abort_unless($torFlow->canInviteSigners(), 422, 'Extend the signing close date before importing signers.');
        $data = $request->validate(['signers_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $headers = $this->signerCsvHeaders($data['signers_csv']->getRealPath());
        $importId = (string) Str::uuid();
        $path = $data['signers_csv']->storeAs('tor-signer-imports', $importId.'.csv', 'local');

        if (! $path) {
            throw ValidationException::withMessages(['signers_csv' => 'The CSV could not be stored for processing. Please try again.']);
        }

        Cache::put(ProcessTorSignerCsvImport::cacheKey($importId), [
            'status' => 'queued',
            'processed' => 0,
            'imported' => 0,
            'invitations_queued' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'file_name' => $data['signers_csv']->getClientOriginalName(),
        ], now()->addDays(7));
        Cache::put($this->lastImportCacheKey($torFlow), $importId, now()->addDays(7));

        ProcessTorSignerCsvImport::dispatch($torFlow->id, $importId, $path, $headers, $this->csvDataOffset($path));

        return back()->with('success', 'Signer CSV queued for background processing. Invitations will be queued automatically without holding this page open.');
    }

    public function addSigner(Request $request, TorFlow $torFlow): RedirectResponse
    {
        $this->allow($request, $torFlow);
        abort_unless($torFlow->canInviteSigners(), 422, 'Extend the signing close date before adding signers.');
        $data = $request->validate([
            'signer.full_name' => ['required', 'string', 'max:255'],
            'signer.email' => ['required', 'email', 'max:255'],
            'signer.state' => ['nullable', 'string', 'max:255'],
            'signer.position' => ['nullable', 'string', 'max:255'],
            'signer.phone_number' => ['nullable', 'string', 'max:50'],
        ]);
        $signer = $data['signer'];
        $email = strtolower(trim($signer['email']));
        $invitation = TorInvitation::query()->firstOrNew(['tor_flow_id' => $torFlow->id, 'email' => $email]);

        if (! $invitation->exists) {
            $invitation->token = Str::random(80);
        }

        if (! $invitation->isComplete()) {
            $invitation->fill([
                'expected_name' => trim($signer['full_name']),
                'state' => $this->nullableCsvValue($signer['state'] ?? null),
                'position' => $this->nullableCsvValue($signer['position'] ?? null),
                'phone_number' => $this->nullableCsvValue($signer['phone_number'] ?? null),
            ]);
        }
        $invitation->save();

        if ($invitation->isComplete() || $invitation->status === 'revoked') {
            return back()->with('success', 'The signer was saved, but their existing completed or revoked invitation was not re-sent.');
        }

        Mail::to($invitation->email)->queue((new TorInvitationMail($invitation->load('flow.organisation')))->onQueue('emails'));
        $invitation->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', 'Signer added. Their unique invitation email has been queued.');
    }

    public function send(Request $request, TorFlow $torFlow): RedirectResponse
    {
        $this->allow($request, $torFlow);
        abort_unless($torFlow->canInviteSigners(), 422, 'Extend the signing close date before sending invitations.');
        $query = $torFlow->invitations();
        if ($request->filled('invitation')) {
            $query->whereKey($request->integer('invitation'));
        } else {
            $query->where('status', 'pending');
        }
        $count = 0;
        $query->get()->each(function (TorInvitation $invitation) use (&$count): void {
            Mail::to($invitation->email)->send(new TorInvitationMail($invitation));
            $invitation->update(['status' => 'sent', 'sent_at' => now()]);
            $count++;
        });

        return back()->with('success', $count.' invitation(s) sent.');
    }

    public function sendRegister(Request $request, TorFlow $torFlow, TorSignerRegisterService $register): RedirectResponse
    {
        $this->allow($request, $torFlow);
        $final = $torFlow->deadlineHasPassed();
        abort_if(($torFlow->internal_recipients ?? []) === [], 422, 'Add at least one internal notification recipient to send the signer register.');

        $signerCount = $torFlow->invitations()->count();
        foreach ($torFlow->internal_recipients as $recipient) {
            Mail::to($recipient)->send(new TorSignerRegisterMail($torFlow, $final, $signerCount));
        }

        return back()->with('success', ($final ? 'Final updated' : 'Initial').' signer register sent to '.count($torFlow->internal_recipients).' company recipient(s).');
    }

    public function downloadRegister(Request $request, TorFlow $torFlow, TorSignerRegisterService $register)
    {
        $this->allow($request, $torFlow);
        $final = $torFlow->deadlineHasPassed();

        return response($register->csv($torFlow), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$register->filename($torFlow, $final).'"',
        ]);
    }

    private function allow(Request $request, TorFlow $flow): void
    {
        abort_unless(in_array((int) $flow->organisation_id, app(CpOperatorScope::class)->organisationIds($request->user()), true), 403);
    }

    private function emailCopy(array $data): array
    {
        return collect([
            'invitation' => $data['invitation_email_copy'] ?? null,
            'signer_completion' => $data['signer_completion_email_copy'] ?? null,
            'organisation_completion' => $data['organisation_completion_email_copy'] ?? null,
        ])->map(fn ($copy) => trim((string) $copy))->filter()->all();
    }

    private function signingDate(array $data, string $key): ?Carbon
    {
        return filled($data[$key] ?? null)
            ? Carbon::parse($data[$key])
            : null;
    }

    private function normaliseSignerSections(array $sections): array
    {
        return collect($sections)
            ->filter(fn (array $section) => ($section['type'] ?? null) === 'section' && filled($section['heading'] ?? null))
            ->map(function (array $section): array {
                $fields = collect($section['fields'] ?? [])
                    ->filter(fn (array $field) => in_array($field['type'] ?? null, ['text', 'textarea', 'date', 'checkbox', 'bank_account'], true) && filled($field['label'] ?? null))
                    ->map(fn (array $field): array => [
                        'handle' => filled($field['handle'] ?? null) && preg_match('/^[a-z0-9_]+$/', $field['handle'])
                            ? $field['handle']
                            : str($field['label'])->slug('_')->append('_'.Str::lower(Str::random(5)))->toString(),
                        'label' => trim($field['label']),
                        'help' => trim($field['help'] ?? ''),
                        'type' => $field['type'],
                        'required' => (bool) ($field['required'] ?? false),
                    ])->values()->all();

                return ['type' => 'section', 'heading' => trim($section['heading']), 'instructions' => trim($section['instructions'] ?? ''), 'fields' => $fields];
            })
            ->filter(fn (array $section) => $section['fields'] !== [])
            ->values()
            ->all();
    }

    /** @return array<int, array{full_name: string, state: ?string, position: ?string, phone_number: ?string, email: string}> */
    private function readSignerCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        $headers = array_map(fn ($header) => str($header)->replace("\xEF\xBB\xBF", '')->lower()->replace([' ', '-'], '_')->trim()->toString(), $headers ?: []);
        $requiredHeaders = ['full_name', 'email'];
        if (array_diff($requiredHeaders, $headers) !== []) {
            fclose($handle);
            throw ValidationException::withMessages(['signers_csv' => 'CSV headers must include full_name and email. Optional headers: state, position, phone_number.']);
        }

        $records = [];
        $rowNumber = 1;
        while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNumber++;
            if ($values === [null] || $values === []) {
                continue;
            }
            $row = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null));
            $name = trim((string) ($row['full_name'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fclose($handle);
                throw ValidationException::withMessages(['signers_csv' => "Row {$rowNumber} must include a valid full_name and email."]);
            }
            $records[$email] = [
                'full_name' => $name,
                'state' => $this->nullableCsvValue($row['state'] ?? null),
                'position' => $this->nullableCsvValue($row['position'] ?? null),
                'phone_number' => $this->nullableCsvValue($row['phone_number'] ?? null),
                'email' => $email,
            ];
        }
        fclose($handle);

        if ($records === []) {
            throw ValidationException::withMessages(['signers_csv' => 'The CSV does not contain any signer records.']);
        }

        return array_values($records);
    }

    /** @return array<int, string> */
    private function signerCsvHeaders(string $path): array
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        fclose($handle);
        $headers = array_map(fn ($header) => str($header)->replace("\xEF\xBB\xBF", '')->lower()->replace([' ', '-'], '_')->trim()->toString(), $headers ?: []);

        if (array_diff(['full_name', 'email'], $headers) !== []) {
            throw ValidationException::withMessages(['signers_csv' => 'CSV headers must include full_name and email. Optional headers: state, position, phone_number.']);
        }

        return $headers;
    }

    private function csvDataOffset(string $path): int
    {
        $handle = fopen(Storage::disk('local')->path($path), 'rb');
        fgetcsv($handle, 0, ',', '"', '\\');
        $offset = ftell($handle);
        fclose($handle);

        return $offset;
    }

    private function importStatus(TorFlow $flow): ?array
    {
        $importId = Cache::get($this->lastImportCacheKey($flow));

        return $importId ? Cache::get(ProcessTorSignerCsvImport::cacheKey($importId)) : null;
    }

    private function lastImportCacheKey(TorFlow $flow): string
    {
        return 'tor-last-signer-import:'.$flow->id;
    }

    private function nullableCsvValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
