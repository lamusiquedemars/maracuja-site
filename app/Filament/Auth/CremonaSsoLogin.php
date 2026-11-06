<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class CremonaSsoLogin extends Login
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Connexion à l’administration';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Utilisez votre compte Cremona pour gérer le site et votre activité. Votre mot de passe reste uniquement dans Cremona.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('continueWithCremona')
                ->label('Se connecter avec Cremona')
                ->url(route('cremona-sso.redirect')),
        ];
    }
}
