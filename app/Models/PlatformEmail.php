<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformEmail extends Model
{
    protected $fillable = [
        'subject',
        'body',
        'recipient_school_ids',
        'total_recipients',
        'sent_count',
        'failed_count',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_school_ids' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
