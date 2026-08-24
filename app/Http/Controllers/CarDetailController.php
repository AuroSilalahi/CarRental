<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\View\View;

class CarDetailController extends Controller
{
    /**
     * Display the car detail page.
     *
     * GET /cars/{id}
     *
     * Requirements: 6.4, 7.1
     */
    public function show(int $id): View
    {
        $car = Car::findOrFail($id);

        return view('cars.show', compact('car'));
    }
}
