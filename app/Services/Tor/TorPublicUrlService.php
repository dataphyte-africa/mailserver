<?php

namespace App\Services\Tor;

use App\Models\Organisation;
use App\Models\TorInvitation;
use Illuminate\Support\Str;

class TorPublicUrlService
{
    public function signingUrl(TorInvitation $invitation): string
    {
        $invitation->loadMissing('flow.organisation');

        return $this->organisationUrl($invitation->flow->organisation, route('tor.public.show', $invitation->token, false));
    }

    public function signedCopyUrl(TorInvitation $invitation): string
    {
        $invitation->loadMissing('flow.organisation');

        return $this->organisationUrl($invitation->flow->organisation, route('tor.public.download', $invitation->token, false));
    }

    public function signedCopyFilename(TorInvitation $invitation, bool $organisationCopy = false): string
    {
        $invitation->loadMissing('flow.organisation');
        $signer = Str::slug($invitation->signer_name ?: $invitation->expected_name ?: 'signer') ?: 'signer';
        $organisation = Str::slug($invitation->flow->organisation->name) ?: 'organisation';
        $suffix = $organisationCopy ? '-organisation-copy' : '';

        return "{$signer}-signed-tor-{$organisation}{$suffix}.pdf";
    }

    private function organisationUrl(Organisation $organisation, string $path): string
    {
        if ($organisation->newsletter_domain_status === 'verified' && filled($organisation->newsletter_domain)) {
            return rtrim((string) config('platform.domain.platform_scheme', 'https'), ':/')
                .'://'.trim($organisation->newsletter_domain)
                .'/'.ltrim($path, '/');
        }

        return url($path);
    }
}
