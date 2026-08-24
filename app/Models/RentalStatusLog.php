<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalStatusLog extends Model
{
    use HasFactory;

    /**
     * Disable automatic timestamps since this table uses changed_at only.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rental_id',
        'status',
        'changed_at',
        'changed_by',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'     => RentalStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the rental this log entry belongs to.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
