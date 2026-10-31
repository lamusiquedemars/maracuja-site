<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['access_scope'] = $this->record->content_permissions === null ? 'all' : 'selected';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['content_permissions'] = $data['access_scope'] === 'all' ? null : ($data['content_permissions'] ?? []);
        unset($data['access_scope']);
        $isRemovingLastAdministrator = $this->record->isAdministrator()
            && $this->record->is_active
            && ($data['role'] !== User::ROLE_ADMIN || ! $data['is_active'])
            && User::query()->where('is_active', true)->where(function ($query): void {
                $query->where('role', User::ROLE_ADMIN)->orWhere('is_admin', true);
            })->count() <= 1;

        if ($isRemovingLastAdministrator) {
            throw ValidationException::withMessages([
                'data.role' => 'Le dernier administrateur du site ne peut pas être rétrogradé.',
            ]);
        }

        if (! $data['is_active'] && $this->record->is(auth()->user())) {
            throw ValidationException::withMessages(['data.is_active' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $data['is_admin'] = $data['role'] === User::ROLE_ADMIN;

        return $data;
    }
}
