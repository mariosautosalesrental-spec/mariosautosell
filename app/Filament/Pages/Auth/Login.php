<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Admin';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Sign in to manage your cars and store info.';
    }
}
