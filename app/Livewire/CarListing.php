<?php

namespace App\Livewire;

use App\Models\Car;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Livewire\Component;

class CarListing extends Component
{
    public string $search = '';
    public string $type = '';
    public string $brand = '';
    public string $capacity = '';
    public string $availabilityFilter = ''; // 'available' or 'unavailable'

    public string $startDate = '';
    public string $endDate = '';

    public function updatedStartDate(): void
    {
        $this->validateDates();
    }

    public function updatedEndDate(): void
    {
        $this->validateDates();
    }

    private function validateDates(): void
    {
        if ($this->startDate && $this->endDate) {
            try {
                $start = Carbon::parse($this->startDate);
                $end = Carbon::parse($this->endDate);

                if ($end->lte($start)) {
                    $this->addError('endDate', 'Tanggal selesai harus setelah tanggal mulai.');
                } else {
                    $this->resetErrorBag('endDate');
                }
            } catch (\Exception) {
                // Ignore parse errors
            }
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->brand = '';
        $this->capacity = '';
        $this->availabilityFilter = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Car::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('brand', 'like', '%' . $this->search . '%')
                  ->orWhere('model', 'like', '%' . $this->search . '%')
                  ->orWhere('license_plate', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        if ($this->brand !== '') {
            $query->where('brand', $this->brand);
        }

        if ($this->capacity !== '') {
            $query->where('passenger_capacity', '>=', (int)$this->capacity);
        }

        if ($this->availabilityFilter === 'available') {
            $query->available();
        } elseif ($this->availabilityFilter === 'unavailable') {
            $query->where(function ($q) {
                $q->where('is_available', false)
                  ->orWhereHas('rentals', function ($r) {
                      $r->whereIn('status', [
                          \App\Enums\RentalStatus::Confirmed,
                          \App\Enums\RentalStatus::Active,
                      ]);
                  });
            });
        }

        $cars = $query->latest()->get();

        // If date range filter is active, check availability for each car via AvailabilityService
        if ($this->startDate && $this->endDate) {
            try {
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end = Carbon::parse($this->endDate)->startOfDay();

                if ($end->gt($start)) {
                    /** @var AvailabilityService $availabilityService */
                    $availabilityService = app(AvailabilityService::class);

                    $cars->transform(function (Car $car) use ($availabilityService, $start, $end) {
                        $isAvailable = $car->is_available && $availabilityService->isAvailable($car->id, $start, $end);
                        $car->is_date_available = $isAvailable;
                        return $car;
                    });
                }
            } catch (\Exception) {
                // Ignore date parsing exception
            }
        }

        $brands = Car::distinct()->pluck('brand')->filter()->values();
        $types = Car::distinct()->pluck('type')->filter()->values();

        return view('livewire.car-listing', [
            'cars' => $cars,
            'brands' => $brands,
            'types' => $types,
        ]);
    }
}
