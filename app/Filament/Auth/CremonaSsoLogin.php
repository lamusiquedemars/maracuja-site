<?php

namespace App\Filament\Auth;

use App\Services\CremonaPortalAuthenticator;
use Filament\Auth\Pages\Login;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

final class CremonaSsoLogin extends Login
{
    public function getHeading(): string|Htmlable|null
    {
        return 'Accès à votre espace de gestion';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Connectez-vous pour gérer votre site et les outils associés.';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $result = app(CremonaPortalAuthenticator::class)->authenticate((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));

        if ($result === null) {
            throw ValidationException::withMessages(['data.email' => 'Adresse e-mail ou mot de passe incorrect.']);
        }

        Auth::login($result['user'], (bool) ($data['remember'] ?? false));
        session()->regenerate();
        session()->put('cremona_portal', [
            'organization' => $result['identity']['organization'] ?? null,
            'navigation' => $result['identity']['navigation'],
        ]);

        return app(LoginResponse::class);
    }
}
