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
        'proof_path',
        'transaction_reference',
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
     * Get secure temporary signed URL (or local URL) for payment proof.
     */
    public function getProofUrlAttribute(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        $disk = config('filesystems.default', 'local');

        if ($disk === 's3') {
            return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($this->proof_path, now()->addMinutes(15));
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->proof_path);
    }

    /**
     * Get the rental this payment belongs to.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
