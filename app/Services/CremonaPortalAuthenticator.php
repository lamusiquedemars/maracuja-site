<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

final class CremonaPortalAuthenticator
{
    /** @return array{user: User, identity: array<string, mixed>}|null */
    public function authenticate(string $email, string $password): ?array
    {
        $clientId = (string) config('cremona-portal.client_id');
        $email = strtolower(trim($email));
        $timestamp = time();
        $signature = hash_hmac('sha256', $clientId.'|'.$email.'|'.$timestamp, (string) config('cremona-portal.logout_secret'));

        $response = Http::acceptJson()->timeout(10)->post(config('cremona-portal.issuer').'/api/v1/site-portal/authenticate', [
            'client_id' => $clientId,
            'email' => $email,
            'password' => $password,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $identity = $response->successful() ? $response->json() : null;
        if (! is_array($identity) || ! $this->isValidIdentity($identity)) {
            return null;
        }

        $user = User::query()->firstOrNew(['cremona_subject' => $identity['subject']]);
        if (! $user->exists && User::query()->where('email', $identity['email'])->exists()) {
            return null;
        }

        $role = config('cremona-portal.default_role', User::ROLE_ADMIN);
        $user->forceFill([
            'name' => $identity['name'],
            'email' => $identity['email'],
            'password' => $user->exists ? $user->password : Hash::make(str()->random(64)),
            'role' => $user->exists ? $user->role : $role,
            'is_admin' => $user->exists ? $user->is_admin : $role === User::ROLE_ADMIN,
            'is_active' => true,
            'content_permissions' => $user->exists ? $user->content_permissions : null,
        ])->save();

        return ['user' => $user, 'identity' => $identity];
    }

    /** @param array<string, mixed> $identity */
    private function isValidIdentity(array $identity): bool
    {
        return is_string($identity['subject'] ?? null)
            && is_string($identity['email'] ?? null)
            && is_string($identity['name'] ?? null)
            && is_array($identity['navigation'] ?? null);
    }
}
