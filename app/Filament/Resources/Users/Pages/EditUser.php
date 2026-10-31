<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [UserResource::deleteUserAction()->successRedirectUrl(UserResource::getUrl('index'))];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $users = User::query()->orderBy('id')->lockForUpdate()->get();
            $actor = $users->firstWhere('id', auth()->id());
            $current = $users->firstWhere('id', $record->getKey());

            if (! $actor || ! $actor->is_active || ! $actor->isAdministrator() || ! $current) {
                throw new AuthorizationException;
            }

            if ($current->isAdministrator() && $current->is_active
                && (! $data['is_admin'] || ! $data['is_active'])
                && $users->filter(fn (User $user): bool => $user->is_active && $user->isAdministrator())->count() <= 1) {
                throw ValidationException::withMessages(['data.role' => 'Le dernier administrateur actif ne peut pas être désactivé ou rétrogradé.']);
            }

            return parent::handleRecordUpdate($current, $data);
        });
    }

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
