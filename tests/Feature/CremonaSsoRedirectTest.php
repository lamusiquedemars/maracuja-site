<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CremonaSsoRedirectTest extends TestCase
{
    public function test_the_site_starts_an_authorization_code_flow_with_pkce(): void
    {
        config()->set('cremona-portal.enabled', true);
        config()->set('cremona-portal.issuer', 'https://cremona.example.test');
        config()->set('cremona-portal.client_id', 'site-demo-client');
        config()->set('cremona-portal.redirect_uri', 'https://site.example.test/admin/sso/callback');

        $response = $this->get(route('cremona-sso.redirect'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://cremona.example.test/oauth/authorize?', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=site-demo-client', $response->headers->get('Location'));
        $this->assertStringContainsString('code_challenge_method=S256', $response->headers->get('Location'));
        $response->assertSessionHas('cremona_sso.state');
        $response->assertSessionHas('cremona_sso.verifier');
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

        $response = $this->actingAs(User::factory()->create())
            ->post(route('cremona-sso.logout'));

        $response->assertOk()
            ->assertSee('https://cremona.example.test/site-portal/logout', false)
            ->assertSee('name="client_id" value="site-demo-client"', false);
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
            ->assertRedirect(route('filament.admin.auth.login'));

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
