<?php

namespace Tests\Feature\Models;

use App\Models\BodyType;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Category;
use App\Models\Drivetrain;
use App\Models\Setting;
use App\Models\Transmission;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------- relationships

    public function test_car_belongs_to_category_and_lookups_and_has_photos(): void
    {
        $car = Car::factory()->withPhotos(3)->create();

        $this->assertInstanceOf(Category::class, $car->category);
        $this->assertInstanceOf(BodyType::class, $car->bodyType);
        $this->assertInstanceOf(Transmission::class, $car->transmission);
        $this->assertInstanceOf(Drivetrain::class, $car->drivetrain);
        $this->assertCount(3, $car->photos);
        $this->assertTrue($car->bodyType->cars->contains($car));
        $this->assertTrue($car->transmission->cars->contains($car));
        $this->assertTrue($car->drivetrain->cars->contains($car));
    }

    public function test_photos_come_ordered_by_sort_order(): void
    {
        $car = Car::factory()->inactive()->withoutPhotos()->create();
        $second = CarPhoto::factory()->for($car)->create(['sort_order' => 2, 'file_path' => 'cars/1/b.jpg']);
        $first = CarPhoto::factory()->for($car)->create(['sort_order' => 1, 'file_path' => 'cars/1/a.jpg']);

        $this->assertSame([$first->id, $second->id], $car->photos->pluck('id')->all());
    }

    public function test_casts(): void
    {
        $car = Car::factory()->sold()->create(['price' => 9995])->fresh();

        $this->assertIsInt($car->price);
        $this->assertIsBool($car->is_active);
        $this->assertIsBool($car->price_drop);
        $this->assertInstanceOf(CarbonImmutable::class, $car->sold_at->toImmutable());
    }

    public function test_mass_assignment_cannot_set_slug_or_sale_fields(): void
    {
        $car = Car::factory()->create();

        $car->fill(['slug' => 'hacked', 'sold_at' => now(), 'sold_price' => 1]);

        $this->assertNotSame('hacked', $car->slug);
        $this->assertNull($car->sold_at);
        $this->assertNull($car->sold_price);
    }

    // ----------------------------------------------------------------- scopes

    public function test_active_scope_excludes_hidden_and_sold(): void
    {
        $active = Car::factory()->create();
        Car::factory()->inactive()->create();
        Car::factory()->sold()->create();

        $this->assertSame([$active->id], Car::active()->pluck('id')->all());
    }

    public function test_sold_and_hidden_scopes(): void
    {
        Car::factory()->create();
        $hidden = Car::factory()->inactive()->create();
        $sold = Car::factory()->sold()->create();

        $this->assertSame([$hidden->id], Car::hidden()->pluck('id')->all());
        $this->assertSame([$sold->id], Car::sold()->pluck('id')->all());
    }

    public function test_status_attribute_is_derived_from_flags(): void
    {
        $this->assertSame('on_site', Car::factory()->create()->status);
        $this->assertSame('hidden', Car::factory()->inactive()->create()->status);
        $this->assertSame('sold', Car::factory()->sold()->create()->status);
    }

    public function test_latest_first_orders_by_created_at_desc(): void
    {
        $old = Car::factory()->create(['created_at' => now()->subDays(3)]);
        $new = Car::factory()->create(['created_at' => now()->subDay()]);
        $middle = Car::factory()->create(['created_at' => now()->subDays(2)]);

        $this->assertSame([$new->id, $middle->id, $old->id], Car::latestFirst()->pluck('id')->all());
    }

    // ------------------------------------------------------------------- slug

    public function test_slug_is_built_from_title_trim_and_id(): void
    {
        $car = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4']);

        $this->assertSame('2019-jeep-compass-limited-4x4-'.$car->id, $car->fresh()->slug);
    }

    public function test_slug_without_trim_has_no_double_dash(): void
    {
        $car = Car::factory()->create(['title' => '2016 Fiat Toro', 'trim' => null]);

        $this->assertSame('2016-fiat-toro-'.$car->id, $car->fresh()->slug);
        $this->assertStringNotContainsString('--', $car->fresh()->slug);
    }

    public function test_slug_updates_when_title_changes_and_stays_unique(): void
    {
        $a = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => null]);
        $b = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => null]);

        $this->assertNotSame($a->fresh()->slug, $b->fresh()->slug);

        $a->update(['title' => '2020 Jeep Renegade']);

        $this->assertSame('2020-jeep-renegade-'.$a->id, $a->fresh()->slug);
    }

    public function test_slug_is_kept_when_other_fields_change(): void
    {
        $car = Car::factory()->create();
        $slug = $car->fresh()->slug;

        $car->update(['price' => 12345]);

        $this->assertSame($slug, $car->fresh()->slug);
    }

    // --------------------------------------------------------------- warranty

    public function test_warranty_at_39999_and_40000(): void
    {
        $this->assertSame('90 days or 3,750 miles', $this->carWithMileage(39_999)->maWarranty());
        $this->assertSame('60 days or 2,500 miles', $this->carWithMileage(40_000)->maWarranty());
    }

    public function test_warranty_at_79999_and_80000(): void
    {
        $this->assertSame('60 days or 2,500 miles', $this->carWithMileage(79_999)->maWarranty());
        $this->assertSame('30 days or 1,250 miles', $this->carWithMileage(80_000)->maWarranty());
    }

    public function test_warranty_at_124999_and_125000(): void
    {
        $this->assertSame('30 days or 1,250 miles', $this->carWithMileage(124_999)->maWarranty());
        $this->assertNull($this->carWithMileage(125_000)->maWarranty());
    }

    public function test_warranty_at_zero_miles(): void
    {
        $this->assertSame('90 days or 3,750 miles', $this->carWithMileage(0)->maWarranty());
    }

    public function test_warranty_is_null_without_mileage(): void
    {
        $this->assertNull($this->carWithMileage(null)->maWarranty());
    }

    private function carWithMileage(?int $mileage): Car
    {
        return Car::factory()->make(['mileage' => $mileage]);
    }

    // --------------------------------------------------------------- whatsapp

    public function test_car_whatsapp_url_contains_encoded_message(): void
    {
        Setting::factory()->create(['whatsapp' => '19784174036']);
        $car = Car::factory()->create(['title' => '2019 Jeep Compass', 'trim' => 'Limited 4x4']);

        $this->assertSame(
            'https://wa.me/19784174036?text='.rawurlencode('Hi Mario, is the 2019 Jeep Compass Limited 4x4 still available?'),
            $car->whatsappUrl(),
        );
    }

    public function test_car_whatsapp_url_without_trim_is_clean(): void
    {
        Setting::factory()->create();
        $car = Car::factory()->create(['title' => '2016 Fiat Toro', 'trim' => null]);

        $url = $car->whatsappUrl();

        $this->assertStringEndsWith(rawurlencode('Hi Mario, is the 2016 Fiat Toro still available?'), $url);
        $this->assertStringNotContainsString('%20%20', $url);
        $this->assertStringNotContainsString('null', $url);
    }

    public function test_whatsapp_url_uses_updated_setting_number(): void
    {
        $setting = Setting::factory()->create(['whatsapp' => '19784174036']);
        $car = Car::factory()->create();

        $this->assertStringStartsWith('https://wa.me/19784174036?', $car->whatsappUrl());

        $setting->update(['whatsapp' => '15551234567']);

        $this->assertStringStartsWith('https://wa.me/15551234567?', $car->whatsappUrl());
    }

    // ------------------------------------------------------------------- sales

    public function test_mark_as_sold_sets_fields_and_deactivates(): void
    {
        $car = Car::factory()->create(['price' => 12995]);
        $at = now()->subHour();

        $car->markAsSold($at, 11500);

        $fresh = $car->fresh();
        $this->assertSame($at->timestamp, $fresh->sold_at->timestamp);
        $this->assertSame(11500, $fresh->sold_price);
        $this->assertFalse($fresh->is_active);
        $this->assertSame('sold', $fresh->status);
    }

    public function test_mark_as_sold_defaults_price_to_listed_price_and_date_to_now(): void
    {
        $car = Car::factory()->create(['price' => 12995]);

        $car->markAsSold();

        $fresh = $car->fresh();
        $this->assertSame(12995, $fresh->sold_price);
        $this->assertTrue($fresh->sold_at->diffInSeconds(now(), true) < 5);
    }

    public function test_mark_as_sold_rejects_future_date(): void
    {
        $car = Car::factory()->create();

        try {
            $car->markAsSold(now()->addDay());
            $this->fail('A future date was accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sold_at', $e->errors());
            $this->assertNull($car->fresh()->sold_at);
            $this->assertTrue($car->fresh()->is_active);
        }
    }

    public function test_mark_as_sold_rejects_zero_price(): void
    {
        $car = Car::factory()->create();

        try {
            $car->markAsSold(null, 0);
            $this->fail('A zero price was accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sold_price', $e->errors());
            $this->assertNull($car->fresh()->sold_at);
        }
    }

    public function test_mark_as_sold_without_any_price_is_rejected(): void
    {
        $car = Car::factory()->inactive()->withoutPrice()->create();

        $this->expectException(ValidationException::class);

        $car->markAsSold();
    }

    public function test_mark_as_sold_is_idempotent(): void
    {
        $car = Car::factory()->create(['price' => 10000]);
        $first = now()->subDays(2);
        $car->markAsSold($first, 9500);

        $car->markAsSold(now()->subHour(), 8000);

        $fresh = $car->fresh();
        $this->assertSame($first->timestamp, $fresh->sold_at->timestamp);
        $this->assertSame(9500, $fresh->sold_price);
    }

    public function test_reopen_clears_sale_and_keeps_car_hidden(): void
    {
        $car = Car::factory()->sold()->create();

        $car->reopen();

        $fresh = $car->fresh();
        $this->assertNull($fresh->sold_at);
        $this->assertNull($fresh->sold_price);
        $this->assertFalse($fresh->is_active);
        $this->assertSame('hidden', $fresh->status);
    }

    public function test_reopen_rejects_car_not_sold(): void
    {
        $car = Car::factory()->create();

        $this->expectException(ValidationException::class);

        $car->reopen();
    }

    // -------------------------------------------------------------- publishing

    public function test_cannot_activate_without_price(): void
    {
        $car = Car::factory()->inactive()->withoutPrice()->withPhotos(1)->create();

        $this->expectActivationError($car, 'price');
    }

    public function test_cannot_activate_without_photo(): void
    {
        $car = Car::factory()->inactive()->withoutPhotos()->create();

        $this->expectActivationError($car, 'photo');
    }

    public function test_cannot_activate_sold_car(): void
    {
        $car = Car::factory()->sold()->withPhotos(1)->create();

        $this->expectActivationError($car, 'sold');
    }

    public function test_can_activate_with_price_and_photo(): void
    {
        $car = Car::factory()->inactive()->withPhotos(1)->create();

        $this->assertNull($car->activationBlocker());

        $car->activate();

        $this->assertTrue($car->fresh()->is_active);
    }

    public function test_deactivate_hides_the_car(): void
    {
        $car = Car::factory()->create();

        $car->deactivate();

        $this->assertFalse($car->fresh()->is_active);
        $this->assertSame('hidden', $car->fresh()->status);
    }

    public function test_draft_without_price_can_be_saved_inactive(): void
    {
        $car = Car::factory()->inactive()->withoutPrice()->withoutPhotos()->create();

        $this->assertDatabaseHas('cars', ['id' => $car->id, 'price' => null, 'is_active' => false]);
    }

    public function test_factory_default_car_is_publishable(): void
    {
        $car = Car::factory()->create();

        $this->assertTrue($car->is_active);
        $this->assertNotNull($car->price);
        $this->assertGreaterThanOrEqual(1, $car->photos()->count());
        $this->assertNull($car->activationBlocker());
    }

    private function expectActivationError(Car $car, string $messagePart): void
    {
        try {
            $car->activate();
            $this->fail('The car was activated');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($messagePart, $e->errors()['is_active'][0]);
            $this->assertFalse($car->fresh()->is_active);
        }
    }
}
