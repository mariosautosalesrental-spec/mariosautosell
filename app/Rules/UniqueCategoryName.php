<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Category names are unique ignoring case and surrounding spaces, so "jeep"
 * and "Jeep" are the same make.
 */
class UniqueCategoryName implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);

        $exists = Category::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->when($this->ignoreId, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->exists();

        if ($exists) {
            $fail("\"{$name}\" already exists.");
        }
    }
}
