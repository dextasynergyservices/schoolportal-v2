<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'session_id',
        'term_id',
        'week_number',
        'title',
        'description',
        'file_url',
        'file_public_id',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'class_id' => 'integer',
            'session_id' => 'integer',
            'term_id' => 'integer',
            'uploaded_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
