<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->visibleTo($request->user())
            ->with(['project', 'assignee'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('title', 'like', '%'.$request->string('q').'%')
                        ->orWhere('description', 'like', '%'.$request->string('q').'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed')")
            ->orderBy('due_date')
            ->get()
            ->groupBy('status');

        return view('tasks.index', [
            'tasksByStatus' => $tasks,
            'projects' => Project::query()->visibleTo($request->user())->orderBy('title')->get(),
            'users' => User::query()->where('role', User::ROLE_USER)->orderBy('name')->get(),
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
            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', 'public');
        }

        Task::create($data);

        return back()->with('status', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task->load(['project', 'assignee']),
            'projects' => Project::query()->visibleTo(request()->user())->orderBy('title')->get(),
            'users' => User::query()->where('role', User::ROLE_USER)->orderBy('name')->get(),
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
                Storage::disk('public')->delete($task->attachment);
            }

            $data['attachment'] = $request->file('attachment')->store('tasks/attachments', 'public');
        }

        $task->update($data);

        return redirect()->route('projects.show', $task->project)->with('status', 'Task updated successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $status = $request->validated('status');

        $task->update([
            'status' => $status,
            'progress' => $this->progressFor($status, $task->progress),
        ]);

        return back()->with('status', 'Task status updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()->with('status', 'Task moved to archive.');
    }

    public function restore(Request $request, int $task): RedirectResponse
    {
        $task = Task::withTrashed()->with('project')->findOrFail($task);
        $this->authorize('restore', $task);

        $task->restore();

        return redirect()->route('projects.show', $task->project)->with('status', 'Task restored successfully.');
    }

    private function progressFor(string $status, int $progress): int
    {
        return match ($status) {
            'pending' => min($progress, 20),
            'in_progress' => max(30, min($progress, 90)),
            'completed' => 100,
            default => $progress,
        };
    }
}
