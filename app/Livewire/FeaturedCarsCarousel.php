<?php

namespace App\Livewire;

use App\Models\Car;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class FeaturedCarsCarousel extends Component
{
    /**
     * @var Collection<int, Car>
     */
    public Collection $cars;

    public int $currentIndex = 0;

    public function mount(): void
    {
        $this->cars = Car::available()
            ->latest()
            ->take(8)
            ->get();
    }

    public function next(): void
    {
        if ($this->cars->count() === 0) {
            return;
        }
        $this->currentIndex = ($this->currentIndex + 1) % $this->cars->count();
    }

    public function prev(): void
    {
        if ($this->cars->count() === 0) {
            return;
        }
        $this->currentIndex = ($this->currentIndex - 1 + $this->cars->count()) % $this->cars->count();
    }

    public function render()
    {
        return view('livewire.featured-cars-carousel');
    }
}
