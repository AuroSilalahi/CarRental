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
     * Get public URL for car image (S3 public URL or local asset).
     */
    public function getImageUrlAttribute(): string
    {
        if (! $this->image_path) {
            return asset('images/default-car.png');
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        $disk = config('filesystems.default', 'local');

        if ($disk === 's3') {
            try {
                if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->image_path)) {
                    return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($this->image_path, now()->addHours(24));
                }
            } catch (\Throwable $e) {
                // Fallback to local URL if S3 is unreachable
            }
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * Scope query to only include cars that are marked as available
     * and currently have no active or confirmed rentals.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->whereDoesntHave('rentals', function ($q) {
                $q->whereIn('status', [
                    \App\Enums\RentalStatus::Confirmed,
                    \App\Enums\RentalStatus::Active,
                ]);
            });
    }

    /**
     * Check if car is currently available (not rented out).
     */
    public function getIsCurrentlyAvailableAttribute(): bool
    {
        if (! $this->is_available) {
            return false;
        }

        return ! $this->rentals()
            ->whereIn('status', [
                \App\Enums\RentalStatus::Confirmed,
                \App\Enums\RentalStatus::Active,
            ])
            ->exists();
    }

    /**
     * Get the rentals for this car.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
