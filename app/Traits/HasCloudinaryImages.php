<?php

declare(strict_types=1);

namespace App\Traits;

trait HasCloudinaryImages
{
    /**
     * Inject Cloudinary transformation parameters into a Cloudinary upload URL.
     *
     * Only modifies URLs that contain the Cloudinary `/upload/` segment.
     * Non-Cloudinary URLs (null, local paths, etc.) are returned unchanged.
     *
     * Example: cloudinaryTransform($url, 'w_400,h_400,c_fill,f_auto,q_auto')
     */
    protected static function cloudinaryTransform(?string $url, string $transforms): string
    {
        if (! $url || ! str_contains($url, '/upload/')) {
            return $url ?? '';
        }

        return str_replace('/upload/', "/upload/{$transforms}/", $url);
    }
}
