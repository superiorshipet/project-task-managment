<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class WorkspaceLookups
{
    public static function users(): Collection
    {
        return self::cachedUsers('lookups.users.v2', User::ROLE_USER);
    }

    public static function projectManagers(): Collection
    {
        return self::cachedUsers('lookups.project_managers.v2', User::ROLE_PROJECT_MANAGER);
    }

    private static function cachedUsers(string $key, string $role): Collection
    {
        $users = Cache::remember($key, now()->addMinutes(10), fn () => User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->where('role', $role)
            ->orderBy('name')
            ->get()
            ->toArray());

        return User::hydrate($users);
    }
}
