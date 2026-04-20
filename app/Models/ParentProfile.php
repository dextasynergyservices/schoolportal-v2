<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentProfile extends Model
{
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'school_id',
        'occupation',
        'relationship',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'school_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
