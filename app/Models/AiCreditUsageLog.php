<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditUsageLog extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_credit_usage_log';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'school_id',
        'user_id',
        'level_id',
        'usage_type',
        'entity_id',
        'credits_used',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(SchoolLevel::class, 'level_id');
    }
}
