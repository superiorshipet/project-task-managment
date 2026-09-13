@extends('layouts.app')

@section('title', 'Team')
@section('eyebrow', auth()->user()->isAdmin() ? 'Admin Control' : 'Manager Control')
@section('page-title', 'Team Workspace')

@section('content')
    <form method="GET" action="{{ route('team.index') }}" class="mb-5 grid gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4 lg:grid-cols-[1fr_160px_220px_180px_auto]">
        <input name="q" value="{{ request('q') }}" placeholder="Search users" class="min-w-0 rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">

        @if (auth()->user()->isAdmin())
            <select name="role" class="min-w-0 rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All roles</option>
                @foreach (\App\Models\User::ROLES as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ str($role)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="role" value="">
        @endif

        <select name="project_id" class="min-w-0 rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <option value="">All projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((int) request('project_id') === $project->id)>{{ $project->title }}</option>
            @endforeach
        </select>

        <select name="status" class="min-w-0 rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <option value="">All task status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>

        <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
    </form>

    <div class="grid gap-4">
        @forelse ($users as $member)
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid size-11 place-items-center rounded-2xl bg-slate-950 text-sm font-bold text-white">
                            {{ str($member->name)->substr(0, 2)->upper() }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-950">{{ $member->name }}</h3>
                            <p class="truncate text-sm text-gray-500">{{ $member->email }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($member->role)->replace('_', ' ')->title() }}</span>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $member->total_tasks_count }} tasks</span>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $member->todo_tasks_count }} todo</span>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $member->in_progress_tasks_count }} doing</span>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $member->completed_tasks_count }} done</span>

                        @if (auth()->user()->isAdmin() && ! auth()->user()->is($member))
                            <form method="POST" action="{{ route('team.destroy', $member) }}" onsubmit="return confirm('Remove this user from the system? Their owned projects will be reassigned to you.');">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Delete User</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-gray-100">
                    <div class="grid min-w-[760px] grid-cols-[1.2fr_1fr_120px_120px_160px] bg-gray-50 px-4 py-3 text-xs font-semibold uppercase text-gray-400">
                        <span>Task</span>
                        <span>Project</span>
                        <span>Status</span>
                        <span>Due</span>
                        <span class="text-right">Actions</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($member->workspaceTasks as $task)
                            <div class="grid min-w-[760px] grid-cols-[1.2fr_1fr_120px_120px_160px] items-center gap-3 px-4 py-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-950">{{ $task->title }}</p>
                                    <p class="line-clamp-1 text-xs text-gray-500">{{ $task->description ?: 'No description.' }}</p>
                                </div>
                                <a href="{{ route('projects.show', $task->project) }}" class="truncate font-medium text-indigo-600">{{ $task->project->title }}</a>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-center text-xs font-semibold text-gray-600">{{ str($task->status)->replace('_', ' ')->title() }}</span>
                                <span class="text-xs font-semibold text-gray-500">{{ $task->due_date?->format('d M Y') ?? '-' }}</span>
                                <div class="flex justify-end">
                                    @if (auth()->user()->canManageProject($task->project))
                                        <form method="POST" action="{{ route('projects.users.destroy', [$task->project, $member]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Remove from project</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-gray-500">No matching tasks for this user.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No team members match these filters.</p>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $users->links() }}
    </div>
@endsection
