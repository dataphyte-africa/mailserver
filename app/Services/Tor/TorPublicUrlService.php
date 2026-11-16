<?php

namespace App\Services\Tor;

use App\Models\Organisation;
use App\Models\TorInvitation;

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
