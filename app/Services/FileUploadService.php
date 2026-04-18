<?php

declare(strict_types=1);

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;

class FileUploadService
{
    private UploadApi $uploader;

    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);

        $this->uploader = new UploadApi;
    }

    /**
     * Upload a student/user avatar to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadAvatar(UploadedFile $file, int $schoolId): array
    {
        $result = $this->uploader->upload($file->getRealPath(), [
            'folder' => "schoolportal/{$schoolId}/avatars",
            'transformation' => [
                'width' => 400,
                'height' => 400,
                'crop' => 'fill',
                'gravity' => 'face',
                'quality' => 'auto',
                'format' => 'webp',
            ],
        ]);

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Delete a file from Cloudinary by its public ID.
     */
    public function delete(string $publicId): void
    {
        $this->uploader->destroy($publicId);
    }
}
