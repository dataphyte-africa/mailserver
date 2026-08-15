<?php

namespace App\Contracts\Domain;

interface ProductUrlGeneratorInterface
{
    public function landingPage(string $productKey): string;

    public function formPage(string $productKey, string $formHandle): string;

    public function formSubmitEndpoint(string $productKey, string $formHandle): string;

    public function preferencesPage(string $productKey, string $subscriberToken): string;

    public function unsubscribePage(string $productKey, string $subscriberToken): string;

    public function browserViewPage(string $productKey, string $campaignKey): string;

    public function campaignLink(string $productKey, string $pathOrUrl): string;
}
