<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CremonaPortal;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        abort_unless(is_array($pending) && is_string($pending['state'] ?? null), Response::HTTP_UNAUTHORIZED);
        abort_unless(hash_equals($pending['state'], (string) $request->query('state')), Response::HTTP_UNAUTHORIZED);
        abort_unless($request->filled('code'), Response::HTTP_UNAUTHORIZED);

        $token = Http::asForm()->acceptJson()->timeout(10)->post(config('cremona-portal.issuer').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('cremona-portal.client_id'),
            'redirect_uri' => config('cremona-portal.redirect_uri'),
            'code' => $request->string('code')->toString(),
            'code_verifier' => $pending['verifier'],
        ]);

        abort_unless($token->successful() && is_string($token->json('access_token')), Response::HTTP_UNAUTHORIZED);

        $profile = Http::withToken($token->json('access_token'))
            ->acceptJson()->timeout(10)
            ->get(config('cremona-portal.issuer').'/api/v1/site-portal/profile');

        abort_unless($profile->successful(), Response::HTTP_FORBIDDEN);

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

    private function enabled(): bool
    {
        return config('cremona-portal.enabled') && filled(config('cremona-portal.client_id'));
    }
}
