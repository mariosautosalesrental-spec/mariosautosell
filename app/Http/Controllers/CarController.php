<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Support\Format;
use Illuminate\Contracts\View\View;

class CarController extends Controller
{
    /**
     * A car's own page. Only cars on the site exist here: hidden, sold and
     * unknown slugs are a 404.
     */
    public function show(string $slug): View
    {
        $car = Car::active()
            ->with(['photos', 'bodyType', 'transmission', 'drivetrain'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('car-page', [
            'car' => $car,
            'description' => trim("{$car->fullName()} for sale in Dracut, MA. ".Format::price($car->price).' including dealer fees. Message Mario on WhatsApp.'),
        ]);
    }
}
