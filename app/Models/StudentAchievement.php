<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAchievement extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'school_id',
        'achievement_key',
        'unlocked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'school_id' => 'integer',
            'unlocked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
