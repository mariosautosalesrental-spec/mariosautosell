<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_profile_page_needs_a_login(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_the_user_can_open_their_profile(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Mario']));

        $this->get('/admin/profile')->assertOk()->assertSee('Mario');
    }

    public function test_the_user_can_change_name_and_email(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);
        $this->actingAs($user);

        // Changing the e-mail asks for the current password.
        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Mario Silva', 'email' => 'mario@example.com', 'currentPassword' => 'old-password-1'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Mario Silva', $user->refresh()->name);
        $this->assertSame('mario@example.com', $user->email);
    }

    public function test_the_user_can_change_the_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'new-password-22',
                'passwordConfirmation' => 'new-password-22',
                'currentPassword' => 'old-password-1',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password-22', $user->refresh()->password));
    }

    public function test_a_wrong_current_password_is_refused(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'new-password-22',
                'passwordConfirmation' => 'new-password-22',
                'currentPassword' => 'not-my-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check('old-password-1', $user->refresh()->password));
    }
}
