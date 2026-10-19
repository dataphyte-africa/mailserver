<?php

namespace App\Services\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class NewsletterBanner
{
    public function defaultUrl(?Product $product, ?Organisation $organisation): ?string
    {
        $path = $product?->banner_path ?: $organisation?->banner_path;

        return $path ? url(Storage::disk('public')->url($path)) : null;
    }

    public function linkUrl(mixed $value): ?string
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https' ? $value : null;
    }

    public function imageUrl(mixed $value): ?string
    {
        return $this->linkUrl($value);
    }
}
