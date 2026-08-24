<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MyRentalController extends Controller
{
    /**
     * Display the authenticated customer's rental history.
     */
    public function index(Request $request)
    {
        $rentals = $request->user()
            ->rentals()
            ->with('car')
            ->orderByDesc('created_at')
            ->get();

        return view('my-rentals.index', compact('rentals'));
    }
}
