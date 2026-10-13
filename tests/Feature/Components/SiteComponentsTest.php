<?php

namespace Tests\Feature\Components;

use App\Models\Car;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_shows_store_info_from_settings(): void
    {
        Setting::factory()->create([
            'street' => '99 Test St', 'city' => 'Lowell', 'state' => 'MA', 'zip' => '01850',
            'phone' => '(978) 555-0100', 'hours_mon_fri' => '8:00 AM – 5:00 PM',
            'hours_sat' => '9:00 AM – 1:00 PM', 'hours_sun' => 'Closed',
        ]);

        $this->blade('<x-site.header />')
            ->assertSee('99 Test St')
            ->assertSee('Lowell, MA 01850')
            ->assertSee('(978) 555-0100')
            ->assertSee('Mon–Fri 8:00 AM – 5:00 PM')
            ->assertSee('Sat 9:00 AM – 1:00 PM · Sun Closed', false)
            ->assertSee('images/logo.png', false);
    }

    public function test_header_has_no_navigation_menu_or_whatsapp_button(): void
    {
        Setting::factory()->create();

        $this->blade('<x-site.header />')
            ->assertDontSee('<nav', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_footer_has_legal_links_price_notice_and_copyright(): void
    {
        $this->blade('<x-site.footer />')
            ->assertSee('/privacy', false)
            ->assertSee('/terms', false)
            ->assertSee('/accessibility', false)
            ->assertSee('Privacy Policy')
            ->assertSee('exclude sales tax, title and registration')
            ->assertSee('© '.now()->year);
    }

    public function test_layout_has_skip_link_language_and_main_landmark(): void
    {
        Setting::factory()->create();

        $this->blade('<x-layouts.app>Hello</x-layouts.app>')
            ->assertSee('<html lang="en">', false)
            ->assertSee('href="#main"', false)
            ->assertSee('<main id="main">', false)
            ->assertSee('Hello');
    }

    public function test_layout_uses_the_page_title_and_description(): void
    {
        Setting::factory()->create();

        $this->blade('<x-layouts.app title="Custom title" description="Custom description">x</x-layouts.app>')
            ->assertSee('<title>Custom title</title>', false)
            ->assertSee('content="Custom description"', false);
    }

    public function test_icon_is_decorative_by_default(): void
    {
        $this->blade('<x-ui.icon name="phone" />')->assertSee('aria-hidden="true"', false);
    }

    public function test_button_renders_link_or_button(): void
    {
        $this->blade('<x-button variant="wa" href="https://example.com">Go</x-button>')
            ->assertSee('<a href="https://example.com"', false)
            ->assertSee('btn btn-wa', false);

        $this->blade('<x-button>Click</x-button>')->assertSee('<button type="button"', false);
    }

    public function test_car_card_shows_car_data_and_whatsapp_link(): void
    {
        Setting::factory()->create(['whatsapp' => '19784174036']);
        $car = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 18900, 'mileage' => 58200, 'color' => 'Gray']);
        $car->load('photos', 'transmission', 'drivetrain');

        $this->blade('<x-car-card :car="$car" />', ['car' => $car])
            ->assertSee('2019 Jeep Compass')
            ->assertSee('Limited 4x4')
            ->assertSee('58,200 mi')
            ->assertSee($car->transmission->name)
            ->assertSee($car->drivetrain->name)
            ->assertSee('$18,900')
            ->assertSee('incl. dealer fees')
            ->assertSee('Ask about this car')
            ->assertSee('https://wa.me/19784174036?text=', false)
            ->assertSee('alt="Gray 2019 Jeep Compass"', false)
            ->assertSee('/cars/'.$car->fresh()->slug, false);
    }

    public function test_price_drop_badge_only_when_flagged(): void
    {
        Setting::factory()->create();
        $plain = Car::factory()->create()->load('photos', 'transmission', 'drivetrain');
        $drop = Car::factory()->priceDrop()->create()->load('photos', 'transmission', 'drivetrain');

        $this->blade('<x-car-card :car="$car" />', ['car' => $plain])->assertDontSee('Price drop');
        $this->blade('<x-car-card :car="$car" />', ['car' => $drop])->assertSee('Price drop');
    }

    public function test_car_card_without_photo_or_optional_fields_stays_clean(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->inactive()->withoutPhotos()->create(['trim' => null, 'mileage' => null]);
        $car->load('photos', 'transmission', 'drivetrain');

        $this->blade('<x-car-card :car="$car" />', ['car' => $car])
            ->assertSee('No photo yet')
            ->assertDontSee(' mi')
            ->assertDontSee('class="meta"', false);
    }

    public function test_car_card_uses_the_cover_photo_conversion_or_original(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->withPhotos(2)->create();
        $car->load('photos', 'transmission', 'drivetrain');
        $cover = $car->photos->first();

        $this->blade('<x-car-card :car="$car" />', ['car' => $car])
            ->assertSee($cover->thumbUrl(), false);
    }
}
