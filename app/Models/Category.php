<?php

namespace App\Models;

use App\Exceptions\CategoryInUseException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sort_order'])]
class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            $cars = $category->cars()->count();

            if ($cars > 0) {
                throw CategoryInUseException::forCategory($category->name, $cars);
            }
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
