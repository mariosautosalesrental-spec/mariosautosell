<?php

namespace App\Models;

use App\Exceptions\CategoryInUseException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'sort_order'])]
class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // New categories go to the end of the "Shop by make" buttons.
        static::creating(function (Category $category): void {
            if (! $category->sort_order) {
                $category->sort_order = (int) static::query()->max('sort_order') + 1;
            }
        });

        static::deleting(function (Category $category): void {
            $cars = $category->cars()->count();

            if ($cars > 0) {
                throw CategoryInUseException::forCategory($category->name, $cars);
            }
        });
    }

    public function isFirst(): bool
    {
        return static::query()->ordered()->value('id') === $this->id;
    }

    public function isLast(): bool
    {
        return static::query()->orderByDesc('sort_order')->orderByDesc('id')->value('id') === $this->id;
    }

    /** Swap places with the previous category (a no-op for the first one). */
    public function moveUp(): void
    {
        $this->moveBy(-1);
    }

    /** Swap places with the next category (a no-op for the last one). */
    public function moveDown(): void
    {
        $this->moveBy(1);
    }

    /**
     * Renumbers every category 1..n after the swap so duplicated or gapped
     * sort orders can never make an arrow look dead.
     */
    private function moveBy(int $step): void
    {
        DB::transaction(function () use ($step): void {
            $ids = static::query()->ordered()->pluck('id')->all();
            $from = array_search($this->id, $ids, true);
            $to = $from + $step;

            if ($from === false || ! isset($ids[$to])) {
                return;
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $position => $id) {
                static::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }

            $this->refresh();
        });
    }

    /** @return HasMany<Car, $this> */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Only categories that have at least one car on the site, with the count
     * of those cars in `active_cars_count`.
     */
    public function scopeWithActiveCars(Builder $query): Builder
    {
        return $query
            ->whereHas('cars', fn (Builder $cars) => $cars->active())
            ->withCount(['cars as active_cars_count' => fn (Builder $cars) => $cars->active()]);
    }
}
