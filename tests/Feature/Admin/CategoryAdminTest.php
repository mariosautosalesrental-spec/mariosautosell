<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Car;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(User::factory()->create());
    }

    private function named(string $name, int $order): Category
    {
        return Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    // ------------------------------------------------------ list, create, rename

    public function test_list_shows_name_and_car_count_in_sort_order(): void
    {
        $jeep = $this->named('Jeep', 2);
        $fiat = $this->named('Fiat', 1);
        Car::factory()->count(2)->for($jeep)->create();
        Car::factory()->for($jeep)->inactive()->create();
        Car::factory()->for($jeep)->sold()->create();

        Livewire::test(ManageCategories::class)
            ->assertCanSeeTableRecords([$fiat, $jeep], inOrder: true)
            ->assertTableColumnStateSet('cars_count', 4, $jeep)
            ->assertTableColumnStateSet('cars_count', 0, $fiat);
    }

    public function test_page_has_the_expected_copy(): void
    {
        $this->get('/admin/categories')
            ->assertOk()
            ->assertSee('Add category')
            ->assertSee('Shop by make');
    }

    public function test_new_category_gets_last_sort_order(): void
    {
        $this->named('Fiat', 1);
        $this->named('Jeep', 2);

        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => 'Toyota'])
            ->assertHasNoFormErrors();

        $this->assertSame(3, Category::where('name', 'Toyota')->value('sort_order'));
    }

    public function test_first_category_gets_sort_order_one(): void
    {
        Livewire::test(ManageCategories::class)->callAction(CreateAction::class, ['name' => 'Toyota']);

        $this->assertSame(1, Category::first()->sort_order);
    }

    public function test_category_name_is_required_and_unique(): void
    {
        $this->named('Jeep', 1);

        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => ''])
            ->assertHasFormErrors(['name' => 'required']);

        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => 'Jeep'])
            ->assertHasFormErrors(['name']);

        $this->assertSame(1, Category::count());
    }

    public function test_category_name_uniqueness_ignores_case_and_spaces(): void
    {
        $this->named('Jeep', 1);

        Livewire::test(ManageCategories::class)
            ->callAction(CreateAction::class, ['name' => '  jeep '])
            ->assertHasFormErrors(['name']);
    }

    public function test_renaming_category_updates_public_label(): void
    {
        $category = $this->named('Jepp', 1);
        $car = Car::factory()->for($category)->create();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('edit')->table($category), ['name' => 'Jeep'])
            ->assertHasNoFormErrors();

        $this->assertSame('Jeep', $category->fresh()->name);
        $this->assertSame('Jeep', $car->fresh()->category->name);
        $this->get('/')->assertSee('Jeep');
    }

    public function test_renaming_to_its_own_name_is_allowed(): void
    {
        $category = $this->named('Jeep', 1);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('edit')->table($category), ['name' => 'Jeep'])
            ->assertHasNoFormErrors();
    }

    public function test_renaming_to_another_existing_name_is_rejected(): void
    {
        $this->named('Jeep', 1);
        $fiat = $this->named('Fiat', 2);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('edit')->table($fiat), ['name' => 'jeep'])
            ->assertHasFormErrors(['name']);

        $this->assertSame('Fiat', $fiat->fresh()->name);
    }

    // ----------------------------------------------------------------- reorder

    public function test_move_up_swaps_sort_order(): void
    {
        $a = $this->named('A', 1);
        $b = $this->named('B', 2);
        $c = $this->named('C', 3);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('moveUp')->table($c));

        $this->assertSame(['A', 'C', 'B'], Category::ordered()->pluck('name')->all());
    }

    public function test_move_down_swaps_sort_order(): void
    {
        $a = $this->named('A', 1);
        $this->named('B', 2);
        $this->named('C', 3);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make('moveDown')->table($a));

        $this->assertSame(['B', 'A', 'C'], Category::ordered()->pluck('name')->all());
    }

    public function test_first_category_cannot_move_up_and_last_cannot_move_down(): void
    {
        $first = $this->named('A', 1);
        $this->named('B', 2);
        $last = $this->named('C', 3);

        Livewire::test(ManageCategories::class)
            ->assertActionDisabled(TestAction::make('moveUp')->table($first))
            ->assertActionDisabled(TestAction::make('moveDown')->table($last))
            ->assertActionEnabled(TestAction::make('moveDown')->table($first))
            ->assertActionEnabled(TestAction::make('moveUp')->table($last));
    }

    public function test_site_buttons_follow_admin_order(): void
    {
        $a = $this->named('Alpha', 1);
        $b = $this->named('Bravo', 2);
        Car::factory()->for($a)->create();
        Car::factory()->for($b)->create();

        $this->get('/')->assertSeeInOrder(['Alpha', 'Bravo']);

        Livewire::test(ManageCategories::class)->callAction(TestAction::make('moveDown')->table($a));

        $this->get('/')->assertSeeInOrder(['Bravo', 'Alpha']);
    }

    // ------------------------------------------------------------------ delete

    public function test_category_with_cars_cannot_be_deleted_in_admin(): void
    {
        $category = $this->named('Jeep', 1);
        Car::factory()->for($category)->create();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertNotified('This category still has cars');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_sold_car_still_blocks_category_deletion(): void
    {
        $category = $this->named('Jeep', 1);
        Car::factory()->for($category)->sold()->create();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertNotified('This category still has cars');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_hidden_car_still_blocks_category_deletion(): void
    {
        $category = $this->named('Jeep', 1);
        Car::factory()->for($category)->inactive()->create();

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertNotified('This category still has cars');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_empty_category_is_deleted_after_confirmation(): void
    {
        $category = $this->named('Jeep', 1);

        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertNotified('Category deleted');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_delete_asks_for_confirmation(): void
    {
        $category = $this->named('Jeep', 1);

        $component = Livewire::test(ManageCategories::class)
            ->mountAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertActionMounted(TestAction::make(DeleteAction::class)->table($category));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
