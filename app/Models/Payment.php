<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rental_id',
        'amount_idr',
        'status',
        'payment_method',
        'paid_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'     => PaymentStatus::class,
            'paid_at'    => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the rental this payment belongs to.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
