<?php

namespace Tests\Feature\Seo;

use App\Models\Car;
use App\Models\Category;
use App\Models\Setting;
use App\Support\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function jsonLd(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m, 'No JSON-LD block found');
        $decoded = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    // ------------------------------------------------------------------ meta

    public function test_home_title_matches_spec_and_description_is_present(): void
    {
        $this->get('/')
            ->assertSee('<title>'.e("Used Cars in Dracut, MA | Mario's Auto Sales & Rentals").'</title>', false)
            ->assertSee('<meta name="description" content="Quality used cars', false);
    }

    public function test_pages_have_canonical_and_open_graph_tags(): void
    {
        $this->get('/terms')
            ->assertSee('<link rel="canonical" href="'.url('/terms').'">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta property="og:description"', false)
            ->assertSee('<meta property="og:image"', false);
    }

    public function test_canonical_ignores_the_query_string(): void
    {
        $this->get('/?make=jeep&max=15000')->assertSee('<link rel="canonical" href="'.url('/').'">', false);
    }

    // ------------------------------------------------------------- json-ld

    public function test_home_has_valid_autodealer_json_ld(): void
    {
        Setting::factory()->create([
            'street' => '99 Test St', 'city' => 'Lowell', 'state' => 'MA', 'zip' => '01850', 'phone' => '(978) 555-0100',
            'hours_mon_fri' => '9:00 AM – 6:00 PM', 'hours_sat' => '9:00 AM – 4:00 PM', 'hours_sun' => 'Closed',
        ]);

        $data = $this->jsonLd($this->get('/')->getContent());

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('AutoDealer', $data['@type']);
        $this->assertSame("Mario's Auto Sales & Rentals", $data['name']);
        $this->assertSame('(978) 555-0100', $data['telephone']);
        $this->assertSame('99 Test St', $data['address']['streetAddress']);
        $this->assertSame('Lowell', $data['address']['addressLocality']);
        $this->assertSame('MA', $data['address']['addressRegion']);
        $this->assertSame('01850', $data['address']['postalCode']);
        $this->assertSame('US', $data['address']['addressCountry']);
    }

    public function test_autodealer_opening_hours_are_parsed_and_closed_days_are_left_out(): void
    {
        $data = StructuredData::autoDealer(Setting::factory()->make([
            'hours_mon_fri' => '9:00 AM – 6:00 PM', 'hours_sat' => '9 AM - 12:30 PM', 'hours_sun' => 'Closed',
        ]));

        $specs = $data['openingHoursSpecification'];

        $this->assertCount(2, $specs);
        $this->assertSame(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], $specs[0]['dayOfWeek']);
        $this->assertSame('09:00', $specs[0]['opens']);
        $this->assertSame('18:00', $specs[0]['closes']);
        $this->assertSame(['Saturday'], $specs[1]['dayOfWeek']);
        $this->assertSame('09:00', $specs[1]['opens']);
        $this->assertSame('12:30', $specs[1]['closes']);
    }

    public function test_autodealer_handles_noon_and_midnight_boundaries(): void
    {
        $data = StructuredData::autoDealer(Setting::factory()->make(['hours_mon_fri' => '12:00 PM – 11:00 PM', 'hours_sat' => '12:00 AM – 1:00 AM', 'hours_sun' => 'Closed']));

        $this->assertSame('12:00', $data['openingHoursSpecification'][0]['opens']);
        $this->assertSame('23:00', $data['openingHoursSpecification'][0]['closes']);
        $this->assertSame('00:00', $data['openingHoursSpecification'][1]['opens']);
    }

    public function test_autodealer_without_parseable_hours_has_no_hours_key(): void
    {
        $data = StructuredData::autoDealer(Setting::factory()->make(['hours_mon_fri' => 'Call us', 'hours_sat' => 'Closed', 'hours_sun' => 'Closed']));

        $this->assertArrayNotHasKey('openingHoursSpecification', $data);
    }

    public function test_car_page_has_valid_car_json_ld(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->for(Category::factory()->create(['name' => 'Jeep']))->withPhotos(2)->create([
            'title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4', 'price' => 18900, 'mileage' => 58200, 'color' => 'Gray',
        ]);

        $data = $this->jsonLd($this->get('/cars/'.$car->fresh()->slug)->getContent());

        $this->assertSame('Car', $data['@type']);
        $this->assertSame('2019 Jeep Compass Limited 4x4', $data['name']);
        $this->assertSame('Gray', $data['color']);
        $this->assertSame($car->transmission->name, $data['vehicleTransmission']);
        $this->assertSame($car->drivetrain->name, $data['driveWheelConfiguration']);
        $this->assertSame('Jeep', $data['brand']['name']);
        $this->assertSame(58200, $data['mileageFromOdometer']['value']);
        $this->assertSame('SMI', $data['mileageFromOdometer']['unitCode']);
        $this->assertSame(18900, $data['offers']['price']);
        $this->assertSame('USD', $data['offers']['priceCurrency']);
        $this->assertCount(2, $data['image']);
    }

    public function test_car_json_ld_leaves_out_missing_values(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create(['mileage' => null, 'color' => null]);
        $car->load('photos', 'category', 'bodyType', 'transmission', 'drivetrain');

        $data = StructuredData::car($car);

        $this->assertArrayNotHasKey('mileageFromOdometer', $data);
        $this->assertArrayNotHasKey('color', $data);
    }

    public function test_json_ld_cannot_close_the_script_tag(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create(['title' => '2019 </script><script>alert(1)</script>', 'trim' => null]);

        $html = $this->get('/cars/'.$car->fresh()->slug)->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertSame('2019 </script><script>alert(1)</script>', $this->jsonLd($html)['name']);
    }

    public function test_hidden_car_page_has_no_json_ld(): void
    {
        $car = Car::factory()->inactive()->create();

        $this->get('/cars/'.$car->fresh()->slug)->assertNotFound()->assertDontSee('application/ld+json', false);
    }

    // ------------------------------------------------------ sitemap and robots

    public function test_sitemap_lists_active_cars_and_legal_pages(): void
    {
        $active = Car::factory()->create();
        $base = rtrim(config('app.url'), '/');

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = (string) $url->loc;
        }

        $this->assertContains("{$base}/", $locs);
        $this->assertContains("{$base}/cars/{$active->fresh()->slug}", $locs);
        $this->assertContains("{$base}/privacy", $locs);
        $this->assertContains("{$base}/terms", $locs);
        $this->assertContains("{$base}/accessibility", $locs);
    }

    public function test_sitemap_excludes_hidden_sold_and_deleted_cars(): void
    {
        $hidden = Car::factory()->inactive()->create();
        $sold = Car::factory()->sold()->create();
        $deleted = Car::factory()->create();
        $deletedSlug = $deleted->fresh()->slug;
        $deleted->delete();
        $live = Car::factory()->create();

        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString($live->fresh()->slug, $body);
        $this->assertStringNotContainsString($hidden->fresh()->slug, $body);
        $this->assertStringNotContainsString($sold->fresh()->slug, $body);
        $this->assertStringNotContainsString($deletedSlug, $body);
    }

    public function test_sitemap_changes_when_a_car_is_sold(): void
    {
        $car = Car::factory()->create();
        $slug = $car->fresh()->slug;
        $this->get('/sitemap.xml')->assertSee($slug);

        $car->markAsSold();

        $this->get('/sitemap.xml')->assertDontSee($slug);
    }

    public function test_sitemap_is_valid_xml_with_lastmod(): void
    {
        Car::factory()->count(3)->create();

        $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());

        $this->assertNotFalse($xml);
        $this->assertCount(1 + 3 + 3, $xml->url);
        $this->assertNotEmpty((string) $xml->url[1]->lastmod);
    }

    public function test_robots_txt_points_to_sitemap_and_blocks_admin(): void
    {
        $base = rtrim(config('app.url'), '/');

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('User-agent: *')
            ->assertSee('Disallow: /admin')
            ->assertSee("Sitemap: {$base}/sitemap.xml");
    }

    // ------------------------------------------------------------- alt text

    public function test_car_photos_have_descriptive_alt_and_decorative_images_are_empty(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->withPhotos(2)->create(['color' => 'White', 'title' => '2018 Jeep Compass']);

        $this->get('/')->assertSee('alt="White 2018 Jeep Compass"', false);
        $this->get('/cars/'.$car->fresh()->slug)
            ->assertSee('alt="White 2018 Jeep Compass"', false)
            ->assertSee('alt=""', false);
    }
}
