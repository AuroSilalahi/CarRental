<?php

namespace Tests\Unit\Services;

use App\Models\Car;
use App\Services\PricingService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Property-Based Tests for PricingService.
 *
 * Each test runs 100+ randomly generated inputs to verify
 * universal correctness properties of the pricing logic.
 *
 * Validates: Requirements 1.9, 4.4, 4.5, 7.3, 8.3
 */
class PricingServicePropertyTest extends TestCase
{
    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingService();
    }

    // -------------------------------------------------------------------------
    // Property 4: Luxury Brand Multiplier Is Applied Consistently
    //
    // For any Car flagged as is_luxury_brand = true with multiplier m (1.0–3.0),
    // getDailyRate() SHALL equal daily_rate_idr × m (rounded to integer).
    // For is_luxury_brand = false, getDailyRate() SHALL equal daily_rate_idr × 1.0.
    //
    // Validates: Requirements 1.9, 4.5
    // -------------------------------------------------------------------------

    /**
     * Property 4: Luxury Brand Multiplier Is Applied Consistently
     *
     * Validates: Requirements 1.9, 4.5
     */
    public function test_property_4_luxury_brand_multiplier_is_applied_consistently(): void
    {
        $iterations = 100;
        $failures   = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random inputs
            $dailyRate  = random_int(1, 1_000_000);
            // Random multiplier in [1.0, 3.0] with one decimal place
            $multiplier = round(1.0 + (mt_rand(0, 200) / 100), 1); // 1.0 – 3.0 step ~0.01
            $isLuxury   = (bool) random_int(0, 1);

            $car = $this->makeCar($dailyRate, $isLuxury, $multiplier);

            $result = $this->service->getDailyRate($car);

            if ($isLuxury) {
                // effective_rate = daily_rate × multiplier (rounded to integer)
                $expected = (int) round($dailyRate * $multiplier);
            } else {
                // non-luxury: effective_rate = daily_rate × 1.0
                $expected = $dailyRate;
            }

            if ($result !== $expected) {
                $failures[] = sprintf(
                    'Iteration %d: daily_rate=%d, multiplier=%.1f, is_luxury=%s → got %d, expected %d',
                    $i + 1,
                    $dailyRate,
                    $multiplier,
                    $isLuxury ? 'true' : 'false',
                    $result,
                    $expected
                );
            }
        }

        $this->assertEmpty(
            $failures,
            sprintf(
                "Property 4 failed on %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                implode("\n", array_slice($failures, 0, 5))
            )
        );
    }

    // -------------------------------------------------------------------------
    // Property 7: Rental Pricing Formula Is Correct
    //
    // For any Car with effective_rate r and rental [start, end] where end > start,
    // calculateTotalCost() SHALL equal r × max(1, ceil((end − start) in days)).
    //
    // Validates: Requirements 4.4, 7.3, 8.3
    // -------------------------------------------------------------------------

    /**
     * Property 7: Rental Pricing Formula Is Correct
     *
     * Validates: Requirements 4.4, 7.3, 8.3
     */
    public function test_property_7_rental_pricing_formula_is_correct(): void
    {
        $iterations = 100;
        $failures   = [];

        // Fixed epoch as base to avoid system-time drift between iterations
        $baseDate = Carbon::parse('2025-01-01 00:00:00');

        for ($i = 0; $i < $iterations; $i++) {
            // Random Car attributes
            $dailyRate  = random_int(1, 1_000_000);
            $isLuxury   = (bool) random_int(0, 1);
            $multiplier = round(1.0 + (mt_rand(0, 200) / 100), 1); // 1.0 – 3.0

            $car = $this->makeCar($dailyRate, $isLuxury, $multiplier);

            // Random start offset: 0 – 364 days from base
            $startOffsetDays  = random_int(0, 364);
            $startOffsetHours = random_int(0, 23);
            $start = $baseDate->copy()
                ->addDays($startOffsetDays)
                ->addHours($startOffsetHours);

            // Random rental length: 1 – 365 days (end strictly after start)
            $rentalDays  = random_int(1, 365);
            $rentalHours = random_int(0, 23); // extra partial-day hours
            $end = $start->copy()
                ->addDays($rentalDays)
                ->addHours($rentalHours);

            // Calculate expected values
            $effectiveRate = $isLuxury
                ? (int) round($dailyRate * $multiplier)
                : $dailyRate;

            $fractionalDays = $start->floatDiffInDays($end);
            $ceiledDays     = max(1, (int) ceil($fractionalDays));
            $expectedTotal  = $effectiveRate * $ceiledDays;

            $result = $this->service->calculateTotalCost($car, $start, $end);

            if ($result !== $expectedTotal) {
                $failures[] = sprintf(
                    'Iteration %d: daily_rate=%d, is_luxury=%s, multiplier=%.1f, start=%s, end=%s '
                    . '→ got %d, expected %d (effective_rate=%d × days=%d)',
                    $i + 1,
                    $dailyRate,
                    $isLuxury ? 'true' : 'false',
                    $multiplier,
                    $start->toDateTimeString(),
                    $end->toDateTimeString(),
                    $result,
                    $expectedTotal,
                    $effectiveRate,
                    $ceiledDays
                );
            }
        }

        $this->assertEmpty(
            $failures,
            sprintf(
                "Property 7 failed on %d/%d iterations:\n%s",
                count($failures),
                $iterations,
                implode("\n", array_slice($failures, 0, 5))
            )
        );
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build an unsaved Car model instance.
     *
     * luxury_multiplier is stored as a decimal string by Eloquent's decimal cast,
     * so we mirror that behaviour here to match production runtime.
     */
    private function makeCar(int $dailyRate, bool $isLuxury, float $multiplier): Car
    {
        $car = new Car();
        $car->daily_rate_idr    = $dailyRate;
        $car->is_luxury_brand   = $isLuxury;
        $car->luxury_multiplier = (string) number_format($multiplier, 1, '.', '');
        return $car;
    }
}
