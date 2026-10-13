<?php

namespace Tests\Feature\Site;

use App\Livewire\CarDetail;
use App\Models\Car;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CarDetailTest extends TestCase
{
    use RefreshDatabase;

    private function car(array $attributes = []): Car
    {
        Setting::factory()->create(['whatsapp' => '19784174036']);

        return Car::factory()->create(array_merge([
            'title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 18900, 'mileage' => 58200,
            'color' => 'Gray', 'engine' => '2.4L 4-cyl', 'description' => 'One owner, clean history.',
        ], $attributes));
    }

    public function test_opening_a_car_shows_its_details(): void
    {
        $car = $this->car();

        Livewire::test(CarDetail::class)
            ->assertDontSee('2019 Jeep Compass')
            ->dispatch('open-car', id: $car->id)
            ->assertSet('carId', $car->id)
            ->assertSee('2019 Jeep Compass')
            ->assertSee('Limited 4x4 · 58,200 mi')
            ->assertSee('$18,900')
            ->assertSee('Includes dealer fees. Plus tax, title & registration.')
            ->assertSee('2.4L 4-cyl')
            ->assertSee($car->transmission->name)
            ->assertSee($car->drivetrain->name)
            ->assertSee($car->bodyType->name)
            ->assertSee('Gray')
            ->assertSee('One owner, clean history.')
            ->assertSee('WhatsApp about this car');
    }

    public function test_close_hides_the_modal(): void
    {
        $car = $this->car();

        Livewire::test(CarDetail::class)
            ->dispatch('open-car', id: $car->id)
            ->assertSee('2019 Jeep Compass')
            ->call('close')
            ->assertSet('carId', null)
            ->assertDontSee('2019 Jeep Compass');
    }

    public function test_modal_is_a_dialog_with_accessible_close_button(): void
    {
        $car = $this->car();

        Livewire::test(CarDetail::class)
            ->dispatch('open-car', id: $car->id)
            ->assertSeeHtml('<dialog')
            ->assertSeeHtml('aria-labelledby="dTitle"')
            ->assertSeeHtml('aria-label="Close"')
            ->assertSeeHtml('showModal()');
    }

    public function test_hidden_and_sold_cars_cannot_be_opened(): void
    {
        $hidden = $this->car(['title' => '2010 Hidden Car', 'is_active' => false]);
        $sold = Car::factory()->sold()->create(['title' => '2011 Sold Car']);

        Livewire::test(CarDetail::class)
            ->dispatch('open-car', id: $hidden->id)
            ->assertSet('carId', null)
            ->assertDontSee('2010 Hidden Car')
            ->dispatch('open-car', id: $sold->id)
            ->assertSet('carId', null)
            ->assertDontSee('2011 Sold Car')
            ->dispatch('open-car', id: 99999)
            ->assertSet('carId', null);
    }

    public function test_optional_empty_fields_do_not_appear(): void
    {
        $car = $this->car(['trim' => null, 'mileage' => null, 'color' => null, 'engine' => null, 'description' => null]);

        Livewire::test(CarDetail::class)
            ->dispatch('open-car', id: $car->id)
            ->assertDontSeeHtml('<dt>Engine</dt>')
            ->assertDontSeeHtml('<dt>Color</dt>')
            ->assertDontSeeHtml('class="d-desc"')
            ->assertDontSeeHtml(' mi</span>')
            ->assertDontSee('MA used-car warranty')
            ->assertDontSee(' · ');
    }

    public function test_detail_shows_90_day_warranty_under_40000(): void
    {
        $car = $this->car(['mileage' => 39999]);

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)
            ->assertSee('MA used-car warranty:')
            ->assertSee('with 39,999 miles, this car is covered for 90 days or 3,750 miles.');
    }

    public function test_detail_shows_each_warranty_band(): void
    {
        foreach ([[40000, '60 days or 2,500 miles'], [80000, '30 days or 1,250 miles'], [124999, '30 days or 1,250 miles']] as [$miles, $text]) {
            $car = $this->car(['mileage' => $miles]);
            Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)->assertSee("covered for {$text}.");
        }
    }

    public function test_detail_hides_warranty_at_125000(): void
    {
        $car = $this->car(['mileage' => 125000]);

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)
            ->assertDontSee('MA used-car warranty')
            ->assertSee('125,000 mi');
    }

    public function test_detail_shows_warranty_for_zero_miles(): void
    {
        $car = $this->car(['mileage' => 0]);

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)->assertSee('covered for 90 days or 3,750 miles.');
    }

    public function test_gallery_has_navigation_only_with_several_photos(): void
    {
        $single = $this->car();
        $many = Car::factory()->withPhotos(3)->create();

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $single->id)
            ->assertDontSeeHtml('aria-label="Next photo"');

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $many->id)
            ->assertSeeHtml('aria-label="Next photo"')
            ->assertSeeHtml('aria-label="Previous photo"')
            ->assertSeeHtml('aria-label="Photos"');
    }

    public function test_gallery_uses_conversions_and_descriptive_alt(): void
    {
        $car = $this->car(['color' => 'Gray', 'title' => '2019 Jeep Compass']);
        $cover = $car->photos()->first();

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)
            ->assertSeeHtml('alt="Gray 2019 Jeep Compass"')
            ->assertSeeHtml($cover->largeUrl());
    }

    public function test_car_without_photos_shows_placeholder(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->inactive()->withoutPhotos()->create();
        $car->forceFill(['is_active' => true])->save(); // a photoless car on the site, e.g. after photos were removed

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)->assertSee('No photo yet');
    }

    public function test_whatsapp_button_links_to_the_car_message(): void
    {
        $car = $this->car();

        Livewire::test(CarDetail::class)->dispatch('open-car', id: $car->id)
            ->assertSeeHtml(e($car->whatsappUrl()))
            ->assertSeeHtml('https://wa.me/19784174036?text=');
    }
}
