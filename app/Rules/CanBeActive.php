<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A car may only be on the site with a price and at least one photo, and a
 * sold car must be reopened first. Used by the car form and the list toggle.
 */
class CanBeActive implements ValidationRule
{
    public function __construct(
        private readonly ?int $price,
        private readonly int $photoCount,
        private readonly bool $isSold = false,
    ) {}

    /**
     * The reason a car cannot be on the site, or null when it can.
     */
    public static function blocker(?int $price, int $photoCount, bool $isSold = false): ?string
    {
        if ($isSold) {
            return 'This car is sold. Mark it as available before putting it back on the site.';
        }

        if ($price === null || $price < 1) {
            return 'Add a price before putting this car on the site.';
        }

        if ($photoCount < 1) {
            return 'Add at least one photo before putting this car on the site.';
        }

        return null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $reason = self::blocker($this->price, $this->photoCount, $this->isSold);

        if ($reason !== null) {
            $fail($reason);
        }
    }
}
