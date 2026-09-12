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
        return $task->assigned_to === $user->id
            || $user->canViewProject($task->project);
    }

    public function create(User $user): bool
    {
        return $user->canManageProjects();
    }

    public function update(User $user, Task $task): bool
    {
        return $user->canManageProject($task->project);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $user->canManageProject($task->project)
            || $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canManageProject($task->project);
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->canManageProject($task->project);
    }
}
