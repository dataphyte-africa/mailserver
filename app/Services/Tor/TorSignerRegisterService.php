<?php

namespace App\Services\Tor;

use App\Models\TorFlow;
use App\Models\TorInvitation;

class TorSignerRegisterService
{
    public function __construct(private readonly TorPublicUrlService $urls) {}

    public function csv(TorFlow $flow): string
    {
        $stream = fopen('php://temp', 'r+');
        $fields = $flow->signerInputFields();
        $headers = [
            'full_name', 'state', 'position', 'phone_number', 'email',
            ...$this->fieldHeaders($fields),
            'status', 'signed_at', 'unique_link', 'signer_copy_link', 'company_copy_link',
        ];
        fputcsv($stream, $headers, ',', '"', '\\');

        $flow->invitations()->orderBy('email')->get()->each(function (TorInvitation $invitation) use ($stream, $fields): void {
            $signedCopy = $invitation->isComplete() && $invitation->signed_pdf_path
                ? $this->urls->signedCopyUrl($invitation)
                : '';

            fputcsv($stream, [
                $this->csvValue($invitation->signer_name ?: $invitation->expected_name),
                $this->csvValue($invitation->state),
                $this->csvValue($invitation->position),
                $this->csvValue($invitation->phone_number),
                $this->csvValue($invitation->email),
                ...collect($fields)->map(fn (array $field) => $this->csvValue($this->fieldValue($invitation->signer_field_values[$field['handle']] ?? null, $field['type'] ?? 'text')))->all(),
                $this->csvValue($invitation->status),
                $invitation->signed_at?->toIso8601String() ?? '',
                $this->urls->signingUrl($invitation),
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

    /** @param array<int, array<string, mixed>> $fields */
    private function fieldHeaders(array $fields): array
    {
        $used = [];

        return collect($fields)->map(function (array $field) use (&$used): string {
            $label = str($field['label'] ?? 'additional_information')
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
            $label = $label !== '' ? $label : 'additional_information';
            $used[$label] = ($used[$label] ?? 0) + 1;

            return $used[$label] === 1 ? $label : $label.'_'.$used[$label];
        })->all();
    }

    private function fieldValue(mixed $value, string $type): string
    {
        if ($type === 'checkbox') {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return collect([
                'Bank' => $value['bank_name'] ?? null,
                'Account number' => $value['account_number'] ?? null,
                'Account name' => $value['account_name'] ?? null,
            ])->filter(fn ($item) => filled($item))->map(fn ($item, $label) => "{$label}: {$item}")->implode(' | ');
        }

        return (string) $value;
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }
}
