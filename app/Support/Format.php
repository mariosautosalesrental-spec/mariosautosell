<?php

namespace App\Support;

/**
 * Display formatting shared by the site and the admin (USD without cents, miles).
 */
class Format
{
    public static function price(?int $dollars): string
    {
        return $dollars === null ? '' : '$'.number_format($dollars);
    }

    public static function miles(?int $miles): string
    {
        return $miles === null ? '' : number_format($miles).' mi';
    }
}
