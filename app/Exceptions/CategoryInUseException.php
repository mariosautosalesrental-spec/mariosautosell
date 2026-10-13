<?php

namespace App\Exceptions;

use DomainException;

class CategoryInUseException extends DomainException
{
    public static function forCategory(string $name, int $cars): self
    {
        return new self(sprintf(
            '"%s" still has %d %s (on the site, hidden or sold), so it cannot be deleted.',
            $name,
            $cars,
            $cars === 1 ? 'car' : 'cars',
        ));
    }
}
