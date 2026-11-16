<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\TorInvitationMail;
use App\Models\TorFlow;
use App\Models\TorInvitation;
use App\Services\Tor\TorSigningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TorSigningController extends Controller
{
    public function access(TorFlow $torFlow): View
    {
        abort_unless($torFlow->status === 'active', 404);

        return view('tor.public.access', compact('torFlow'));
    }

    public function requestAccess(TorFlow $torFlow, Request $request): RedirectResponse
    {
        abort_unless($torFlow->status === 'active', 404);
        $data = $request->validate(['email' => ['required', 'email']]);
        $invitation = $torFlow->invitations()->where('email', strtolower($data['email']))->first();

        if ($invitation && ! $invitation->isComplete() && $invitation->status !== 'revoked') {
            Mail::to($invitation->email)->send(new TorInvitationMail($invitation));
            $invitation->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return back()->with('success', 'If your email is authorised, a secure signing link has been sent to it.');
    }

    public function show(string $token, Request $request): View
    {
        $invitation = TorInvitation::query()->where('token', $token)->with('flow.organisation')->firstOrFail();
        abort_if($invitation->status === 'revoked', 403, 'This signing link has been revoked.');
        abort_if(! $invitation->isComplete() && $invitation->flow->status !== 'active', 403, 'This TOR is no longer accepting signatures.');
        if (! $invitation->isComplete() && $invitation->status !== 'clicked') {
            $invitation->update(['status' => 'clicked', 'clicked_at' => now(), 'delivered_at' => $invitation->delivered_at ?? now()]);
        }

        return view('tor.public.sign', compact('invitation'));
    }

    public function sign(string $token, Request $request, TorSigningService $signing): RedirectResponse
    {
        $invitation = TorInvitation::query()->where('token', $token)->with('flow.organisation')->firstOrFail();
        $data = $request->validate(['agree' => ['accepted'], 'full_name' => ['required', 'string', 'max:255'], 'signature_method' => ['required', 'in:snap,draw,type'], 'signature_drawn' => ['nullable', 'string', 'max:200000'], 'signature_upload' => ['nullable', 'image', 'max:5120']]);
        if ($data['signature_method'] === 'snap') {
            $request->validate(['signature_upload' => ['required', 'image', 'max:5120']]);
        }
        if ($data['signature_method'] === 'draw') {
            $request->validate(['signature_drawn' => ['required', 'regex:/^data:image\/png;base64,/']]);
        }
        $data['signer_field_values'] = $this->validateSignerFields($request, $invitation);
        $signing->complete($invitation, $data, $request->file('signature_upload'), (string) $request->ip());

        return redirect()->to(route('tor.public.show', $token, false))->with('success', 'Your TOR has been signed and emailed.');
    }

    public function download(string $token)
    {
        $invitation = TorInvitation::query()->where('token', $token)->firstOrFail();
        if (! $invitation->isComplete()) {
            return redirect()
                ->to(route('tor.public.show', $token, false))
                ->withErrors(['download' => 'This Terms of Reference has not been completed yet. Complete signing before downloading the record.']);
        }

        if (! $invitation->signed_pdf_path || ! Storage::disk('local')->exists($invitation->signed_pdf_path)) {
            return redirect()
                ->to(route('tor.public.show', $token, false))
                ->withErrors(['download' => 'Your signing record is complete, but its PDF is temporarily unavailable. Please contact the organisation.']);
        }

        return Storage::disk('local')->download($invitation->signed_pdf_path, 'signed-tor.pdf');
    }

    private function validateSignerFields(Request $request, TorInvitation $invitation): array
    {
        $rules = [];
        foreach ($invitation->flow->signerInputFields() as $field) {
            $key = 'signer_fields.'.$field['handle'];
            $rules[$key] = match ($field['type']) {
                'checkbox' => ! empty($field['required']) ? ['accepted'] : ['nullable', 'accepted'],
                'date' => ! empty($field['required']) ? ['required', 'date'] : ['nullable', 'date'],
                default => ! empty($field['required']) ? ['required', 'string', 'max:2000'] : ['nullable', 'string', 'max:2000'],
            };
        }

        return $rules === [] ? [] : ($request->validate($rules)['signer_fields'] ?? []);
    }
}
