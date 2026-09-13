<div x-data="{ openTaskModal: false }">
    @php
        $boardFilterGrid = isset($project) && $project
            ? 'md:grid-cols-[minmax(180px,1fr)_160px_180px_auto_auto]'
            : 'md:grid-cols-[180px_minmax(180px,1fr)_160px_180px_auto_auto]';
    @endphp
    <div class="mb-5">
        <form class="{{ $boardFilterGrid }} grid w-full gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4" data-live-search data-live-mode="client" data-live-target="#task-board-columns" data-live-partial="1">
            @if (request('due_date'))
                <input type="hidden" name="due_date" value="{{ request('due_date') }}">
            @endif
            @if (request('due_range'))
                <input type="hidden" name="due_range" value="{{ request('due_range') }}">
            @endif
            @if (!isset($project) || ! $project)
                <select name="project_id" class="min-w-0 rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                    <option value="">All projects</option>
                    @foreach ($projects as $item)
                        <option value="{{ $item->id }}" @selected((int) request('project_id') === $item->id)>{{ $item->title }}</option>
                    @endforeach
                </select>
            @endif
            <input name="q" value="{{ request('q') }}" placeholder="Search tasks" data-project-task-search class="min-w-0 rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <select name="status" class="min-w-0 rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All status</option>
                @foreach (['todo' => 'To Do', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assigned_to" class="min-w-0 rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((int) request('assigned_to') === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
            @can('create', \App\Models\Task::class)
                <button type="button" @click="openTaskModal = true" class="rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">New Task</button>
            @endcan
        </form>
    </div>

    @if (request('due_date') || request('due_range'))
        <div class="mb-5 flex flex-wrap items-center gap-2 text-sm">
            <span class="rounded-full bg-indigo-50 px-3 py-1 font-semibold text-indigo-700">
                Deadline:
                @if (request('due_date'))
                    {{ \Carbon\Carbon::parse(request('due_date'))->format('d M Y') }}
                @else
                    {{ str(request('due_range'))->replace('_', ' ')->title() }}
                @endif
            </span>
            <a href="{{ isset($project) && $project ? route('projects.show', $project) : route('tasks.index') }}" class="rounded-full border border-gray-200 bg-white px-3 py-1 font-semibold text-gray-600 transition hover:bg-gray-50">Clear</a>
        </div>
    @endif

    <div id="task-board-columns">
        @include('tasks._columns', ['tasksByStatus' => $tasksByStatus])
    </div>

    @can('create', \App\Models\Task::class)
        <div x-show="openTaskModal" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-3 sm:p-4">
            <div @click.outside="openTaskModal = false" class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Quick create</p>
                        <h3 class="text-xl font-bold">New task</h3>
                    </div>
                    <button type="button" @click="openTaskModal = false" class="grid size-9 place-items-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50">x</button>
                </div>
                <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data">
                    @include('tasks._form', [
                        'task' => null,
                        'projects' => isset($project) && $project ? collect([$project]) : $projects,
                        'users' => $users,
                        'button' => 'Create Task'
                    ])
                </form>
            </div>
        </div>
    @endcan
</div>
