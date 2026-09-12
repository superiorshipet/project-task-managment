<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Mail\TaskAssignedMail;
use App\Models\Project;
use App\Models\Task;
use App\Support\WorkspaceLookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
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
        $this->authorize('update', $project);

        $data = $request->validated();
        $data['progress'] = $this->progressFor($data['status'], (int) ($data['progress'] ?? 0));

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', config('filesystems.default'));
        }

        $task = Task::create($data);
        $this->touchTaskBoardCaches($task->project_id);
        $this->sendAssignmentEmail($task);

        return back()->with('status', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task->load(['project', 'assignee']),
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
        $data['progress'] = $this->progressFor($data['status'], (int) ($data['progress'] ?? $task->progress));

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk(config('filesystems.default'))->delete($task->attachment);
            }

            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', config('filesystems.default'));
        }

        $oldAssignee = $task->assigned_to;
        $oldProjectId = $task->project_id;

        $task->update($data);
        $this->touchTaskBoardCaches($task->project_id);

        if ($oldProjectId !== $task->project_id) {
            $this->touchTaskBoardCaches($oldProjectId);
        }

        if ($task->assigned_to && $task->assigned_to !== $oldAssignee) {
            $this->sendAssignmentEmail($task);
        }

        return redirect()->route('projects.show', $task->project)->with('status', 'Task updated successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse|RedirectResponse
    {
        $status = $request->validated('status');
        $progress = $this->progressFor($status, $task->progress);

        $task->update([
            'status' => $status,
            'progress' => $progress,
        ]);
        $this->touchTaskBoardCaches($task->project_id);

        if ($request->ajax()) {
            return response()->json([
                'id' => $task->id,
                'status' => $status,
                'progress' => $progress,
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

    private function progressFor(string $status, int $progress): int
    {
        return match ($status) {
            'todo' => min($progress, 20),
            'in_progress' => max(30, min($progress, 90)),
            'completed' => 100,
            default => $progress,
        };
    }

    private function sendAssignmentEmail(Task $task): void
    {
        $task->loadMissing(['assignee', 'project']);

        if (! $task->assignee?->email) {
            return;
        }

        Mail::to($task->assignee->email)->send(new TaskAssignedMail($task));
    }

    private function cachedTaskBoard($query, Request $request)
    {
        $version = Cache::get('tasks.board.version', 1);
        $filters = collect($request->only(['q', 'status', 'project_id', 'assigned_to']))
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
            ->with(['project:id,title,user_id', 'assignee:id,name,email,role'])
            ->get()
            ->sortBy(fn (Task $task) => $positions[$task->id] ?? PHP_INT_MAX)
            ->values()
            ->groupBy('status');
    }

    private function touchTaskBoardCaches(int $projectId): void
    {
        Cache::increment('tasks.board.version');
        Cache::increment("project.board.version.{$projectId}");
    }
}
