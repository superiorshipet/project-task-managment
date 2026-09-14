<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Mail\TaskAssignedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\WorkspaceLookups;
use App\Support\WorkspaceNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        $tasksQuery = Task::query()
            ->visibleTo($request->user())
            ->search($request->filled('q') ? $request->string('q')->toString() : null)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where(fn ($tasks) => $tasks
                ->where('assigned_to', $request->integer('assigned_to'))
                ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->integer('assigned_to')))))
            ->when($request->filled('due_date'), fn ($query) => $query->whereDate('due_date', $request->date('due_date')->toDateString()))
            ->when($request->filled('due_range'), fn ($query) => $this->applyDueRange($query, $request->string('due_range')->toString()))
            ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'completed')")
            ->orderBy('due_date');

        $tasks = $this->cachedTaskBoard($tasksQuery, $request);

        if ($request->boolean('partial')) {
            return view('tasks._columns', [
                'tasksByStatus' => $tasks,
            ]);
        }

        return view('tasks.index', [
            'tasksByStatus' => $tasks,
            'projects' => Project::query()->visibleTo($request->user())->select(['id', 'title', 'user_id'])->orderBy('title')->get(),
            'users' => WorkspaceLookups::users(),
            'statuses' => Task::STATUSES,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $project = Project::query()->visibleTo($request->user())->findOrFail($request->integer('project_id'));
        $this->authorize('view', $project);

        $data = $request->validated();
        $assignedUserIds = $this->assignedUserIds($request);
        $this->ensureAssigneesBelongToProject($project, $assignedUserIds);
        $data['assigned_to'] = $assignedUserIds[0] ?? null;
        unset($data['assigned_users']);
        $data['progress'] = $this->progressFor($data['status']);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', config('filesystems.default'));
        }

        $task = Task::create($data);
        $this->syncTaskAssignees($task, $assignedUserIds);
        $this->touchTaskBoardCaches($task->project_id);
        $task->load(['assignees', 'assignee', 'project']);
        $this->sendAssignmentEmails($task, $task->assignees);
        WorkspaceNotifier::taskAssigned($task);

        return back()->with('status', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task->load(['project', 'assignee', 'assignees']),
            'projects' => Project::query()->visibleTo(request()->user())->select(['id', 'title', 'user_id'])->orderBy('title')->get(),
            'users' => WorkspaceLookups::users(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $targetProject = Project::query()->visibleTo($request->user())->findOrFail($request->integer('project_id'));

        if (! $request->user()->canManageProject($targetProject)) {
            abort(403);
        }

        $data = $request->validated();
        $assignedUserIds = $this->assignedUserIds($request);
        $this->ensureAssigneesBelongToProject($targetProject, $assignedUserIds);
        $oldAssignedUserIds = $task->assignees()->pluck('users.id')->push($task->assigned_to)->filter()->unique()->values()->all();
        $data['assigned_to'] = $assignedUserIds[0] ?? null;
        unset($data['assigned_users']);
        $data['progress'] = $this->progressFor($data['status']);

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk(config('filesystems.default'))->delete($task->attachment);
            }

            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', config('filesystems.default'));
        }

        $oldProjectId = $task->project_id;

        $task->update($data);
        $this->syncTaskAssignees($task, $assignedUserIds);
        $this->touchTaskBoardCaches($task->project_id);

        if ($oldProjectId !== $task->project_id) {
            $this->touchTaskBoardCaches($oldProjectId);
        }

        $newAssignedUserIds = collect($assignedUserIds)->diff($oldAssignedUserIds)->values();

        if ($newAssignedUserIds->isNotEmpty()) {
            $newUsers = User::query()->whereIn('id', $newAssignedUserIds)->get();
            $task->loadMissing('project');
            $this->sendAssignmentEmails($task, $newUsers);
            WorkspaceNotifier::taskAssignedTo($task, $newUsers);
        }

        return redirect()->route('projects.show', $task->project)->with('status', 'Task updated successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse|RedirectResponse
    {
        $status = $request->validated('status');
        $previousStatus = $task->status;
        $progress = $this->progressFor($status);

        $task->update([
            'status' => $status,
            'progress' => $progress,
        ]);
        $this->touchTaskBoardCaches($task->project_id);

        if ($previousStatus !== $status) {
            WorkspaceNotifier::taskStatusChanged($task, $request->user(), $previousStatus);
        }

        if ($request->ajax()) {
            return response()->json([
                'id' => $task->id,
                'status' => $status,
            ]);
        }

        return back()->with('status', 'Task status updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();
        $this->touchTaskBoardCaches($task->project_id);

        return back()->with('status', 'Task moved to archive.');
    }

    public function restore(Request $request, int $task): RedirectResponse
    {
        $task = Task::withTrashed()->with('project')->findOrFail($task);
        $this->authorize('restore', $task);

        $task->restore();
        $this->touchTaskBoardCaches($task->project_id);

        return redirect()->route('projects.show', $task->project)->with('status', 'Task restored successfully.');
    }

    private function progressFor(string $status): int
    {
        return match ($status) {
            'todo' => 0,
            'in_progress' => 50,
            'completed' => 100,
            default => 0,
        };
    }

    private function assignedUserIds(Request $request): array
    {
        $ids = collect($request->input('assigned_users', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty() && $request->filled('assigned_to')) {
            $ids->push($request->integer('assigned_to'));
        }

        return $ids->unique()->values()->all();
    }

    private function syncTaskAssignees(Task $task, array $userIds): void
    {
        $task->assignees()->sync($userIds);
    }

    private function ensureAssigneesBelongToProject(Project $project, array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $allowedUserIds = $project->members()
            ->pluck('users.id')
            ->push($project->user_id)
            ->unique()
            ->values();

        $unauthorizedUserIds = collect($userIds)->diff($allowedUserIds);

        if ($unauthorizedUserIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'assigned_users' => 'Assigned users must accept the project invitation before they can be added to a task.',
            ]);
        }
    }

    private function sendAssignmentEmails(Task $task, iterable $users): void
    {
        $task->loadMissing('project');

        foreach (collect($users)->unique('id') as $user) {
            if (! $user->email) {
                continue;
            }

            Mail::to($user->email)->send(new TaskAssignedMail($task, $user));
        }
    }

    private function cachedTaskBoard($query, Request $request)
    {
        $version = Cache::get('tasks.board.version', 1);
        $filters = collect($request->only(['q', 'status', 'project_id', 'assigned_to', 'due_date', 'due_range']))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->all();
        $key = 'tasks.board.ids.'.md5(json_encode([
            'user_id' => $request->user()->id,
            'role' => $request->user()->role,
            'version' => $version,
            'filters' => $filters,
        ]));

        $ids = Cache::remember($key, now()->addSeconds(30), fn () => (clone $query)->pluck('id')->all());

        if ($ids === []) {
            return collect();
        }

        $positions = array_flip($ids);

        return Task::query()
            ->whereIn('id', $ids)
            ->with(['project:id,title,user_id', 'assignee:id,name,email,role', 'assignees:id,name,email,role'])
            ->get()
            ->sortBy(fn (Task $task) => $positions[$task->id] ?? PHP_INT_MAX)
            ->values()
            ->groupBy('status');
    }

    private function applyDueRange($query, string $range)
    {
        return match ($range) {
            'today' => $query->whereDate('due_date', now()->toDateString()),
            'week' => $query->whereBetween('due_date', [now()->startOfDay(), now()->endOfWeek()]),
            'overdue' => $query->whereDate('due_date', '<', now()->toDateString())->where('status', '!=', 'completed'),
            default => $query,
        };
    }

    private function touchTaskBoardCaches(int $projectId): void
    {
        Cache::increment('tasks.board.version');
        Cache::increment("project.board.version.{$projectId}");
    }
}
