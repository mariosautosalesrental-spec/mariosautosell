<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
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

    /** The store sends no e-mail, so there is no "Forgot password?" link: an admin gives a new link from the Users page. */
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->hint(null);
    }
}
