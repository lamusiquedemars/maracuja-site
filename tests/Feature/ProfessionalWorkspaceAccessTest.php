<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maracuja\SiteCore\ProfessionalWorkspace\Models\ProfessionalWorkspaceSetting;
use Maracuja\SiteCore\ProfessionalWorkspace\Support\ProfessionalWorkspace;
use Tests\TestCase;

class ProfessionalWorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_professional_workspace_is_a_single_direct_admin_entry_when_cremona_grants_access(): void
    {
        config()->set('maracuja-professional-workspace.enabled', true);
        ProfessionalWorkspaceSetting::current()->update([
            'is_enabled' => true,
            'label' => 'Accès professionnel',
        ]);

        session()->put('cremona_portal.navigation', [[
            'key' => 'workspace',
            'label' => 'Espace professionnel',
            'url' => 'https://cremona.example.test/dashboard/demo',
        ]]);

        $this->assertTrue(ProfessionalWorkspace::isAvailable());
        $this->assertSame('Accès professionnel', ProfessionalWorkspace::label());
        $this->assertSame(route('cremona-sso.portal', ['item' => 'workspace']), ProfessionalWorkspace::url());

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

        $this->actingAs($admin)
            ->withSession(['cremona_portal.navigation' => session('cremona_portal.navigation')])
            ->get('/admin')
            ->assertOk()
            ->assertSee('Accès professionnel');
    }
}
