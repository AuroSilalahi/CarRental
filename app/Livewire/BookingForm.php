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

    #[Validate('required|date|after_or_equal:today')]
    public string $startDate = '';

    #[Validate('required|date|after:startDate')]
    public string $endDate = '';

    #[Validate('required|min:5')]
    public string $pickupLocation = '';

    #[Validate('required|min:5')]
    public string $returnLocation = '';

    public int $estimatedCost = 0;

    public string $availabilityError = '';

    public bool $isAvailable = true;

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

        // Server-side validation
        $this->validate([
            'startDate'      => 'required|date|after_or_equal:today',
            'endDate'        => 'required|date|after:startDate',
            'pickupLocation' => 'required|min:5',
            'returnLocation' => 'required|min:5',
        ], [
            'startDate.required'           => 'Tanggal mulai wajib diisi.',
            'startDate.date'               => 'Format tanggal mulai tidak valid.',
            'startDate.after_or_equal'     => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'endDate.required'             => 'Tanggal selesai wajib diisi.',
            'endDate.date'                 => 'Format tanggal selesai tidak valid.',
            'endDate.after'                => 'Tanggal selesai harus setelah tanggal mulai.',
            'pickupLocation.required'      => 'Lokasi pengambilan wajib diisi.',
            'pickupLocation.min'           => 'Lokasi pengambilan minimal 5 karakter.',
            'returnLocation.required'      => 'Lokasi pengembalian wajib diisi.',
            'returnLocation.min'           => 'Lokasi pengembalian minimal 5 karakter.',
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
                'pickup_location' => $this->pickupLocation,
                'return_location' => $this->returnLocation,
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
