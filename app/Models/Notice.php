<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'school_id',
        'title',
        'content',
        'image_url',
        'image_public_id',
        'target_levels',
        'target_roles',
        'is_published',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_levels' => 'array',
            'target_roles' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
