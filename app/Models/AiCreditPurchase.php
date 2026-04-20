<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCreditPurchase extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'purchased_by',
        'credits',
        'amount_naira',
        'reference',
        'payment_method',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'purchased_by' => 'integer',
            'amount_naira' => 'decimal:2',
        ];
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }
}
