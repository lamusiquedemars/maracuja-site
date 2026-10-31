<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\DeleteSiteUser;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_delete_another_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false]);
        $this->actingAs($admin->fresh());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListUsers::class)->callTableAction('delete', $user)->assertHasNoActionErrors();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_administrator_can_delete_from_the_edit_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false]);
        $this->actingAs($admin->fresh());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->callAction('delete')
            ->assertHasNoActionErrors();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $this->actingAs($admin->fresh());
        $this->assertFalse(DeleteSiteUser::allowed($admin->fresh(), $admin));
        $this->expectException(AuthorizationException::class);
        DeleteSiteUser::delete($admin);
    }

    public function test_non_administrator_cannot_delete_other_accounts(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_admin' => false]);
        $target = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false]);
        $this->actingAs($user);
        $this->assertFalse(DeleteSiteUser::allowed($user, $target));
        $this->expectException(AuthorizationException::class);
        DeleteSiteUser::delete($target);
    }
}
