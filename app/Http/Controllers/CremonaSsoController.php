<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CremonaPortal;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CremonaSsoController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), Response::HTTP_NOT_FOUND);

        $state = Str::random(64);
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $request->session()->put('cremona_sso', ['state' => $state, 'verifier' => $verifier]);

        return redirect()->away(config('cremona-portal.issuer').'/oauth/authorize?'.http_build_query([
            'client_id' => config('cremona-portal.client_id'),
            'redirect_uri' => config('cremona-portal.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'site:access',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), Response::HTTP_NOT_FOUND);

        $pending = $request->session()->pull('cremona_sso');
        if (! is_array($pending) || ! is_string($pending['state'] ?? null)) {
            Log::warning('SSO Cremona refusé au retour du site.', ['reason' => 'missing_pending_session']);
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! hash_equals($pending['state'], (string) $request->query('state'))) {
            Log::warning('SSO Cremona refusé au retour du site.', ['reason' => 'state_mismatch']);
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if (! $request->filled('code')) {
            Log::warning('SSO Cremona refusé au retour du site.', ['reason' => 'missing_authorization_code']);
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $token = Http::asForm()->acceptJson()->timeout(10)->post(config('cremona-portal.issuer').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('cremona-portal.client_id'),
            'redirect_uri' => config('cremona-portal.redirect_uri'),
            'code' => $request->string('code')->toString(),
            'code_verifier' => $pending['verifier'],
        ]);

        if (! $token->successful() || ! is_string($token->json('access_token'))) {
            Log::warning('SSO Cremona : échange du code refusé.', [
                'status' => $token->status(),
                'oauth_error' => $token->json('error'),
            ]);
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $profile = Http::withToken($token->json('access_token'))
            ->acceptJson()->timeout(10)
            ->get(config('cremona-portal.issuer').'/api/v1/site-portal/profile');

        if (! $profile->successful()) {
            Log::warning('SSO Cremona : profil de portail refusé.', ['status' => $profile->status()]);
            abort(Response::HTTP_FORBIDDEN);
        }

        $identity = $profile->json();
        abort_unless(
            is_array($identity)
            && is_string($identity['subject'] ?? null)
            && is_string($identity['email'] ?? null)
            && is_string($identity['name'] ?? null)
            && is_array($identity['navigation'] ?? null),
            Response::HTTP_FORBIDDEN,
        );

        $user = User::query()->firstOrNew(['cremona_subject' => $identity['subject']]);

        if (! $user->exists) {
            // We never silently attach an existing local account by e-mail.
            abort_if(User::query()->where('email', $identity['email'])->exists(), Response::HTTP_CONFLICT);

            $role = config('cremona-portal.default_role', User::ROLE_ADMIN);
            $user->forceFill([
                'name' => $identity['name'],
                'email' => $identity['email'],
                'password' => Hash::make(Str::random(64)),
                'role' => $role,
                'is_admin' => $role === User::ROLE_ADMIN,
                'is_active' => true,
                'content_permissions' => null,
            ]);
        } else {
            $user->forceFill(['name' => $identity['name'], 'email' => $identity['email']]);
        }

        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('cremona_portal', [
            'organization' => $identity['organization'] ?? null,
            'navigation' => $identity['navigation'],
        ]);

        return redirect()->to(Filament::getUrl());
    }

    public function portal(string $item): RedirectResponse
    {
        abort_unless(config('cremona-portal.enabled'), Response::HTTP_NOT_FOUND);

        $url = CremonaPortal::url($item);
        abort_unless($url !== null, Response::HTTP_NOT_FOUND);

        return redirect()->away($url);
    }

    /**
     * Starts a single, browser-mediated logout. The site session intentionally
     * remains alive until Cremona has closed its own session and returned with
     * the state created below.
     */
    public function logout(Request $request): HttpResponse
    {
        abort_unless($this->enabled(), Response::HTTP_NOT_FOUND);

        $state = Str::random(96);
        $clientId = (string) config('cremona-portal.client_id');

        $request->session()->put('cremona_sso_logout', [
            'state' => $state,
            'expires_at' => now()->addMinutes(5)->getTimestamp(),
        ]);

        return response()
            ->view('sso.logout', [
                'endpoint' => config('cremona-portal.issuer').'/site-portal/logout',
                'clientId' => $clientId,
                'state' => $state,
                'signature' => hash_hmac('sha256', $clientId.'|'.$state, (string) config('cremona-portal.logout_secret')),
            ])
            // Le formulaire contient une preuve HMAC liée à l'état éphémère :
            // aucun referer n'est nécessaire ni souhaitable.
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function completeLogout(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), Response::HTTP_NOT_FOUND);

        $pending = $request->session()->pull('cremona_sso_logout');

        abort_unless(
            is_array($pending)
            && is_string($pending['state'] ?? null)
            && is_int($pending['expires_at'] ?? null)
            && $pending['expires_at'] >= now()->getTimestamp()
            && hash_equals($pending['state'], $request->string('state')->toString()),
            Response::HTTP_UNAUTHORIZED,
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    }

    private function enabled(): bool
    {
        return config('cremona-portal.enabled') && filled(config('cremona-portal.client_id'));
    }
}
