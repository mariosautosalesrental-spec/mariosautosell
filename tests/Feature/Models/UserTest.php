<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_user_can_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_password_is_hashed_and_hidden(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
