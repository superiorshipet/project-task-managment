<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class WorkspaceLookups
{
    public static function users(): Collection
    {
        return Cache::remember('lookups.users', now()->addMinutes(10), fn () => User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->where('role', User::ROLE_USER)
            ->orderBy('name')
            ->get());
    }

    public static function projectManagers(): Collection
    {
        return Cache::remember('lookups.project_managers', now()->addMinutes(10), fn () => User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->where('role', User::ROLE_PROJECT_MANAGER)
            ->orderBy('name')
            ->get());
    }
}
