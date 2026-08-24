<?php

namespace Tests\Unit\Services;

use App\Models\Car;
use App\Services\PricingService;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for PricingService.
 *
 * Validates: Requirements 4.4, 4.5, 7.3, 8.3, 1.9
 */
class PricingServiceTest extends TestCase
{
    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingService();
    }

    // -------------------------------------------------------------------------
    // getRentalDays()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_rental_days_returns_one_for_exactly_one_day(): void
    {
        $start = Carbon::parse('2025-01-01 00:00:00');
        $end   = Carbon::parse('2025-01-02 00:00:00');

        $this->assertSame(1, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_returns_exact_days_for_whole_day_difference(): void
    {
        $start = Carbon::parse('2025-01-01 00:00:00');
        $end   = Carbon::parse('2025-01-05 00:00:00');

        $this->assertSame(4, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_applies_ceiling_for_partial_day(): void
    {
        // 1 day + 1 second → rounds up to 2
        $start = Carbon::parse('2025-01-01 00:00:00');
        $end   = Carbon::parse('2025-01-02 00:00:01');

        $this->assertSame(2, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_applies_ceiling_for_partial_day_hours(): void
    {
        // 1 day and 6 hours = 1.25 days → ceiling = 2
        $start = Carbon::parse('2025-06-01 08:00:00');
        $end   = Carbon::parse('2025-06-02 14:00:00');

        $this->assertSame(2, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_returns_minimum_one_for_sub_day_difference(): void
    {
        // 6 hours < 1 day → minimum 1
        $start = Carbon::parse('2025-01-01 08:00:00');
        $end   = Carbon::parse('2025-01-01 14:00:00');

        $this->assertSame(1, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_returns_minimum_one_for_one_second_difference(): void
    {
        $start = Carbon::parse('2025-01-01 00:00:00');
        $end   = Carbon::parse('2025-01-01 00:00:01');

        $this->assertSame(1, $this->service->getRentalDays($start, $end));
    }

    #[Test]
    public function get_rental_days_throws_exception_when_end_equals_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $date = Carbon::parse('2025-01-01 12:00:00');
        $this->service->getRentalDays($date, $date->copy());
    }

    #[Test]
    public function get_rental_days_throws_exception_when_end_is_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $start = Carbon::parse('2025-01-05 00:00:00');
        $end   = Carbon::parse('2025-01-01 00:00:00');
        $this->service->getRentalDays($start, $end);
    }

    // -------------------------------------------------------------------------
    // getDailyRate()
    // -------------------------------------------------------------------------

    #[Test]
    public function get_daily_rate_returns_base_rate_for_non_luxury_car(): void
    {
        $car = $this->makeCar(dailyRate: 500_000, isLuxury: false, multiplier: 1.0);

        $this->assertSame(500_000, $this->service->getDailyRate($car));
    }

    #[Test]
    public function get_daily_rate_applies_multiplier_for_luxury_car(): void
    {
        $car = $this->makeCar(dailyRate: 1_000_000, isLuxury: true, multiplier: 1.5);

        // 1,000,000 × 1.5 = 1,500,000
        $this->assertSame(1_500_000, $this->service->getDailyRate($car));
    }

    #[Test]
    public function get_daily_rate_applies_minimum_luxury_multiplier(): void
    {
        $car = $this->makeCar(dailyRate: 800_000, isLuxury: true, multiplier: 1.1);

        // 800,000 × 1.1 = 880,000
        $this->assertSame(880_000, $this->service->getDailyRate($car));
    }

    #[Test]
    public function get_daily_rate_applies_maximum_luxury_multiplier(): void
    {
        $car = $this->makeCar(dailyRate: 1_000_000, isLuxury: true, multiplier: 3.0);

        // 1,000,000 × 3.0 = 3,000,000
        $this->assertSame(3_000_000, $this->service->getDailyRate($car));
    }

    #[Test]
    public function get_daily_rate_returns_integer_for_luxury_car(): void
    {
        $car = $this->makeCar(dailyRate: 300_000, isLuxury: true, multiplier: 1.3);

        $rate = $this->service->getDailyRate($car);

        $this->assertIsInt($rate);
        // 300,000 × 1.3 = 390,000
        $this->assertSame(390_000, $rate);
    }

    #[Test]
    public function get_daily_rate_ignores_multiplier_when_luxury_flag_is_false(): void
    {
        // Multiplier value is irrelevant when is_luxury_brand is false
        $car = $this->makeCar(dailyRate: 600_000, isLuxury: false, multiplier: 2.0);

        $this->assertSame(600_000, $this->service->getDailyRate($car));
    }

    // -------------------------------------------------------------------------
    // calculateTotalCost()
    // -------------------------------------------------------------------------

    #[Test]
    public function calculate_total_cost_for_non_luxury_car_exact_days(): void
    {
        $car   = $this->makeCar(dailyRate: 500_000, isLuxury: false, multiplier: 1.0);
        $start = Carbon::parse('2025-03-01');
        $end   = Carbon::parse('2025-03-04'); // 3 days

        // 500,000 × 3 = 1,500,000
        $this->assertSame(1_500_000, $this->service->calculateTotalCost($car, $start, $end));
    }

    #[Test]
    public function calculate_total_cost_for_luxury_car(): void
    {
        $car   = $this->makeCar(dailyRate: 1_000_000, isLuxury: true, multiplier: 2.0);
        $start = Carbon::parse('2025-03-01');
        $end   = Carbon::parse('2025-03-03'); // 2 days

        // effective rate: 1,000,000 × 2.0 = 2,000,000 → total: 2,000,000 × 2 = 4,000,000
        $this->assertSame(4_000_000, $this->service->calculateTotalCost($car, $start, $end));
    }

    #[Test]
    public function calculate_total_cost_applies_ceiling_for_partial_day(): void
    {
        $car   = $this->makeCar(dailyRate: 500_000, isLuxury: false, multiplier: 1.0);
        $start = Carbon::parse('2025-03-01 08:00:00');
        $end   = Carbon::parse('2025-03-02 12:00:00'); // 1 day 4 hours → ceil = 2 days

        // 500,000 × 2 = 1,000,000
        $this->assertSame(1_000_000, $this->service->calculateTotalCost($car, $start, $end));
    }

    #[Test]
    public function calculate_total_cost_minimum_one_day_for_short_rental(): void
    {
        $car   = $this->makeCar(dailyRate: 400_000, isLuxury: false, multiplier: 1.0);
        $start = Carbon::parse('2025-03-01 10:00:00');
        $end   = Carbon::parse('2025-03-01 14:00:00'); // 4 hours → minimum 1 day

        // 400,000 × 1 = 400,000
        $this->assertSame(400_000, $this->service->calculateTotalCost($car, $start, $end));
    }

    #[Test]
    public function calculate_total_cost_throws_exception_when_end_equals_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $car  = $this->makeCar(dailyRate: 500_000, isLuxury: false, multiplier: 1.0);
        $date = Carbon::parse('2025-03-01');
        $this->service->calculateTotalCost($car, $date, $date->copy());
    }

    #[Test]
    public function calculate_total_cost_throws_exception_when_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $car   = $this->makeCar(dailyRate: 500_000, isLuxury: false, multiplier: 1.0);
        $start = Carbon::parse('2025-03-05');
        $end   = Carbon::parse('2025-03-01');
        $this->service->calculateTotalCost($car, $start, $end);
    }

    #[Test]
    public function calculate_total_cost_returns_integer(): void
    {
        $car   = $this->makeCar(dailyRate: 750_000, isLuxury: true, multiplier: 1.2);
        $start = Carbon::parse('2025-03-01');
        $end   = Carbon::parse('2025-03-03'); // 2 days

        $result = $this->service->calculateTotalCost($car, $start, $end);

        $this->assertIsInt($result);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build an unsaved Car model instance for testing.
     */
    private function makeCar(int $dailyRate, bool $isLuxury, float $multiplier): Car
    {
        $car = new Car();
        $car->daily_rate_idr    = $dailyRate;
        $car->is_luxury_brand   = $isLuxury;
        $car->luxury_multiplier = (string) $multiplier; // Eloquent stores decimal as string
        return $car;
    }
}
