<?php

namespace App\Livewire;

use App\Models\BodyType;
use App\Models\Car;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The public lot: search bar, "Shop by make" buttons and the grid of cars.
 *
 * State lives in the query string (?make=jeep&body=suv&max=15000&page=2) so a
 * search can be shared. Everything from the URL is untrusted: invalid values
 * are ignored instead of raising errors, which is why the properties are
 * plain strings.
 */
class Inventory extends Component
{
    /** Slug of the category name, e.g. `jeep`. */
    #[Url(as: 'make')]
    public string $make = '';

    /** Slug of the body type, e.g. `suv`. */
    #[Url(as: 'body')]
    public string $body = '';

    /** Maximum price in dollars (inclusive). */
    #[Url(as: 'max')]
    public string $max = '';

    /** How many "pages" of cars are shown: `page=2` shows the first 2 × 12. */
    #[Url(as: 'page')]
    public string $page = '1';

    public function updatedMake(): void
    {
        $this->page = '1';
    }

    public function updatedBody(): void
    {
        $this->page = '1';
    }

    public function updatedMax(): void
    {
        $this->page = '1';
    }

    public function selectMake(string $slug): void
    {
        $this->make = $slug;
        $this->page = '1';
    }

    public function showMore(): void
    {
        $this->page = (string) ($this->pages() + 1);
    }

    public function render()
    {
        $total = $this->filteredQuery()->count();
        $limit = $this->pages() * config('marios.cars_per_page');

        $cars = $this->filteredQuery()
            ->with(['photos', 'transmission', 'drivetrain'])
            ->latestFirst()
            ->limit($limit)
            ->get();

        return view('livewire.inventory', [
            'cars' => $cars,
            'total' => $total,
            'shown' => $cars->count(),
            'makes' => $this->makes(),
            'activeMake' => $this->activeMake(),
            'bodyTypes' => BodyType::ordered()->get(),
            'totalActive' => Car::active()->count(),
            'whatsappUrl' => Setting::current()->whatsappUrl("Hi Mario, I couldn't find the car I'm looking for. Can you help?"),
        ]);
    }

    /** @return Builder<Car> */
    private function filteredQuery(): Builder
    {
        $query = Car::active();

        if (($category = $this->activeMake()) !== null) {
            $query->where('category_id', $category->id);
        }

        if (($bodyType = $this->activeBody()) !== null) {
            $query->where('body_type_id', $bodyType->id);
        }

        if (($max = $this->maxPrice()) !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    /**
     * Categories with at least one car on the site, in the order set in the
     * admin, with their active-car counts.
     *
     * @return Collection<int, Category>
     */
    private function makes(): Collection
    {
        return once(fn () => Category::withActiveCars()->ordered()->get()
            ->each(fn (Category $category) => $category->setAttribute('slug', Str::slug($category->name))));
    }

    private function activeMake(): ?Category
    {
        if ($this->make === '') {
            return null;
        }

        return $this->makes()->firstWhere('slug', $this->make);
    }

    private function activeBody(): ?BodyType
    {
        return $this->body === '' ? null : BodyType::where('slug', $this->body)->first();
    }

    private function maxPrice(): ?int
    {
        return ctype_digit($this->max) && (int) $this->max > 0 ? (int) $this->max : null;
    }

    /** Number of 12-car blocks to show, at least 1. */
    private function pages(): int
    {
        return ctype_digit($this->page) ? min(max((int) $this->page, 1), 100) : 1;
    }
}
