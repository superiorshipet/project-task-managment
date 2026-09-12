<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->assigned_to === $user->id
            || $task->project->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->assigned_to === $user->id
            || $task->project->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->project->user_id === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->project->user_id === $user->id;
    }
}
