<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Maracuja\SiteCore\Contact\Models\Inquiry;
use Tests\TestCase;

class ContentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_functions_control_navigation_and_server_access(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_admin' => false,
            'content_permissions' => ['notices'],
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('create', SiteNotice::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', NewsPost::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', User::class));
        $this->actingAs($user)->get('/admin/news-posts')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/site-notices')->assertOk();
        $this->actingAs($user)->get('/admin')->assertDontSee('Actualités');
    }

    public function test_permission_does_not_activate_a_disabled_module(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false, 'content_permissions' => ['news']]);
        config()->set('maracuja.modules.news', false);

        $this->actingAs($user)->get('/admin/news-posts')->assertForbidden();
        $this->assertSame(['news'], $user->fresh()->content_permissions);
    }

    public function test_legacy_boolean_administrator_keeps_access_even_with_viewer_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => true, 'content_permissions' => []]);
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Inquiry::class));
        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_reader_cannot_open_screens_with_unprotected_write_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => false, 'content_permissions' => ['inquiries']]);
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Inquiry::class));
    }

    public function test_legacy_null_permissions_keep_editor_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false, 'content_permissions' => null]);

        $this->assertTrue(Gate::forUser($user)->allows('create', NewsPost::class));
    }

    public function test_site_manager_can_use_granted_functions_but_not_manage_accounts(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_admin' => false, 'content_permissions' => ['notices']]);

        $this->assertTrue(Gate::forUser($user)->allows('create', SiteNotice::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', NewsPost::class));
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_administrator_can_choose_functions_for_an_existing_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $user = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false, 'content_permissions' => null]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['access_scope' => 'selected', 'content_permissions' => ['notices']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['notices'], $user->fresh()->content_permissions);
        $this->assertFalse(Gate::forUser($user->fresh())->allows('viewAny', NewsPost::class));
    }
}
