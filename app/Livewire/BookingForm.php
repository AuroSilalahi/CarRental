<?php

namespace App\Livewire;

use App\Exceptions\CarNotAvailableException;
use App\Exceptions\EmailNotVerifiedException;
use App\Models\Car;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\RentalService;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BookingForm extends Component
{
    // Car passed as prop from parent view
    public Car $car;

    #[Validate('required|date|after_or_equal:tomorrow')]
    public string $startDate = '';

    #[Validate('required|date|after:startDate')]
    public string $endDate = '';

    public string $pickupLocation = 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)';

    public string $returnLocation = 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)';

    #[Validate('required|min:3')]
    public string $destination = '';

    public int $estimatedCost = 0;

    public string $availabilityError = '';

    public bool $isAvailable = true;

    public function mount(): void
    {
        $this->startDate = now()->addDay()->toDateString();
        $this->endDate = now()->addDays(2)->toDateString();
        $this->calculateEstimate();
        $this->checkAvailability();
    }

    /**
     * Called automatically by Livewire when $startDate changes.
     */
    public function updatedStartDate(): void
    {
        $this->calculateEstimate();
        $this->checkAvailability();
    }

    /**
     * Called automatically by Livewire when $endDate changes.
     */
    public function updatedEndDate(): void
    {
        $this->calculateEstimate();
        $this->checkAvailability();
    }

    /**
     * Calculate the estimated total cost using PricingService.
     * Requires both dates to be valid and endDate > startDate.
     */
    public function calculateEstimate(): void
    {
        $this->estimatedCost = 0;

        if (blank($this->startDate) || blank($this->endDate)) {
            return;
        }

        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end   = Carbon::parse($this->endDate)->startOfDay();
        } catch (\Exception) {
            return;
        }

        if ($end->lte($start)) {
            return;
        }

        try {
            /** @var PricingService $pricingService */
            $pricingService = app(PricingService::class);
            $this->estimatedCost = $pricingService->calculateTotalCost($this->car, $start, $end);
        } catch (\InvalidArgumentException) {
            $this->estimatedCost = 0;
        }
    }

    /**
     * Check car availability using AvailabilityService and update state.
     */
    public function checkAvailability(): void
    {
        $this->availabilityError = '';
        $this->isAvailable = true;

        if (! $this->car->is_available) {
            $this->isAvailable = false;
            $this->availabilityError = 'Kendaraan ini sedang disewa atau tidak tersedia saat ini. Silakan pilih unit lain.';
            return;
        }

        if (blank($this->startDate) || blank($this->endDate)) {
            return;
        }

        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end   = Carbon::parse($this->endDate)->startOfDay();
        } catch (\Exception) {
            return;
        }

        if ($end->lte($start)) {
            return;
        }

        /** @var AvailabilityService $availabilityService */
        $availabilityService = app(AvailabilityService::class);

        if (! $availabilityService->isAvailable($this->car->id, $start, $end)) {
            $this->isAvailable       = false;
            $this->availabilityError = 'Kendaraan ini tidak tersedia pada tanggal yang dipilih. Silakan pilih tanggal lain.';
        }
    }

    /**
     * Handle form submission: validate, create booking, redirect.
     */
    public function submit(): mixed
    {
        // Block if not authenticated
        if (! auth()->check()) {
            return $this->addError('general', 'Anda harus login untuk melakukan pemesanan.');
        }

        $tomorrow = now()->addDay()->toDateString();

        // Server-side validation
        $this->validate([
            'startDate'   => 'required|date|after_or_equal:' . $tomorrow,
            'endDate'     => 'required|date|after:startDate',
            'destination' => 'required|min:3',
        ], [
            'startDate.required'           => 'Tanggal mulai sewa wajib diisi.',
            'startDate.date'               => 'Format tanggal mulai tidak valid.',
            'startDate.after_or_equal'     => 'Pemesanan harus dilakukan minimal H+1 (mulai besok).',
            'endDate.required'             => 'Tanggal selesai sewa wajib diisi.',
            'endDate.date'                 => 'Format tanggal selesai tidak valid.',
            'endDate.after'                => 'Tanggal selesai harus setelah tanggal mulai.',
            'destination.required'         => 'Tujuan / Destinasi perjalanan wajib diisi.',
            'destination.min'              => 'Tujuan perjalanan minimal 3 karakter.',
        ]);

        // Re-check availability before submitting
        $this->checkAvailability();
        if (! $this->isAvailable) {
            return null;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var RentalService $rentalService */
        $rentalService = app(RentalService::class);

        try {
            $rental = $rentalService->createBooking($user, [
                'car_id'          => $this->car->id,
                'start_date'      => $this->startDate,
                'end_date'        => $this->endDate,
                'pickup_location' => 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)',
                'return_location' => 'Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)',
                'destination'     => $this->destination,
            ]);
        } catch (EmailNotVerifiedException $e) {
            $this->addError('general', 'Email Anda belum diverifikasi. Silakan verifikasi email terlebih dahulu.');
            return null;
        } catch (CarNotAvailableException $e) {
            $this->isAvailable       = false;
            $this->availabilityError = 'Kendaraan ini tidak tersedia pada tanggal yang dipilih. Silakan pilih tanggal lain.';
            return null;
        }

        // Redirect to booking confirmation page
        return $this->redirect('/bookings/' . $rental->id, navigate: true);
    }

    public function render()
    {
        return view('livewire.booking-form');
    }
}
