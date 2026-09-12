<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkspaceNotification;

class WorkspaceNotifier
{
    public static function taskAssigned(Task $task): void
    {
        $task->loadMissing(['assignee', 'assignees', 'project']);

        $recipients = $task->assignees
            ->when($task->assignee, fn ($users) => $users->push($task->assignee))
            ->unique('id');

        foreach ($recipients as $recipient) {
            self::create($recipient, $task, 'task_assigned', 'New task assigned', "{$task->title} was assigned to you in {$task->project->title}.");
        }
    }

    public static function taskAssignedTo(Task $task, iterable $users): void
    {
        $task->loadMissing('project');

        foreach (collect($users)->unique('id') as $recipient) {
            self::create($recipient, $task, 'task_assigned', 'New task assigned', "{$task->title} was assigned to you in {$task->project->title}.");
        }
    }

    public static function taskStatusChanged(Task $task, User $actor, string $previousStatus): void
    {
        $task->loadMissing(['assignee', 'assignees', 'project.owner', 'project']);

        $recipients = collect([$task->assignee, $task->project->owner])
            ->merge($task->assignees)
            ->filter()
            ->reject(fn (User $user) => $user->is($actor))
            ->unique('id');

        foreach ($recipients as $recipient) {
            self::create(
                $recipient,
                $task,
                'task_status_changed',
                'Task status updated',
                "{$task->title} moved from ".str($previousStatus)->replace('_', ' ')->title().' to '.str($task->status)->replace('_', ' ')->title().'.',
            );
        }
    }

    private static function create(User $user, Task $task, string $type, string $title, string $body): void
    {
        WorkspaceNotification::query()->create([
            'user_id' => $user->id,
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => [
                'task_title' => $task->title,
                'project_title' => $task->project?->title,
                'status' => $task->status,
            ],
        ]);
    }
}
