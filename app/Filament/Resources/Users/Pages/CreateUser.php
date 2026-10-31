<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['content_permissions'] = $data['access_scope'] === 'all' ? null : ($data['content_permissions'] ?? []);
        unset($data['access_scope']);
        $data['is_admin'] = $data['role'] === User::ROLE_ADMIN;

        return $data;
    }
}
