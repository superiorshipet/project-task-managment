<div x-data="{ openTaskModal: false }">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form class="grid flex-1 gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_160px_180px_180px_auto]">
            @if (!isset($project) || ! $project)
                <select name="project_id" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                    <option value="">All projects</option>
                    @foreach ($projects as $item)
                        <option value="{{ $item->id }}" @selected((int) request('project_id') === $item->id)>{{ $item->title }}</option>
                    @endforeach
                </select>
            @endif
            <input name="q" value="{{ request('q') }}" placeholder="Search tasks" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <select name="status" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All status</option>
                @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assigned_to" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((int) request('assigned_to') === $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
        </form>

        @can('create', \App\Models\Task::class)
            <button type="button" @click="openTaskModal = true" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">New Task</button>
        @endcan
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        @foreach (['pending' => ['To Do', 'bg-rose-400'], 'in_progress' => ['In Progress', 'bg-amber-400'], 'completed' => ['Completed', 'bg-emerald-400']] as $status => [$label, $dot])
            @php($columnTasks = $tasksByStatus->get($status, collect()))
            <section class="min-h-[620px] rounded-2xl border border-gray-200 bg-gray-100/60 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full {{ $dot }}"></span>
                        <h3 class="font-semibold">{{ $label }}</h3>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-gray-500">{{ $columnTasks->count() }}</span>
                    </div>
                    @can('create', \App\Models\Task::class)
                        <button type="button" @click="openTaskModal = true" class="grid size-8 place-items-center rounded-xl bg-slate-950 text-white transition hover:bg-slate-800">+</button>
                    @endcan
                </div>

                <div class="space-y-4">
                    @forelse ($columnTasks as $task)
                        @include('tasks._card', ['task' => $task])
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white/70 p-6 text-center text-sm text-gray-500">No tasks here.</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @can('create', \App\Models\Task::class)
        <div x-show="openTaskModal" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4">
            <div @click.outside="openTaskModal = false" class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
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
