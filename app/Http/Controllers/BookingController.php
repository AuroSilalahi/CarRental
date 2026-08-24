<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display the booking confirmation page.
     *
     * Requirements: 8.1, 8.2
     */
    public function show(Rental $rental): \Illuminate\View\View
    {
        if (auth()->id() !== $rental->customer_id) {
            abort(403);
        }

        $rental->load('car', 'payment');

        return view('bookings.show', compact('rental'));
    }
}
