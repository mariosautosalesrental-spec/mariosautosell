<?php

namespace Tests\Feature\Site;

use App\Livewire\CarDetail;
use App\Livewire\Inventory;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_every_section_in_order(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $positions = array_map(fn (string $needle) => strpos($html, $needle), [
            'class="hero"', 'id="searchForm"', 'id="makesH"', 'id="inventory"',
            'class="why"', 'id="trade"', 'id="visit"', '<footer class="site">',
        ]);

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
    }

    public function test_trade_in_button_links_to_whatsapp_with_message(): void
    {
        Setting::factory()->create(['whatsapp' => '15551234567']);

        $this->get('/')
            ->assertSee('Trading in? Get a cash offer.')
            ->assertSee('Send the VIN, mileage and a few photos on WhatsApp. We reply fast.')
            ->assertSee('Value my car')
            ->assertSee('https://wa.me/15551234567?text=Hi%20Mario%2C%20I%27d%20like%20an%20offer%20on%20my%20car.', false);
    }

    public function test_trade_in_has_no_form(): void
    {
        $html = $this->get('/')->getContent();
        $trade = substr($html, strpos($html, 'id="trade"'), 800);

        $this->assertStringNotContainsString('<form', $trade);
        $this->assertStringNotContainsString('<input', $trade);
    }

    public function test_trust_block_has_three_columns(): void
    {
        $this->get('/')
            ->assertSee('Straight prices')
            ->assertSee('MA used-car warranty')
            ->assertSee('Local &amp; family-run', false);
    }

    public function test_visit_section_uses_setting_address_in_map_links(): void
    {
        Setting::factory()->create([
            'street' => '99 Test St', 'city' => 'Lowell', 'state' => 'MA', 'zip' => '01850',
            'phone' => '(978) 555-0100', 'hours_mon_fri' => '8:00 AM – 5:00 PM', 'hours_sat' => 'By appointment', 'hours_sun' => 'Closed',
        ]);

        $this->get('/')
            ->assertSee('Visit the lot')
            ->assertSee('99 Test St')
            ->assertSee('Lowell, MA 01850')
            ->assertSee('(978) 555-0100')
            ->assertSee('8:00 AM – 5:00 PM')
            ->assertSee('By appointment')
            ->assertSee('Closed')
            ->assertSee('<iframe src="https://www.google.com/maps?q=99+Test+St%2C+Lowell%2C+MA+01850&amp;output=embed"', false)
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=99+Test+St+Lowell+MA+01850', false)
            ->assertSee('Open in Google Maps');
    }

    public function test_visit_map_matches_the_mockup_link_for_the_default_address(): void
    {
        $this->assertSame(
            'https://www.google.com/maps/search/?api=1&query=1336+Merrimack+Ave+Dracut+MA+01826',
            Setting::factory()->make()->mapsUrl(),
        );
    }

    public function test_map_iframe_is_accessible_and_lazy(): void
    {
        $this->get('/')
            ->assertSee('title="Map showing 1336 Merrimack Ave, Dracut, MA 01826"', false)
            ->assertSee('loading="lazy"', false);
    }

    public function test_home_includes_the_car_detail_modal_component(): void
    {
        $this->get('/')->assertSeeLivewire(CarDetail::class)->assertSeeLivewire(Inventory::class);
    }
}
