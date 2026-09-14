<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use App\Support\WorkspaceLookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->visibleTo($request->user())
            ->with(['owner:id,name,email,role'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->search($request->filled('q') ? $request->string('q')->toString() : null)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('favorite'), fn ($query) => $query->whereHas('favoritedBy', fn ($favorites) => $favorites->whereKey($request->user()->id)))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $favoriteProjectIds = $request->user()->favoriteProjects()->pluck('projects.id')->all();

        if ($request->boolean('partial')) {
            return view('projects._grid', compact('projects', 'favoriteProjectIds'));
        }

        return view('projects.index', compact('projects', 'favoriteProjectIds'));
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create', [
            'projectManagers' => WorkspaceLookups::projectManagers(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();
        $data['user_id'] = $request->user()->isAdmin()
            ? $request->integer('user_id')
            : $request->user()->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('projects/covers', config('filesystems.default'));
        }

        $project = Project::create($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project created successfully.');
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        $tasksQuery = Task::query()
            ->where('project_id', $project->id)
            ->visibleTo($request->user())
            ->search($request->filled('q') ? $request->string('q')->toString() : null)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where(fn ($tasks) => $tasks
                ->where('assigned_to', $request->integer('assigned_to'))
                ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($request->integer('assigned_to')))))
            ->when($request->filled('due_date'), fn ($query) => $query->whereDate('due_date', $request->date('due_date')->toDateString()))
            ->when($request->filled('due_range'), fn ($query) => $this->applyDueRange($query, $request->string('due_range')->toString()))
            ->orderByRaw("FIELD(status, 'todo', 'in_progress', 'completed')")
            ->orderBy('due_date');

        $tasks = $this->cachedTaskBoard($tasksQuery, $request, $project->id);

        if ($request->boolean('partial')) {
            return view('tasks._columns', [
                'tasksByStatus' => $tasks,
            ]);
        }

        $project = $project->load(['owner:id,name,email,role', 'members:id,name,email,role'])
            ->loadCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed'),
            ]);
        $users = $project->assignableUsers();
        $mentionableUsers = collect([$project->owner])
            ->merge($project->members)
            ->merge(User::query()
                ->whereIn('id', $project->tasks()->pluck('assigned_to')->filter())
                ->get(['id', 'name', 'email', 'role']))
            ->filter()
            ->unique('id')
            ->values();

        return view('projects.show', [
            'project' => $project,
            'isFavorite' => $request->user()->favoriteProjects()->whereKey($project->id)->exists(),
            'tasksByStatus' => $tasks,
            'users' => $users,
            'statuses' => Task::STATUSES,
            'activeTab' => $request->string('tab')->toString() === 'timeline' ? 'board' : ($request->string('tab')->toString() ?: 'board'),
            'projectFiles' => ProjectFile::query()->where('project_id', $project->id)->with('user:id,name,email,role')->latest()->get(),
            'taskFiles' => Task::query()->where('project_id', $project->id)->visibleTo($request->user())->whereNotNull('attachment')->with(['assignee:id,name,email,role', 'assignees:id,name,email,role'])->latest()->get(),
            'projectMessages' => $project->messages()->with('user:id,name,email,role')->latest()->limit(50)->get()->reverse()->values(),
            'mentionableUsers' => $mentionableUsers,
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', [
            'project' => $project,
            'projectManagers' => WorkspaceLookups::projectManagers(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->isAdmin()
            ? $request->integer('user_id')
            : $project->user_id;

        if ($request->hasFile('cover_image')) {
            if ($project->cover_image) {
                Storage::disk(config('filesystems.default'))->delete($project->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('projects/covers', config('filesystems.default'));
        }

        $project->update($data);
        Cache::increment("project.board.version.{$project->id}");

        return redirect()->route('projects.show', $project)->with('status', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();
        Cache::increment("project.board.version.{$project->id}");

        return redirect()->route('projects.index')->with('status', 'Project moved to archive.');
    }

    public function restore(Request $request, int $project): RedirectResponse
    {
        $project = Project::withTrashed()->findOrFail($project);
        $this->authorize('restore', $project);

        $project->restore();
        Cache::increment("project.board.version.{$project->id}");

        return redirect()->route('projects.show', $project)->with('status', 'Project restored successfully.');
    }

    private function cachedTaskBoard($query, Request $request, int $projectId)
    {
        $version = Cache::get("project.board.version.{$projectId}", 1);
        $filters = collect($request->only(['q', 'status', 'assigned_to', 'due_date', 'due_range']))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->all();
        $key = 'project.board.ids.'.md5(json_encode([
            'project_id' => $projectId,
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
            ->with(['assignee:id,name,email,role', 'assignees:id,name,email,role', 'project:id,title,user_id'])
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
}
