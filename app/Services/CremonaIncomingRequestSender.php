<?php

namespace App\Services;

use Maracuja\SiteCore\Contact\Data\ContactRequestData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class CremonaIncomingRequestSender
{
    public function enabled(): bool
    {
        return (bool) config('cremona-requests.enabled')
            && filled(config('cremona-requests.token'))
            && filled(config('cremona-requests.site_reference'));
    }

    public function send(ContactRequestData $message): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Le connecteur de demandes Cremona n’est pas configuré.');
        }

        $response = Http::acceptJson()
            ->withToken((string) config('cremona-requests.token'))
            ->timeout(10)
            ->withHeaders(['Idempotency-Key' => 'site-contact:'.Str::uuid()])
            ->post(config('cremona-requests.endpoint'), [
                'source' => [
                    'channel' => 'website',
                    'name' => config('cremona-requests.source_name'),
                    'site_reference' => config('cremona-requests.site_reference'),
                    'form_reference' => 'contact',
                ],
                'contact' => [
                    'name' => $message->name,
                    'email' => $message->email,
                    'phone' => $message->phone,
                ],
                'request' => [
                    'subject' => $message->subject,
                    'message' => $message->message,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Cremona a refusé la demande (HTTP '.$response->status().').');
        }
    }
}
