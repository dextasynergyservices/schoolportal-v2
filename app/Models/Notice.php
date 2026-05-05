<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\FileUploadService;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use App\Traits\HasCloudinaryImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use Auditable, BelongsToTenant, HasCloudinaryImages, HasFactory;

    protected $fillable = [
        'school_id',
        'title',
        'content',
        'image_url',
        'image_public_id',
        'file_url',
        'file_public_id',
        'file_name',
        'target_levels',
        'target_roles',
        'target_classes',
        'is_published',
        'status',
        'published_at',
        'expires_at',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'created_by' => 'integer',
            'target_levels' => 'array',
            'target_roles' => 'array',
            'target_classes' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the attached file is an image based on its name/url.
     */
    public function fileIsImage(): bool
    {
        $name = $this->file_name ?? $this->attributes['file_url'] ?? '';

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $name);
    }

    /**
     * Get the file URL — auto-signs raw (non-image) Cloudinary files
     * so they don't return HTTP 401 on accounts with strict delivery.
     */
    public function getFileUrlAttribute(?string $value): ?string
    {
        if (! $value || ! $this->file_public_id || $this->fileIsImage()) {
            return $value;
        }

        try {
            return FileUploadService::signedRawUrl($this->file_public_id);
        } catch (\Throwable) {
            return $value;
        }
    }

    // ── Cloudinary Image Accessors ──

    /** For small inline thumbnails in dashboard notice cards (displayed ~48px). */
    public function imageThumbnailUrl(): string
    {
        return self::cloudinaryTransform($this->image_url, 'w_128,h_128,c_fill,f_auto,q_auto');
    }

    /** For notice index grid cards displayed in aspect-video containers (~400px wide). */
    public function imageCardUrl(): string
    {
        return self::cloudinaryTransform($this->image_url, 'w_800,h_450,c_fill,f_auto,q_auto');
    }
}
