<?php

namespace App\Services\Tor;

use App\Models\TorFlow;
use App\Models\TorInvitation;

class TorSignerRegisterService
{
    public function csv(TorFlow $flow): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['full_name', 'state', 'position', 'phone_number', 'email', 'status', 'signed_at', 'unique_link', 'signer_copy_link', 'company_copy_link'], ',', '"', '\\');

        $flow->invitations()->orderBy('email')->get()->each(function (TorInvitation $invitation) use ($stream): void {
            $signedCopy = $invitation->isComplete() && $invitation->signed_pdf_path
                ? route('tor.public.download', $invitation->token)
                : '';

            fputcsv($stream, [
                $this->csvValue($invitation->signer_name ?: $invitation->expected_name),
                $this->csvValue($invitation->state),
                $this->csvValue($invitation->position),
                $this->csvValue($invitation->phone_number),
                $this->csvValue($invitation->email),
                $this->csvValue($invitation->status),
                $invitation->signed_at?->toIso8601String() ?? '',
                route('tor.public.show', $invitation->token),
                $signedCopy,
                $signedCopy,
            ], ',', '"', '\\');
        });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    public function filename(TorFlow $flow, bool $final): string
    {
        $suffix = $final ? 'final-signer-register' : 'signer-register';

        return str($flow->title)->slug()->append('-'.$suffix.'.csv')->toString();
    }

    private function csvValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
