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
        return 'Accès à votre espace de gestion';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Connectez-vous pour gérer votre site et les outils associés.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('continueWithCremona')
                ->label('Se connecter')
                ->url(route('cremona-sso.redirect')),
        ];
    }
}
