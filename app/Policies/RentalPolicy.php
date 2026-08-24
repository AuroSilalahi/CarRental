<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rental $rental): bool
    {
        return $user->id === $rental->customer_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rental $rental): bool
    {
        return $user->id === $rental->customer_id;
    }

    public function delete(User $user, Rental $rental): bool
    {
        return false;
    }
}
