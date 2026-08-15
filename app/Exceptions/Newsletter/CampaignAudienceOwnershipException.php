<?php

namespace App\Exceptions\Newsletter;

use RuntimeException;

class CampaignAudienceOwnershipException extends RuntimeException
{
    public function __construct(
        private readonly string $input,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function input(): string
    {
        return $this->input;
    }
}
