<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_id',
        'class_id',
        'admission_number',
        'date_of_birth',
        'address',
        'blood_group',
        'medical_notes',
        'enrolled_session_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'school_id' => 'integer',
            'class_id' => 'integer',
            'enrolled_session_id' => 'integer',
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function enrolledSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'enrolled_session_id');
    }
}
