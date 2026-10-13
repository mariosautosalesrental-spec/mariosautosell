<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Cars\Pages\CreateCar;
use App\Filament\Resources\Cars\Pages\EditCar;
use App\Models\BodyType;
use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\Category;
use App\Models\Drivetrain;
use App\Models\Setting;
use App\Models\Transmission;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\MakesImages;
use Tests\TestCase;

class CarFormTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    private Category $category;

    private BodyType $body;

    private Transmission $transmission;

    private Drivetrain $drivetrain;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());
        Setting::factory()->create();

        $this->category = Category::factory()->create(['name' => 'Jeep']);
        $this->body = BodyType::factory()->create(['name' => 'SUV', 'slug' => 'suv']);
        $this->transmission = Transmission::factory()->create(['name' => 'Automatic', 'slug' => 'automatic']);
        $this->drivetrain = Drivetrain::factory()->create(['name' => '4WD', 'slug' => '4wd']);
    }

    private function photo(string $name = 'front.jpg', int $width = 1600, int $height = 1200): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->jpegBytes($width, $height));
    }

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'photos' => [$this->photo()],
            'category_id' => $this->category->id,
            'title' => '2019 Jeep Compass',
            'trim' => 'Limited 4x4',
            'price' => 18900,
            'mileage' => 58200,
            'body_type_id' => $this->body->id,
            'transmission_id' => $this->transmission->id,
            'drivetrain_id' => $this->drivetrain->id,
            'color' => 'Gray',
            'description' => 'One owner.',
            'is_active' => true,
            'price_drop' => false,
        ], $overrides);
    }

    // ----------------------------------------------------------------- create

    public function test_create_page_has_the_expected_fields(): void
    {
        $this->get('/admin/cars/create')
            ->assertOk()
            ->assertSee('Photos')
            ->assertSee('Category')
            ->assertSee('Version')
            ->assertSee('Price')
            ->assertSee('Mileage')
            ->assertSee('Body type')
            ->assertSee('Transmission')
            ->assertSee('Drivetrain')
            ->assertSee('Color')
            ->assertSee('Engine (optional)')
            ->assertSee('Description')
            ->assertSee('Show on website')
            ->assertSee('Price drop')
            ->assertSee('Preview on the site');
    }

    public function test_show_on_website_is_on_by_default(): void
    {
        Livewire::test(CreateCar::class)->assertFormSet(['is_active' => true, 'price_drop' => false]);
    }

    public function test_a_valid_car_is_created_and_appears_on_the_site(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid())
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/cars');

        $car = Car::firstOrFail();
        $this->assertSame('2019 Jeep Compass', $car->title);
        $this->assertTrue($car->is_active);
        $this->assertSame(18900, $car->price);
        $this->assertSame(58200, $car->mileage);
        $this->assertSame('2019-jeep-compass-limited-4x4-'.$car->id, $car->slug);

        $this->get('/')->assertSee('2019 Jeep Compass');
        $this->get('/cars/'.$car->slug)->assertOk()->assertSee('$18,900');
    }

    public function test_photos_are_saved_in_the_car_directory_with_conversions(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['photos' => [$this->photo('a.jpg', 2400, 1800), $this->photo('b.jpg', 2400, 1800)]]))
            ->call('create')
            ->assertHasNoFormErrors();

        $car = Car::firstOrFail();
        $photos = $car->photos;

        $this->assertCount(2, $photos);
        foreach ($photos as $photo) {
            $this->assertStringStartsWith("cars/{$car->id}/", $photo->file_path);
            Storage::disk('public')->assertExists($photo->file_path);
            Storage::disk('public')->assertExists($photo->conversionPath('thumb'));
            Storage::disk('public')->assertExists($photo->conversionPath('large'));
        }
        $this->assertSame([], Storage::disk('public')->allFiles('cars/uploads'));
    }

    public function test_create_requires_the_mandatory_fields(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm([
                'category_id' => null, 'title' => '', 'body_type_id' => null, 'transmission_id' => null, 'drivetrain_id' => null,
                'is_active' => false,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'category_id' => 'required', 'title' => 'required', 'body_type_id' => 'required',
                'transmission_id' => 'required', 'drivetrain_id' => 'required',
            ]);

        $this->assertSame(0, Car::count());
    }

    public function test_price_and_mileage_must_be_whole_numbers_in_range(): void
    {
        foreach ([['price' => 18900.5], ['price' => 0], ['price' => -5], ['mileage' => -1], ['mileage' => 12.5], ['price' => 'abc']] as $bad) {
            Livewire::test(CreateCar::class)
                ->fillForm($this->valid($bad))
                ->call('create')
                ->assertHasFormErrors(array_keys($bad));
        }

        $this->assertSame(0, Car::count());
    }

    public function test_mileage_zero_is_accepted(): void
    {
        Livewire::test(CreateCar::class)->fillForm($this->valid(['mileage' => 0]))->call('create')->assertHasNoFormErrors();

        $this->assertSame(0, Car::first()->mileage);
    }

    public function test_optional_fields_can_be_left_empty(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['trim' => '', 'mileage' => null, 'color' => '', 'engine' => '', 'description' => '']))
            ->call('create')
            ->assertHasNoFormErrors();

        $car = Car::first();
        $this->assertNull($car->trim);
        $this->assertNull($car->mileage);
        $this->assertNull($car->engine);
        $this->assertSame('2019-jeep-compass-'.$car->id, $car->slug);
    }

    public function test_lookup_selects_only_accept_existing_rows(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['body_type_id' => 9999, 'category_id' => 9999]))
            ->call('create')
            ->assertHasFormErrors(['body_type_id', 'category_id']);
    }

    // ------------------------------------------------------ publishing rules

    public function test_active_car_requires_price_and_photo(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['price' => null]))
            ->call('create')
            ->assertHasFormErrors(['is_active']);

        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['photos' => []]))
            ->call('create')
            ->assertHasFormErrors(['is_active']);

        $this->assertSame(0, Car::count());
    }

    public function test_active_car_error_messages_explain_the_reason(): void
    {
        $noPrice = Livewire::test(CreateCar::class)->fillForm($this->valid(['price' => null]))->call('create');
        $noPhoto = Livewire::test(CreateCar::class)->fillForm($this->valid(['photos' => []]))->call('create');

        $this->assertSame(['Add a price before putting this car on the site.'], $noPrice->errors()->get('data.is_active'));
        $this->assertSame(['Add at least one photo before putting this car on the site.'], $noPhoto->errors()->get('data.is_active'));
    }

    public function test_inactive_draft_saves_without_price_or_photo(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['is_active' => false, 'price' => null, 'photos' => []]))
            ->call('create')
            ->assertHasNoFormErrors();

        $car = Car::firstOrFail();
        $this->assertFalse($car->is_active);
        $this->assertNull($car->price);
        $this->assertSame(0, $car->photos()->count());
        $this->get('/')->assertDontSee('2019 Jeep Compass');
        $this->get('/cars/'.$car->slug)->assertNotFound();
    }

    public function test_price_drop_flag_is_saved_and_shown_on_the_site(): void
    {
        Livewire::test(CreateCar::class)->fillForm($this->valid(['price_drop' => true]))->call('create');

        $this->assertTrue(Car::first()->price_drop);
        $this->get('/')->assertSee('Price drop');
    }

    public function test_mass_assignment_cannot_override_the_slug_or_sale_fields(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid())
            ->call('create');

        $car = Car::first();
        $this->assertNull($car->sold_at);
        $this->assertNull($car->sold_price);
    }

    // -------------------------------------------------------------------- edit

    private function existingCar(array $attributes = []): Car
    {
        $car = Car::factory()->create(array_merge([
            'category_id' => $this->category->id,
            'body_type_id' => $this->body->id,
            'transmission_id' => $this->transmission->id,
            'drivetrain_id' => $this->drivetrain->id,
            'title' => '2018 Jeep Compass', 'price' => 15000,
        ], $attributes));

        // real files for the photo created by the factory
        foreach ($car->photos as $photo) {
            Storage::disk('public')->put($photo->file_path, $this->jpegBytes(1200, 900));
        }

        return $car;
    }

    public function test_edit_form_loads_the_car_and_its_photos(): void
    {
        $car = $this->existingCar(['color' => 'Gray']);

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->assertFormSet(fn (array $state) => $state['title'] === '2018 Jeep Compass'
                && $state['price'] === 15000
                && $state['color'] === 'Gray'
                && array_values($state['photos']) === $car->photos->pluck('file_path')->all());
    }

    public function test_edit_updates_public_page(): void
    {
        $car = $this->existingCar();
        $slug = $car->fresh()->slug;

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['price' => 13500, 'description' => 'Now with new tires.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get("/cars/{$slug}")->assertSee('$13,500')->assertSee('Now with new tires.')->assertDontSee('$15,000');
    }

    public function test_edit_keeps_validation_rules(): void
    {
        $car = $this->existingCar();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['title' => '', 'price' => 0])
            ->call('save')
            ->assertHasFormErrors(['title' => 'required', 'price']);
    }

    public function test_changing_the_name_updates_the_slug(): void
    {
        $car = $this->existingCar(['trim' => null]);

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['title' => '2020 Jeep Renegade'])
            ->call('save');

        $this->assertSame('2020-jeep-renegade-'.$car->id, $car->fresh()->slug);
    }

    public function test_edit_can_add_a_photo_and_remove_the_old_one(): void
    {
        $car = $this->existingCar();
        $old = $car->photos->first();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['photos' => [$this->photo('new.jpg')]])
            ->call('save')
            ->assertHasNoFormErrors();

        $paths = $car->fresh()->photos->pluck('file_path')->all();
        $this->assertCount(1, $paths);
        $this->assertNotContains($old->file_path, $paths);
        Storage::disk('public')->assertMissing($old->file_path);
        Storage::disk('public')->assertExists($paths[0]);
    }

    public function test_removing_the_last_photo_of_an_active_car_is_blocked(): void
    {
        $car = $this->existingCar();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['photos' => []])
            ->call('save')
            ->assertHasFormErrors(['is_active']);

        $this->assertSame(1, $car->photos()->count());
        $this->assertTrue($car->fresh()->is_active);
    }

    public function test_removing_the_last_photo_is_fine_when_the_car_is_turned_off(): void
    {
        $car = $this->existingCar();
        $path = $car->photos->first()->file_path;

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['photos' => [], 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $car->photos()->count());
        $this->assertFalse($car->fresh()->is_active);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_sold_car_cannot_be_turned_on_from_the_form(): void
    {
        $car = $this->existingCar();
        $car->markAsSold();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->assertFormFieldDisabled('is_active')
            ->fillForm(['price' => 14000])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $car->fresh();
        $this->assertFalse($fresh->is_active);
        $this->assertTrue($fresh->isSold());
        $this->assertSame(14000, $fresh->price);
    }

    public function test_a_photo_path_from_another_car_cannot_be_attached(): void
    {
        $mine = $this->existingCar();
        $other = $this->existingCar(['title' => '2012 Other Car']);
        $otherPath = $other->photos->first()->file_path;

        Livewire::test(EditCar::class, ['record' => $mine->getKey()])
            ->fillForm(['photos' => [$otherPath]])
            ->call('save');

        $this->assertSame(1, $other->photos()->count());
        Storage::disk('public')->assertExists($otherPath);
        $this->assertNotContains($otherPath, $mine->fresh()->photos->pluck('file_path')->all());
    }

    public function test_deleting_from_the_edit_page_removes_the_car_and_its_files(): void
    {
        $car = $this->existingCar();
        $path = $car->photos->first()->file_path;

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, CarPhoto::count());
    }

    // ------------------------------------------------------- upload rules

    public function test_non_image_files_are_rejected(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['photos' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')]]))
            ->call('create')
            ->assertHasFormErrors(['photos']);

        $this->assertSame(0, Car::count());
    }

    public function test_photos_over_the_size_limit_are_rejected(): void
    {
        config(['marios.max_photo_kb' => 10240]);

        Livewire::test(CreateCar::class)
            ->fillForm($this->valid(['photos' => [UploadedFile::fake()->create('huge.jpg', 10241, 'image/jpeg')]]))
            ->call('create')
            ->assertHasFormErrors(['photos']);
    }

    public function test_the_category_can_be_created_from_the_car_form(): void
    {
        $this->assertSame(1, Category::count());

        $component = Livewire::test(CreateCar::class);
        $component->fillForm($this->valid(['category_id' => null]))
            ->callAction(TestAction::make('createOption')->schemaComponent('category_id'), ['name' => 'Toyota'])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Toyota']);
        $this->assertSame($this->category->sort_order + 1, Category::where('name', 'Toyota')->value('sort_order')); // goes to the end
    }

    // ---------------------------------------------------------------- preview

    public function test_preview_shows_the_typed_values(): void
    {
        $component = Livewire::test(CreateCar::class)
            ->fillForm(['title' => '2016 Fiat Toro', 'trim' => 'Freedom', 'price' => 21995, 'price_drop' => true]);

        $component->assertSee('2016 Fiat Toro')->assertSee('Freedom')->assertSee('$21,995')->assertSee('Price drop');
    }

    public function test_preview_has_a_placeholder_without_photos_and_shows_the_cover_on_edit(): void
    {
        Livewire::test(CreateCar::class)->assertSee('No photo yet');

        $car = $this->existingCar();
        $cover = Storage::disk('public')->url($car->photos->first()->file_path);

        Livewire::test(EditCar::class, ['record' => $car->getKey()])->assertSeeHtml($cover);
    }
}
