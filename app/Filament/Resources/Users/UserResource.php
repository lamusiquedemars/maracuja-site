<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\ContentPermissions;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static \UnitEnum|string|null $navigationGroup = 'Réglages';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?int $navigationSort = 95;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdministrator() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('role')
                ->label('Rôle')
                ->options([
                    User::ROLE_ADMIN => 'Administrateur Maracuja',
                    User::ROLE_MANAGER => 'Responsable du site',
                    User::ROLE_EDITOR => 'Éditeur',
                    User::ROLE_VIEWER => 'Lecteur',
                ])
                ->default(User::ROLE_EDITOR)
                ->required()
                ->helperText('L’administrateur Maracuja gère les comptes et les réglages internes. Les autres profils accèdent uniquement aux fonctions attribuées.'),
            Select::make('access_scope')
                ->label('Accès aux fonctions')
                ->options(['selected' => 'Fonctions sélectionnées', 'all' => 'Toutes les fonctions'])
                ->default('selected')
                ->required()
                ->live()
                ->dehydrated(),
            CheckboxList::make('content_permissions')
                ->label('Fonctions accessibles')
                ->options(ContentPermissions::labelsForForm())
                ->columns(2)
                ->default([])
                ->visible(fn (Get $get): bool => $get('access_scope') === 'selected')
                ->helperText('Sélectionnez les fonctions accessibles. Les administrateurs ont tous les accès ; les lecteurs ne peuvent pas accéder aux demandes, conversations ou campagnes comportant des actions de modification.'),
            Toggle::make('is_active')->label('Compte actif')->default(true),
            TextInput::make('password')
                ->label('Mot de passe')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn ($state): bool => filled($state))
                ->minLength(8)
                ->helperText('Laissez vide pour conserver le mot de passe actuel.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'Administrateur',
                        User::ROLE_MANAGER => 'Responsable du site',
                        User::ROLE_EDITOR => 'Éditeur',
                        User::ROLE_VIEWER => 'Lecteur',
                        default => 'Non défini',
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Modifier'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
