@extends('layouts.app')

@section('title', $project->title)
@section('eyebrow', 'Project Board')
@section('page-title', $project->title)

@section('content')
    <div class="mb-5 border-b border-gray-200 bg-white px-5 pt-5 shadow-sm" x-data="{ inviteOpen: false, copied: false }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $project->title }}</h1>
                    <form method="POST" action="{{ route('projects.favorite', $project) }}">
                        @csrf
                        <button class="text-xl text-amber-400 transition hover:scale-110" aria-label="Toggle favorite">{{ $isFavorite ? '★' : '☆' }}</button>
                    </form>
                </div>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">{{ $project->description ?: 'Design tasks such as themes, dashboards, and launch workflows.' }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ ucfirst($project->status) }}</span>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">{{ $project->tasks_count }} tasks</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-600">Owner: {{ $project->owner->name }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="document.querySelector('[data-project-task-search]')?.focus()" class="grid size-9 place-items-center rounded-lg text-gray-500 transition hover:bg-gray-100">⌕</button>
                <a href="{{ route('notifications.index', ['type' => 'task_status_changed']) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Updates</a>
                <button type="button" @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 1400)" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <span x-show="! copied">Share</span>
                    <span x-show="copied" x-cloak>Copied</span>
                </button>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                @endcan
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <nav class="flex gap-6 text-sm font-semibold text-gray-500">
                @foreach ([
                    'board' => ['Task Board', $project->tasks_count],
                    'timeline' => ['Timeline', $timelineTasks->count()],
                    'files' => ['Files', $projectFiles->count()],
                    'mentions' => ['Mentions', $mentions->count()],
                    'whiteboard' => ['Whiteboard', null],
                ] as $tab => [$label, $count])
                    <a href="{{ $tab === 'whiteboard' ? route('projects.whiteboard.show', $project) : route('projects.show', ['project' => $project, 'tab' => $tab]) }}" class="{{ $activeTab === $tab ? 'border-slate-950 text-slate-950' : 'border-transparent text-gray-500' }} border-b-2 pb-3 transition hover:text-slate-950">
                        {{ $label }}
                        @if ($count !== null)
                            <span class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px]">{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3 pb-3">
                @can('update', $project)
                    <button type="button" @click="inviteOpen = true" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">Invite</button>
                @endcan
                <div class="flex -space-x-2">
                    @foreach ($project->members->take(3) as $member)
                        <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-slate-950 text-[10px] font-bold text-white">{{ str($member->name)->substr(0, 2)->upper() }}</span>
                    @endforeach
                    <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-gray-100 text-[10px] font-bold text-gray-600">+{{ max($project->members->count() - 3, 0) }}</span>
                </div>
            </div>
        </div>

        @can('update', $project)
            <div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4">
                <form method="POST" action="{{ route('projects.invitations.store', $project) }}" @click.outside="inviteOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    @csrf
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Invitation</p>
                            <h3 class="text-xl font-bold">Invite to {{ $project->title }}</h3>
                        </div>
                        <button type="button" @click="inviteOpen = false" class="grid size-9 place-items-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50">x</button>
                    </div>
                    <label class="block text-sm font-semibold text-gray-700">Email</label>
                    <input name="email" type="email" required class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" placeholder="member@example.com">
                    <label class="mt-4 block text-sm font-semibold text-gray-700">Message</label>
                    <textarea name="message" rows="3" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" placeholder="Optional note"></textarea>
                    <button class="mt-5 w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Send Invitation</button>
                </form>
            </div>
        @endcan
    </div>

    @if ($activeTab === 'board')
        @include('tasks._board', ['project' => $project, 'projects' => collect([$project])])
    @elseif ($activeTab === 'timeline')
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="space-y-3">
                @foreach ($timelineTasks as $task)
                    <div class="grid gap-3 rounded-xl border border-gray-100 p-4 md:grid-cols-[160px_1fr_140px]">
                        <span class="text-sm font-semibold text-gray-500">{{ $task->due_date?->format('d M Y') ?? 'No due date' }}</span>
                        <div>
                            <p class="font-semibold">{{ $task->title }}</p>
                            <p class="text-sm text-gray-500">{{ $task->assignees->pluck('name')->filter()->implode(', ') ?: ($task->assignee?->name ?? 'Unassigned') }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-center text-xs font-semibold text-gray-600">{{ str($task->status)->replace('_', ' ')->title() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif ($activeTab === 'files')
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($projectFiles as $task)
                    <a href="{{ Storage::disk(config('filesystems.default'))->url($task->attachment) }}" target="_blank" class="rounded-xl border border-gray-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                        <p class="font-semibold">{{ basename($task->attachment) }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $task->title }} · {{ $task->assignees->pluck('name')->filter()->implode(', ') ?: ($task->assignee?->name ?? 'Unassigned') }}</p>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">No files uploaded yet.</p>
                @endforelse
            </div>
        </div>
    @else
        <div class="space-y-3">
            @forelse ($mentions as $mention)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="font-semibold">{{ $mention->title }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $mention->body }}</p>
                    <p class="mt-3 text-xs font-semibold text-gray-400">{{ $mention->created_at->diffForHumans() }}</p>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No mentions or updates for this project yet.</p>
            @endforelse
        </div>
    @endif
@endsection
