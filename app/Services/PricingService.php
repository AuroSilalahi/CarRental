<?php

namespace App\Services;

use App\Models\Car;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    /**
     * Calculate the number of rental days between two dates.
     *
     * Partial days are rounded up to the next whole day (ceiling).
     * The minimum return value is 1.
     *
     * @throws InvalidArgumentException if $end is not strictly after $start
     */
    public function getRentalDays(Carbon $start, Carbon $end): int
    {
        if ($end->lte($start)) {
            throw new InvalidArgumentException(
                'The end date/time must be strictly after the start date/time.'
            );
        }

        // Use floatDiffInDays for a precise fractional difference, then ceiling.
        // $start->floatDiffInDays($end) returns a positive value when $end > $start.
        $fractionalDays = $start->floatDiffInDays($end);

        $days = (int) ceil($fractionalDays);

        return max(1, $days);
    }

    /**
     * Get the effective daily rate for a car in IDR.
     *
     * For luxury brand cars, the daily rate is multiplied by the luxury_multiplier.
     * For non-luxury cars, the base daily_rate_idr is returned as-is.
     */
    public function getDailyRate(Car $car): int
    {
        if ($car->is_luxury_brand) {
            return (int) round($car->daily_rate_idr * (float) $car->luxury_multiplier);
        }

        return (int) $car->daily_rate_idr;
    }

    /**
     * Calculate the total rental cost in IDR.
     *
     * Uses getRentalDays() and getDailyRate() internally.
     *
     * @throws InvalidArgumentException if $end is not strictly after $start
     */
    public function calculateTotalCost(Car $car, Carbon $start, Carbon $end): int
    {
        $days = $this->getRentalDays($start, $end);
        $dailyRate = $this->getDailyRate($car);

        return $dailyRate * $days;
    }
}
