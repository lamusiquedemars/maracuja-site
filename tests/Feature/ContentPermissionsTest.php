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
use Maracuja\SiteCore\Catalogue\Filament\Pages\CatalogueHome;
use Maracuja\SiteCore\Catalogue\Filament\Resources\CatalogueCategories\CatalogueCategoryResource;
use Maracuja\SiteCore\Catalogue\Filament\Resources\CatalogueCollections\CatalogueCollectionResource;
use Maracuja\SiteCore\Catalogue\Filament\Resources\CatalogueItems\CatalogueItemResource;
use Maracuja\SiteCore\Catalogue\Models\CatalogueCollection;
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

    public function test_catalogue_administration_requires_an_enabled_module_and_editorial_access(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_admin' => false,
            'content_permissions' => ['catalogue'],
        ]);
        $reader = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_admin' => false,
            'content_permissions' => ['catalogue'],
        ]);
        $homeUrl = CatalogueHome::getUrl(panel: 'admin');
        $resourceUrls = [
            CatalogueItemResource::getUrl(panel: 'admin'),
            CatalogueCategoryResource::getUrl(panel: 'admin'),
            CatalogueCollectionResource::getUrl(panel: 'admin'),
        ];

        $this->actingAs($manager);
        foreach ([$homeUrl, ...$resourceUrls] as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs($reader);
        foreach ([$homeUrl, ...$resourceUrls] as $url) {
            $this->get($url)->assertForbidden();
        }

        config()->set('maracuja.modules.catalogue', false);
        $this->actingAs($manager);
        foreach ([$homeUrl, ...$resourceUrls] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_catalogue_home_previews_each_section_and_links_back_from_management(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_admin' => false,
            'content_permissions' => ['catalogue'],
        ]);
        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CatalogueHome::class)
            ->assertSet('activeTab', 'items')
            ->assertSee('Aucune fiche dans le catalogue.')
            ->call('selectTab', 'categories')
            ->assertSee('Aucune catégorie dans le catalogue.')
            ->call('selectTab', 'catalogues')
            ->assertSee('Créez un catalogue dans les réglages');

        CatalogueCollection::query()->create(['name' => 'Archets de l’atelier']);
        $instruments = CatalogueCollection::query()->create([
            'name' => 'Instruments de l’atelier',
            'plural_label' => 'Nos instruments',
            'source' => 'cremona',
            'is_published' => true,
        ]);
        $category = $instruments->categories()->create(['name' => 'Violons']);
        $published = $instruments->items()->create(['title' => 'Violon Horizon', 'publication_status' => 'published']);
        $draft = $instruments->items()->create(['title' => 'Violon de travail', 'publication_status' => 'draft']);
        $category->items()->attach([$published->getKey(), $draft->getKey()]);

        $itemsUrl = CatalogueItemResource::getUrl(panel: 'admin');
        $categoriesUrl = CatalogueCategoryResource::getUrl(panel: 'admin');
        $settingsUrl = CatalogueCollectionResource::getUrl(panel: 'admin');

        Livewire::test(CatalogueHome::class)
            ->assertSet('activeTab', 'items')
            ->assertSee('Violon Horizon')
            ->assertSee('Violon de travail')
            ->assertSee('Publié')
            ->assertSee('Brouillon')
            ->assertSee('Gérer les fiches')
            ->assertSee($itemsUrl)
            ->call('selectTab', 'categories')
            ->assertSee('Violons')
            ->assertSee('2 fiches')
            ->assertSee('Gérer les catégories')
            ->assertSee($categoriesUrl)
            ->assertDontSee('Violon Horizon')
            ->call('selectTab', 'catalogues')
            ->assertSee('Nos instruments')
            ->assertSee('Cremona')
            ->assertSee('Ouvrir les réglages')
            ->assertSee($settingsUrl);

        $homeUrl = CatalogueHome::getUrl(panel: 'admin');
        foreach ([$itemsUrl => 'Fiches', $categoriesUrl => 'Catégories', $settingsUrl => 'Réglages du catalogue'] as $url => $label) {
            $this->get($url)
                ->assertOk()
                ->assertSeeInOrder(['fi-breadcrumbs', 'href="'.$homeUrl.'"', $label], false);
        }
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
