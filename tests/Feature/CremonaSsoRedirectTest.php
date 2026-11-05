<?php

namespace Tests\Feature;

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
}
