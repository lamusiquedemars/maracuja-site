<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CremonaSsoRedirectTest extends TestCase
{
    public function test_the_site_starts_an_authorization_code_flow_with_pkce(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.issuer', 'https://cremona.example.test');
        config()->set('cremona-portal.client_id', 'site-demo-client');
        config()->set('cremona-portal.logout_secret', 'test-shared-secret');
        config()->set('cremona-portal.redirect_uri', 'https://site.example.test/admin/sso/callback');

        $response = $this->get(route('cremona-sso.redirect'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://cremona.example.test/oauth/authorize?', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=site-demo-client', $response->headers->get('Location'));
        $this->assertStringContainsString('code_challenge_method=S256', $response->headers->get('Location'));
        $response->assertSessionHas('cremona_sso.state');
        $response->assertSessionHas('cremona_sso.verifier');
    }

    public function test_the_workspace_shortcut_redirects_to_the_central_session_ticket(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.issuer', 'https://cremona.example.test');
        config()->set('cremona-portal.client_id', 'site-demo-client');
        config()->set('cremona-portal.logout_secret', 'test-shared-secret');
        Http::fake([
            'https://cremona.example.test/api/v1/site-portal/session-link' => Http::response([
                'entry_url' => 'https://cremona.example.test/site-portal/session?ticket=single-use',
            ]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->withSession(['cremona_portal' => ['navigation' => [['key' => 'workspace']]]])
            ->get(route('cremona-sso.portal', ['item' => 'workspace']));

        $response->assertRedirect('https://cremona.example.test/site-portal/session?ticket=single-use');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://cremona.example.test/api/v1/site-portal/session-link'
            && $request['item'] === 'workspace');
    }

    public function test_the_workspace_route_rejects_anonymous_visitors_without_a_login_redirect(): void
    {
        config()->set('cremona-portal.enabled', true);
        $this->withSession([
            'cremona_portal' => ['navigation' => [['key' => 'workspace']]],
        ])->get(route('cremona-sso.portal', ['item' => 'workspace']))
            ->assertUnauthorized();
    }

    public function test_the_site_does_not_expose_the_sso_flow_before_it_is_configured(): void
    {
        config()->set('cremona-portal.enabled', false);

        $this->get(route('cremona-sso.redirect'))->assertNotFound();
    }

    public function test_an_authenticated_site_user_starts_a_single_logout_with_a_short_session_state(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.issuer', 'https://cremona.example.test');
        config()->set('cremona-portal.client_id', 'site-demo-client');
        config()->set('cremona-portal.logout_secret', 'test-shared-secret');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('cremona-sso.logout'));

        $response->assertOk()
            ->assertSee('https://cremona.example.test/site-portal/logout', false)
            ->assertSee('name="client_id" value="site-demo-client"', false)
            ->assertSee('name="signature"', false);
        $response->assertSessionHas('cremona_sso_logout.state');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_the_site_only_closes_its_session_after_the_matching_central_logout_returns(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.client_id', 'site-demo-client');

        $user = User::factory()->create();
        $this->actingAs($user)->withSession([
            'cremona_sso_logout' => ['state' => 'matching-state', 'expires_at' => now()->addMinute()->getTimestamp()],
        ])->get(route('cremona-sso.logout.complete', ['state' => 'matching-state']))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_the_site_refuses_a_central_logout_return_with_an_invalid_state(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.client_id', 'site-demo-client');

        $this->actingAs(User::factory()->create())->withSession([
            'cremona_sso_logout' => ['state' => 'expected-state', 'expires_at' => now()->addMinute()->getTimestamp()],
        ])->get(route('cremona-sso.logout.complete', ['state' => 'other-state']))
            ->assertUnauthorized();
    }
}
