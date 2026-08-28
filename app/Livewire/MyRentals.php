<?php

namespace App\Livewire;

use App\Models\Rental;
use Livewire\Component;
use Livewire\WithPagination;

class MyRentals extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public ?Rental $selectedRental = null;
    public bool $showModal = false;

    public function openDetailModal(int $rentalId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->selectedRental = Rental::where('customer_id', $user->id)
            ->with(['car', 'payment', 'statusLogs'])
            ->find($rentalId);

        if ($this->selectedRental) {
            $this->showModal = true;
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedRental = null;
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $query = Rental::where('customer_id', $user->id)
            ->with(['car', 'payment']);

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        $rentals = $query->latest()->paginate(10);

        return view('livewire.my-rentals', [
            'rentals' => $rentals,
        ]);
    }
}
