<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'brand',
        'model',
        'type',
        'license_plate',
        'passenger_capacity',
        'colour',
        'year',
        'daily_rate_idr',
        'is_available',
        'is_luxury_brand',
        'luxury_multiplier',
        'image_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available'     => 'boolean',
            'is_luxury_brand'  => 'boolean',
            'luxury_multiplier' => 'decimal:1',
        ];
    }

    /**
     * Get the rentals for this car.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
