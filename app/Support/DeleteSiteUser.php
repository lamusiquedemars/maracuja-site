<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class DeleteSiteUser
{
    public static function allowed(?User $actor, User $target): bool
    {
        return $actor !== null && $actor->is_active === true
            && $actor->isAdministrator() && ! $actor->is($target);
    }

    public static function delete(User $target): bool
    {
        $actorId = auth()->id();

        return DB::transaction(function () use ($actorId, $target): bool {
            // Serialize user deletions and role updates, then recheck both
            // accounts from the database before making an irreversible change.
            $users = User::query()->orderBy('id')->lockForUpdate()->get();
            $actor = $users->firstWhere('id', $actorId);
            $currentTarget = $users->firstWhere('id', $target->getKey());

            if (! $currentTarget || ! self::allowed($actor, $currentTarget)) {
                throw new AuthorizationException;
            }

            return (bool) $currentTarget->delete();
        });
    }
}
