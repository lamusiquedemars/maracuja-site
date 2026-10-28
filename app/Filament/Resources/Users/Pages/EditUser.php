<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $isRemovingLastAdministrator = $this->record->isAdministrator()
            && $data['role'] !== User::ROLE_ADMIN
            && User::query()->where(function ($query): void {
                $query->where('role', User::ROLE_ADMIN)->orWhere('is_admin', true);
            })->count() <= 1;

        if ($isRemovingLastAdministrator) {
            throw ValidationException::withMessages([
                'data.role' => 'Le dernier administrateur du site ne peut pas être rétrogradé.',
            ]);
        }

        $data['is_admin'] = $data['role'] === User::ROLE_ADMIN;

        return $data;
    }
}
