<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'school_id',
        'type',
        'question_text',
        'question_image_url',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'school_id' => 'integer',
            'options' => 'array',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
