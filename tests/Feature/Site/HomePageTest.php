<?php

namespace Tests\Feature\Site;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_responds_with_the_main_sections_in_order(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $positions = array_map(fn (string $needle) => strpos($html, $needle), [
            '<header class="site">',
            'class="hero"',
            'id="searchForm"',
            'id="makesH"',
            'id="inventory"',
            '<footer class="site">',
        ]);

        $this->assertNotContains(false, $positions, 'A section is missing from the home page');
        $this->assertSame($positions, collect($positions)->sort()->values()->all(), 'Sections are out of order');
    }

    public function test_home_has_hero_copy_and_browse_button(): void
    {
        $this->get('/')
            ->assertSee('Used cars · Dracut, MA')
            ->assertSee('Quality used cars. Prices that make sense.')
            ->assertSee('A local, family-run lot where you get a good car and a straight answer.')
            ->assertSee('href="#inventory"', false)
            ->assertSee('Browse our cars')
            ->assertSee('Aerial view of a used car lot', false);
    }

    public function test_home_has_expected_title_and_description(): void
    {
        $this->get('/')
            ->assertSee('<title>'.e("Used Cars in Dracut, MA | Mario's Auto Sales & Rentals").'</title>', false)
            ->assertSee('<meta name="description"', false);
    }

    public function test_home_renders_store_info_from_settings(): void
    {
        Setting::factory()->create([
            'street' => '99 Test St', 'city' => 'Lowell', 'zip' => '01850', 'phone' => '(978) 555-0100',
        ]);

        $this->get('/')
            ->assertSee('99 Test St')
            ->assertSee('Lowell, MA 01850')
            ->assertSee('(978) 555-0100');
    }

    public function test_home_has_no_hardcoded_contact_data(): void
    {
        Setting::factory()->create([
            'whatsapp' => '15550001111', 'phone' => '(555) 000-1111', 'street' => '1 Other Rd',
            'city' => 'Boston', 'state' => 'MA', 'zip' => '02101',
            'hours_mon_fri' => '7:00 AM – 7:00 PM', 'hours_sat' => 'Noon – 1 PM', 'hours_sun' => 'Open',
        ]);

        $this->get('/')
            ->assertDontSee('417-4036')
            ->assertDontSee('1336 Merrimack')
            ->assertDontSee('19784174036')
            ->assertSee('(555) 000-1111')
            ->assertSee('7:00 AM – 7:00 PM');
    }

    public function test_updated_setting_appears_on_next_request(): void
    {
        $setting = Setting::factory()->create(['phone' => '(978) 111-1111']);
        $this->get('/')->assertSee('(978) 111-1111');

        $setting->update(['phone' => '(978) 222-2222']);

        $this->get('/')->assertSee('(978) 222-2222')->assertDontSee('(978) 111-1111');
    }
}
