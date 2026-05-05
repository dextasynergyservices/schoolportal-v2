<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'row_hash',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'user_id' => 'integer',
            'entity_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Compute the tamper-detection hash for this log row.
     * Uses only immutable fields (everything except row_hash itself).
     */
    public function computeHash(): string
    {
        return hash('sha256', implode('|', [
            (string) $this->school_id,
            (string) ($this->user_id ?? ''),
            (string) $this->action,
            (string) ($this->entity_type ?? ''),
            (string) ($this->entity_id ?? ''),
            json_encode($this->old_values, JSON_UNESCAPED_UNICODE),
            json_encode($this->new_values, JSON_UNESCAPED_UNICODE),
            (string) ($this->ip_address ?? ''),
            (string) ($this->user_agent ?? ''),
            (string) $this->created_at,
        ]));
    }

    /**
     * Return true if the stored row_hash matches the computed hash.
     */
    public function isIntact(): bool
    {
        return $this->row_hash !== null && hash_equals($this->row_hash, $this->computeHash());
    }

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            // Ensure created_at is set before hashing
            $log->created_at = $log->created_at ?? now();
            $log->row_hash = $log->computeHash();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
