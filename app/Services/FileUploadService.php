<?php

declare(strict_types=1);

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\File;
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
     * Upload a school logo to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadSchoolLogo(UploadedFile $file, int $schoolId): array
    {
        $result = $this->uploader->upload($file->getRealPath(), [
            'folder' => "schoolportal/{$schoolId}/branding",
            'resource_type' => 'image',
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

    /**
     * Upload a notice file (image or document) to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadNoticeFile(UploadedFile $file, int $schoolId): array
    {
        $isImage = str_starts_with($file->getMimeType() ?? '', 'image/');

        // Use original filename (without extension) as public_id so Cloudinary
        // preserves the file extension in the URL — this ensures downloads
        // have the correct file type instead of a generic "file".
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $uniqueName = $safeName.'_'.uniqid();

        $options = [
            'folder' => "schoolportal/{$schoolId}/notices",
            'public_id' => $uniqueName,
        ];

        $uploadPath = $file->getRealPath();
        $tempFile = null;

        if (! $isImage) {
            $options['resource_type'] = 'raw';
            // For raw files, append extension so Cloudinary URL includes it
            $options['public_id'] = $uniqueName.'.'.$extension;

            // Copy to a temp file with the correct extension so Cloudinary
            // doesn't append .tmp from PHP's temporary upload path.
            $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.$uniqueName.'.'.$extension;
            copy($file->getRealPath(), $tempFile);
            $uploadPath = $tempFile;
        }

        try {
            $result = $this->uploader->upload($uploadPath, $options);
        } finally {
            if ($tempFile) {
                @unlink($tempFile);
            }
        }

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Upload a result file (PDF) to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadResult(UploadedFile $file, int $schoolId): array
    {
        return $this->uploadRawFile($file, $schoolId, 'results');
    }

    /**
     * Upload a result file to Cloudinary from a local file path.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadResultFromPath(string $filePath, int $schoolId): array
    {
        $originalName = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'pdf';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $uniqueName = $safeName.'_'.uniqid();

        $result = $this->uploader->upload($filePath, [
            'folder' => "schoolportal/{$schoolId}/results",
            'resource_type' => 'raw',
            'public_id' => $uniqueName.'.'.$extension,
        ]);

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Upload an assignment file to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadAssignment(UploadedFile $file, int $schoolId): array
    {
        return $this->uploadRawFile($file, $schoolId, 'assignments');
    }

    /**
     * Upload a raw file (PDF, DOCX, etc.) to Cloudinary with a safe unique name.
     *
     * PHP stores uploaded files with a .tmp extension. Cloudinary's raw upload
     * appends the file path's extension to the public_id, so we must copy the
     * file to a temp path with the correct extension before uploading.
     *
     * @return array{url: string, public_id: string}
     */
    private function uploadRawFile(UploadedFile $file, int $schoolId, string $subfolder): array
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $uniqueName = $safeName.'_'.uniqid();

        // Copy to a temp file with the correct extension so Cloudinary
        // doesn't append .tmp from PHP's temporary upload path.
        $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.$uniqueName.'.'.$extension;
        copy($file->getRealPath(), $tempFile);

        try {
            $result = $this->uploader->upload($tempFile, [
                'folder' => "schoolportal/{$schoolId}/{$subfolder}",
                'resource_type' => 'raw',
                'public_id' => $uniqueName.'.'.$extension,
            ]);
        } finally {
            @unlink($tempFile);
        }

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Generate a signed Cloudinary URL for a raw file (PDF, DOCX, etc.).
     *
     * Cloudinary accounts with "Strict Transformations" enabled block
     * unsigned delivery of raw resources, returning HTTP 401.
     * A long signature (32-char SHA-256) is required for strict mode.
     */
    public static function signedRawUrl(string $publicId): string
    {
        self::ensureConfigured();

        return (string) (new File($publicId))
            ->signUrl()
            ->longUrlSignature()
            ->toUrl();
    }

    /**
     * Ensure the Cloudinary Configuration singleton is initialised.
     *
     * This is needed because signedRawUrl() is static and may be called
     * before any FileUploadService instance has been constructed.
     */
    private static function ensureConfigured(): void
    {
        static $configured = false;

        if ($configured) {
            return;
        }

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

        $configured = true;
    }
}
