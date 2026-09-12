<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageProjects(), 403);

        $projects = Project::query()
            ->visibleTo($request->user())
            ->select(['id', 'title', 'user_id'])
            ->orderBy('title')
            ->get();

        $projectId = $request->filled('project_id') ? $request->integer('project_id') : null;
        $status = $request->filled('status') ? $request->string('status')->toString() : null;
        $keyword = trim($request->string('q')->toString());

        $users = User::query()
            ->when(! $request->user()->isAdmin(), function ($query) use ($request): void {
                $query->where(fn ($users) => $users
                    ->whereHas('assignedTasks.project', fn ($projects) => $projects->where('projects.user_id', $request->user()->id))
                    ->orWhereHas('collaborativeTasks.project', fn ($projects) => $projects->where('projects.user_id', $request->user()->id)));
            })
            ->when($request->user()->isAdmin() && $request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($keyword !== '', fn ($query) => $query->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', $keyword.'%')
                    ->orWhere('email', 'like', $keyword.'%');
            }))
            ->when($projectId, fn ($query) => $query->where(fn ($users) => $users
                ->whereHas('assignedTasks', fn ($tasks) => $tasks->where('project_id', $projectId))
                ->orWhereHas('collaborativeTasks', fn ($tasks) => $tasks->where('project_id', $projectId))))
            ->when($status, fn ($query) => $query->where(fn ($users) => $users
                ->whereHas('assignedTasks', fn ($tasks) => $tasks->where('status', $status))
                ->orWhereHas('collaborativeTasks', fn ($tasks) => $tasks->where('status', $status))))
            ->withCount([
                'assignedTasks as total_tasks_count' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request),
                'assignedTasks as todo_tasks_count' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request)->where('status', 'todo'),
                'assignedTasks as in_progress_tasks_count' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request)->where('status', 'in_progress'),
                'assignedTasks as completed_tasks_count' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request)->where('status', 'completed'),
            ])
            ->with(['assignedTasks' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request)
                ->with(['project:id,title,user_id'])
                ->when($projectId, fn ($tasks) => $tasks->where('project_id', $projectId))
                ->when($status, fn ($tasks) => $tasks->where('status', $status))
                ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'completed')")
                ->orderBy('due_date'),
                'collaborativeTasks' => fn ($tasks) => $this->scopeTasksForManager($tasks, $request)
                ->with(['project:id,title,user_id'])
                ->when($projectId, fn ($tasks) => $tasks->where('project_id', $projectId))
                ->when($status, fn ($tasks) => $tasks->where('status', $status))
                ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'completed')")
                ->orderBy('due_date')])
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $users->getCollection()->each(function (User $member): void {
            $tasks = $member->assignedTasks
                ->merge($member->collaborativeTasks)
                ->unique('id')
                ->values();

            $member->setRelation('workspaceTasks', $tasks);
            $member->setAttribute('total_tasks_count', $tasks->count());
            $member->setAttribute('todo_tasks_count', $tasks->where('status', 'todo')->count());
            $member->setAttribute('in_progress_tasks_count', $tasks->where('status', 'in_progress')->count());
            $member->setAttribute('completed_tasks_count', $tasks->where('status', 'completed')->count());
        });

        return view('team.index', [
            'users' => $users,
            'projects' => $projects,
            'statuses' => Task::STATUSES,
        ]);
    }

    public function removeFromProject(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageProject($project), 403);

        $updated = Task::query()
            ->where('project_id', $project->id)
            ->where('assigned_to', $user->id)
            ->update(['assigned_to' => null]);
        $projectTaskIds = Task::query()->where('project_id', $project->id)->pluck('id');

        DB::table('task_assignees')
            ->whereIn('task_id', $projectTaskIds)
            ->where('user_id', $user->id)
            ->delete();
        $project->members()->detach($user->id);

        Cache::increment('tasks.board.version');
        Cache::increment("project.board.version.{$project->id}");

        return back()->with('status', "{$user->name} removed from {$project->title}. {$updated} tasks are now unassigned.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if($request->user()->is($user), 422, 'You cannot delete your own account.');
        abort_if($user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1, 422, 'At least one admin must remain.');

        DB::transaction(function () use ($request, $user): void {
            Project::query()
                ->where('user_id', $user->id)
                ->update(['user_id' => $request->user()->id]);

            $user->delete();
        });

        Cache::increment('tasks.board.version');

        return redirect()->route('team.index')->with('status', "{$user->name} removed from the system.");
    }

    private function scopeTasksForManager($tasks, Request $request)
    {
        if ($request->user()->isAdmin()) {
            return $tasks;
        }

        return $tasks->whereHas('project', fn ($projects) => $projects->where('projects.user_id', $request->user()->id));
    }
}
