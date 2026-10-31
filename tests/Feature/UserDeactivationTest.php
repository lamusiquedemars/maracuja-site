<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UserDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_local_account_with_only_selected_access(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $password = Str::random(32);
        $email = Str::random(16).'@example.test';
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Test', 'email' => $email, 'role' => User::ROLE_EDITOR, 'password' => $password, 'is_active' => true, 'access_scope' => 'selected', 'content_permissions' => ['notices']])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', $email)->firstOrFail();
        $this->assertSame(['notices'], $created->content_permissions);
        $this->assertTrue(Hash::check($password, $created->password));
        $this->assertNotSame($password, $created->password);
    }

    public function test_admin_can_disable_another_account_and_block_the_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($user->fresh()->is_active);
        $this->actingAs($user->fresh())->get('/admin')->assertForbidden();
    }

    public function test_last_administrator_cannot_disable_their_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasFormErrors();

        $this->assertTrue($admin->fresh()->is_active);
    }
}
