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
        return 'Administration du site';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Connectez-vous avec votre compte Maracuja pour gérer le site et votre activité.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('continueWithMaracuja')
                ->label('Continuer avec Maracuja')
                ->url(route('cremona-sso.redirect')),
        ];
    }
}
