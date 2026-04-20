<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlay extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'game_id',
        'student_id',
        'school_id',
        'score',
        'max_score',
        'percentage',
        'time_spent_seconds',
        'completed',
        'game_state',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'game_id' => 'integer',
            'student_id' => 'integer',
            'school_id' => 'integer',
            'percentage' => 'decimal:2',
            'completed' => 'boolean',
            'game_state' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
