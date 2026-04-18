<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditAllocation extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'level_id',
        'allocated_credits',
        'used_credits',
        'allocated_by',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(SchoolLevel::class, 'level_id');
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function remainingCredits(): int
    {
        return $this->allocated_credits - $this->used_credits;
    }
}
