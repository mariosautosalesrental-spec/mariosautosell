<?php

namespace App\Livewire;

use App\Models\Car;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Car detail in a modal on the home page. Cards fire `open-car` with the car id.
 * Only cars on the site can be opened (hidden and sold cars are ignored).
 */
class CarDetail extends Component
{
    public ?int $carId = null;

    #[On('open-car')]
    public function open(int $id): void
    {
        $this->carId = Car::active()->whereKey($id)->value('id');
    }

    public function close(): void
    {
        $this->carId = null;
    }

    public function render()
    {
        $car = $this->carId === null
            ? null
            : Car::active()->with(['photos', 'bodyType', 'transmission', 'drivetrain'])->find($this->carId);

        return view('livewire.car-detail', ['car' => $car]);
    }
}
