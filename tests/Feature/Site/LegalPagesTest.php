<?php

namespace Tests\Feature\Site;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_return_200(): void
    {
        foreach (['/privacy', '/terms', '/accessibility'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_each_legal_page_has_its_title_and_a_back_link(): void
    {
        $this->get('/privacy')->assertSee('<h1>Privacy Policy</h1>', false)->assertSee('Back to the lot');
        $this->get('/terms')->assertSee('<h1>Terms of Use</h1>', false);
        $this->get('/accessibility')->assertSee('<h1>Accessibility</h1>', false);
    }

    public function test_accessibility_page_shows_store_phone_and_address(): void
    {
        Setting::factory()->create(['street' => '99 Test St', 'city' => 'Lowell', 'state' => 'MA', 'zip' => '01850', 'phone' => '(978) 555-0100']);

        $this->get('/accessibility')
            ->assertSee('Phone: (978) 555-0100')
            ->assertSee('Visit: 99 Test St, Lowell, MA 01850')
            ->assertSee('WCAG')
            ->assertSee('2.1');
    }

    public function test_privacy_page_shows_store_contact(): void
    {
        Setting::factory()->create(['phone' => '(978) 555-0100', 'street' => '99 Test St', 'city' => 'Lowell', 'state' => 'MA', 'zip' => '01850']);

        $this->get('/privacy')->assertSee('calling (978) 555-0100')->assertSee('99 Test St, Lowell, MA 01850 · (978) 555-0100');
    }

    public function test_terms_mentions_massachusetts_warranty(): void
    {
        $this->get('/terms')
            ->assertSee('Massachusetts Used Vehicle Warranty Law')
            ->assertSee('M.G.L. c. 90')
            ->assertSee('Advertised prices include all dealer fees');
    }

    public function test_footer_links_to_the_three_pages(): void
    {
        $this->get('/')
            ->assertSee('href="'.route('legal.privacy').'"', false)
            ->assertSee('href="'.route('legal.terms').'"', false)
            ->assertSee('href="'.route('legal.accessibility').'"', false);
    }

    public function test_legal_pages_have_their_own_titles_and_descriptions(): void
    {
        $this->get('/privacy')->assertSee('<title>Privacy Policy | Mario&#039;s Auto Sales &amp; Rentals</title>', false);
        $this->get('/terms')->assertSee('<title>Terms of Use | Mario&#039;s Auto Sales &amp; Rentals</title>', false);
        $this->get('/accessibility')->assertSee('<title>Accessibility | Mario&#039;s Auto Sales &amp; Rentals</title>', false);
    }
}
