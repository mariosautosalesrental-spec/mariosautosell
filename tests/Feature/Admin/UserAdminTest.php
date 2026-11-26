<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->me = User::factory()->create(['name' => 'Mario', 'email' => 'mario@example.com']);
        $this->actingAs($this->me);
    }

    public function test_the_users_page_needs_a_login(): void
    {
        auth()->logout();

        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    public function test_it_lists_the_users(): void
    {
        $other = User::factory()->create(['name' => 'Joao']);

        Livewire::test(ManageUsers::class)
            ->assertCanSeeTableRecords([$this->me, $other])
            ->assertSee('You');
    }

    public function test_inviting_creates_the_user_and_opens_a_link(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('invite', ['name' => 'Joao Antonio', 'email' => 'joao@example.com'])
            ->assertHasNoActionErrors()
            ->assertTableActionVisible('accessLink', User::where('email', 'joao@example.com')->first());

        $this->assertDatabaseHas('users', ['name' => 'Joao Antonio', 'email' => 'joao@example.com']);
    }

    public function test_an_email_already_in_use_cannot_be_invited_again(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction('invite', ['name' => 'Copy', 'email' => 'mario@example.com'])
            ->assertHasActionErrors(['email']);

        $this->assertSame(1, User::count());
    }

    public function test_the_invited_person_sets_a_password_with_the_link(): void
    {
        $invited = User::factory()->create(['email' => 'joao@example.com']);
        $token = Password::broker()->createToken($invited);

        auth()->logout();

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => 'joao@example.com'])
            ->fillForm(['password' => 'my-new-password-1', 'passwordConfirmation' => 'my-new-password-1'])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('my-new-password-1', $invited->refresh()->password));
    }

    public function test_the_access_link_points_to_the_reset_page(): void
    {
        $other = User::factory()->create();

        $link = UserResource::accessLink($other);

        $this->assertStringContainsString('/admin/password-reset/reset', $link);
        $this->assertStringContainsString(rawurlencode($other->email), $link);
    }

    public function test_the_link_lasts_a_week(): void
    {
        $this->assertSame(10080, config('auth.passwords.users.expire'));
    }

    public function test_a_user_can_be_renamed(): void
    {
        $other = User::factory()->create(['name' => 'Old']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($other), ['name' => 'New', 'email' => $other->email])
            ->assertHasNoActionErrors();

        $this->assertSame('New', $other->refresh()->name);
    }

    public function test_you_cannot_remove_yourself(): void
    {
        User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($this->me));
    }

    public function test_another_user_can_be_removed(): void
    {
        $other = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('delete')->table($other));

        $this->assertModelMissing($other);
    }

    public function test_the_login_has_no_forgot_password_link(): void
    {
        auth()->logout();

        $this->get('/admin/login')->assertOk()->assertDontSee('Forgot password');
    }
}
