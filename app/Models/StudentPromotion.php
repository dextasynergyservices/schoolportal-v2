<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPromotion extends Model
{
    use Auditable, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'student_id',
        'from_class_id',
        'to_class_id',
        'from_session_id',
        'to_session_id',
        'promoted_by',
    ];

    protected function casts(): array
    {
        return [
            'promoted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'to_class_id');
    }

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'from_session_id');
    }

    public function toSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'to_session_id');
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }
}
