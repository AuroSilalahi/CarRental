<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id'      => Rental::factory(),
            'amount_idr'     => fake()->numberBetween(500_000, 5_000_000),
            'status'         => PaymentStatus::Unpaid,
            'payment_method' => null,
            'paid_at'        => null,
            'expires_at'     => now()->addHours(24),
        ];
    }

    /** Set the payment status to unpaid. */
    public function unpaid(): static
    {
        return $this->state([
            'status'  => PaymentStatus::Unpaid,
            'paid_at' => null,
        ]);
    }

    /** Set the payment status to paid. */
    public function paid(): static
    {
        return $this->state([
            'status'  => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /** Set the payment status to expired. */
    public function expired(): static
    {
        return $this->state([
            'status'     => PaymentStatus::Expired,
            'paid_at'    => null,
            'expires_at' => now()->subHour(),
        ]);
    }
}
