<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

final class CremonaPortalAuthenticator
{
    /** @return array{user: User, identity: array<string, mixed>}|null */
    public function authenticate(User $user): ?array
    {
        $clientId = (string) config('cremona-portal.client_id');
        $email = strtolower(trim($user->email));
        $subject = (string) $user->getAuthIdentifier();
        $timestamp = time();
        $signature = hash_hmac('sha256', $clientId.'|'.$subject.'|'.$email.'|'.$timestamp, (string) config('cremona-portal.logout_secret'));

        $response = Http::acceptJson()->timeout(10)->post(config('cremona-portal.issuer').'/api/v1/site-portal/authenticate', [
            'client_id' => $clientId,
            'subject' => $subject,
            'email' => $email,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        $identity = $response->successful() ? $response->json() : null;
        if (! is_array($identity) || ! $this->isValidIdentity($identity)) {
            return null;
        }

        if ((string) $identity['email'] !== $email) {
            return null;
        }

        return ['user' => $user, 'identity' => $identity];
    }

    public function sessionLink(User $user, string $item): ?string
    {
        $clientId = (string) config('cremona-portal.client_id');
        $email = strtolower(trim($user->email));
        $subject = (string) $user->getAuthIdentifier();
        $timestamp = time();
        $signature = hash_hmac('sha256', implode('|', [$clientId, $subject, $email, $item, $timestamp]), (string) config('cremona-portal.logout_secret'));

        $response = Http::acceptJson()->timeout(10)->post(config('cremona-portal.issuer').'/api/v1/site-portal/session-link', [
            'client_id' => $clientId,
            'subject' => $subject,
            'email' => $email,
            'item' => $item,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        return $response->successful() && is_string($response->json('entry_url')) ? $response->json('entry_url') : null;
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
