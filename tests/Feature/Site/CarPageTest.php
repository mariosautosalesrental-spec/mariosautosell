<?php

namespace Tests\Feature\Site;

use App\Models\Car;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarPageTest extends TestCase
{
    use RefreshDatabase;

    private function active(array $attributes = []): Car
    {
        Setting::factory()->create(['whatsapp' => '19784174036']);

        return Car::factory()->create(array_merge(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 18900, 'mileage' => 58200], $attributes));
    }

    public function test_car_page_renders_active_car(): void
    {
        $car = $this->active();

        $this->get(route('cars.show', $car->fresh()->slug))
            ->assertOk()
            ->assertSee('2019 Jeep Compass')
            ->assertSee('$18,900')
            ->assertSee('58,200 mi')
            ->assertSee('Includes dealer fees. Plus tax, title & registration.')
            ->assertSee('WhatsApp about this car')
            ->assertSee('Back to all cars');
    }

    public function test_car_page_returns_404_for_hidden_car(): void
    {
        $car = $this->active(['is_active' => false]);

        $this->get('/cars/'.$car->fresh()->slug)->assertNotFound();
    }

    public function test_car_page_returns_404_for_sold_car(): void
    {
        $car = $this->active();
        $slug = $car->fresh()->slug;
        $this->get("/cars/{$slug}")->assertOk();

        $car->markAsSold();

        $this->get("/cars/{$slug}")->assertNotFound();
    }

    public function test_car_page_returns_404_for_unknown_slug(): void
    {
        Setting::factory()->create();

        $this->get('/cars/does-not-exist')->assertNotFound();
    }

    public function test_car_page_title_has_expected_format(): void
    {
        $car = $this->active();

        $this->get('/cars/'.$car->fresh()->slug)
            ->assertSee('<title>2019 Jeep Compass Limited 4x4 for sale in Dracut, MA</title>', false);
    }

    public function test_car_page_title_without_trim(): void
    {
        $car = $this->active(['trim' => null, 'title' => '2016 Fiat Toro']);

        $this->get('/cars/'.$car->fresh()->slug)->assertSee('<title>2016 Fiat Toro for sale in Dracut, MA</title>', false);
    }

    public function test_car_page_has_meta_description(): void
    {
        $car = $this->active();

        $this->get('/cars/'.$car->fresh()->slug)->assertSee('<meta name="description" content="2019 Jeep Compass Limited 4x4 for sale in Dracut, MA. $18,900', false);
    }

    public function test_car_page_shows_warranty(): void
    {
        $car = $this->active(['mileage' => 58200]);

        $this->get('/cars/'.$car->fresh()->slug)->assertSee('covered for 60 days or 2,500 miles.');
    }

    public function test_car_page_escapes_the_description(): void
    {
        $car = $this->active(['description' => '<script>alert(1)</script>']);

        $this->get('/cars/'.$car->fresh()->slug)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_card_modal_and_page_use_same_whatsapp_link(): void
    {
        $car = $this->active();
        $car->load('photos', 'transmission', 'drivetrain');
        $expected = $car->whatsappUrl();

        $this->blade('<x-car-card :car="$car" />', ['car' => $car])->assertSee(e($expected), false);
        $this->get('/cars/'.$car->fresh()->slug)->assertSee(e($expected), false);
    }

    public function test_whatsapp_links_follow_setting_number(): void
    {
        $car = $this->active();
        Setting::current()->update(['whatsapp' => '15551234567']);

        $this->get('/cars/'.$car->fresh()->slug)->assertSee('https://wa.me/15551234567?text=', false);
    }

    public function test_card_opens_the_modal_and_keeps_a_crawlable_link(): void
    {
        $car = $this->active();
        $car->load('photos', 'transmission', 'drivetrain');

        $this->blade('<x-car-card :car="$car" />', ['car' => $car])
            ->assertSee("Livewire.dispatch('open-car', { id: {$car->id} })", false)
            ->assertSee('href="'.route('cars.show', $car->fresh()->slug).'"', false);
    }
}
