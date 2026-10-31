<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_change_their_own_password(): void
    {
        $oldPassword = Str::random(32);
        $newPassword = Str::random(32);
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => false, 'password' => $oldPassword]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->get(route('filament.admin.auth.profile'))->assertOk();

        Livewire::test(EditProfile::class)
            ->fillForm(['password' => $newPassword, 'passwordConfirmation' => $newPassword, 'currentPassword' => $oldPassword])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertFalse(Hash::check($oldPassword, $user->fresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $oldPassword = Str::random(32);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false, 'password' => $oldPassword]);
        $newPassword = Str::random(32);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(EditProfile::class)
            ->fillForm(['password' => $newPassword, 'passwordConfirmation' => $newPassword, 'currentPassword' => Str::random(32)])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertTrue(Hash::check($oldPassword, $user->fresh()->password));
    }
}
