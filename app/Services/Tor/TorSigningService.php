<?php

namespace App\Services\Tor;

use App\Mail\TorCompletedMail;
use App\Models\TorInvitation;
use App\Models\WebhookLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TorSigningService
{
    public function complete(TorInvitation $invitation, array $data, ?UploadedFile $signatureUpload, string $ip): TorInvitation
    {
        $completedInvitation = DB::transaction(function () use ($invitation, $data, $signatureUpload, $ip): TorInvitation {
            $invitation = TorInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            abort_if($invitation->status === 'signed', 422, 'This TOR has already been signed.');
            abort_if($invitation->status === 'revoked', 403, 'This signing link has been revoked.');
            abort_if($invitation->flow->status !== 'active', 403, 'This TOR is not currently available for signing.');

            $signatureData = $this->signatureData($data, $signatureUpload);
            $code = hash('sha256', Str::uuid()->toString().config('app.key'));
            $invitation->fill([
                'status' => 'signed', 'signer_name' => $data['full_name'], 'signature_method' => $data['signature_method'],
                'signature_data' => $signatureData, 'signer_field_values' => $data['signer_field_values'] ?? [],
                'verification_code' => $code, 'signed_at' => now(), 'ip_address' => $ip,
            ])->save();

            $path = app(TorPdfService::class)->generate($invitation->fresh(['flow.organisation']));
            $invitation->update(['signed_pdf_path' => $path, 'document_hash' => hash('sha256', Storage::disk('local')->get($path))]);
            $invitation->refresh();

            return $invitation;
        });

        $this->sendCompletedCopies($completedInvitation);

        return $completedInvitation;
    }

    public function applyWebhookEvent(WebhookLog $log, string $event): bool
    {
        if (! in_array($event, ['delivered', 'clicked', 'bounced', 'failed'], true)) {
            return false;
        }
        $invitation = $log->transaction_id
            ? TorInvitation::query()->where('elastic_email_transaction_id', $log->transaction_id)->first()
            : null;
        $invitation ??= TorInvitation::query()
            ->where('email', $log->to_email)
            ->whereIn('status', ['sent', 'delivered', 'clicked'])
            ->latest('sent_at')->first();
        if (! $invitation) {
            return false;
        }
        $at = now();
        if ($event === 'delivered' && $invitation->status === 'sent') {
            $invitation->update(['status' => 'delivered', 'delivered_at' => $at]);
        }
        if ($event === 'clicked' && $invitation->status !== 'signed') {
            $invitation->update(['status' => 'clicked', 'clicked_at' => $invitation->clicked_at ?? $at, 'delivered_at' => $invitation->delivered_at ?? $at]);
        }
        if (in_array($event, ['bounced', 'failed'], true) && $invitation->status !== 'signed') {
            $invitation->update(['status' => $event]);
        }

        return true;
    }

    private function signatureData(array $data, ?UploadedFile $upload): string
    {
        if ($data['signature_method'] === 'snap') {
            return $upload->store('tor-signatures', 'local');
        }

        return $data['signature_method'] === 'draw' ? $data['signature_drawn'] : $data['full_name'];
    }

    private function sendCompletedCopies(TorInvitation $invitation): void
    {
        $invitation->loadMissing('flow.organisation');
        $recipients = [[
            'email' => $invitation->email,
            'copy_type' => 'signer',
        ]];

        foreach ($invitation->flow->internal_recipients ?? [] as $recipient) {
            $recipients[] = ['email' => $recipient, 'copy_type' => 'organisation'];
        }

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient['email'])->send(new TorCompletedMail($invitation, $recipient['copy_type']));
            } catch (\Throwable $exception) {
                Log::warning('TOR completion email could not be sent after signing was recorded.', [
                    'invitation_id' => $invitation->id,
                    'recipient' => $recipient['email'],
                    'copy_type' => $recipient['copy_type'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}
