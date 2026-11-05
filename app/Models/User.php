<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['cremona_subject', 'name', 'email', 'password', 'role', 'is_admin', 'content_permissions', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_VIEWER = 'viewer';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'content_permissions' => 'array',
        ];
    }

    public function isAdministrator(): bool
    {
        // Keep installations upgraded from the former boolean permission model
        // working until every account has been explicitly assigned a role.
        return $this->role === self::ROLE_ADMIN || $this->is_admin;
    }

    public function canEditContent(): bool
    {
        return $this->isAdministrator() || in_array($this->role, [self::ROLE_MANAGER, self::ROLE_EDITOR], true);
    }

    public function canAccessContent(string $permission): bool
    {
        return $this->isAdministrator()
            || $this->content_permissions === null
            || in_array($permission, $this->content_permissions, true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        return $this->isAdministrator()
            || in_array($this->role, [self::ROLE_MANAGER, self::ROLE_EDITOR, self::ROLE_VIEWER], true);
    }
}
