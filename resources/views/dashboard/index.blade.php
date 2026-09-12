@extends('layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', 'Overview')
@section('page-title', 'Design Management')

@section('content')
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            ['label' => 'Total Projects', 'value' => $totalProjects, 'tone' => 'bg-indigo-50 text-indigo-700'],
            ['label' => 'To Do Tasks', 'value' => $todoTasks, 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Completed Tasks', 'value' => $completedTasks, 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Team Members', 'value' => $teamMembers, 'tone' => 'bg-slate-100 text-slate-700'],
        ] as $stat)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-3xl font-bold">{{ $stat['value'] }}</p>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $stat['tone'] }}">Live</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Recent Projects</h3>
                <a href="{{ route('projects.index') }}" class="text-sm font-semibold text-indigo-600">View all</a>
            </div>
            <div class="space-y-3">
                @forelse ($recentProjects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="block rounded-2xl border border-gray-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="font-semibold">{{ $project->title }}</h4>
                                <p class="mt-1 line-clamp-1 text-sm text-gray-500">{{ $project->description ?: 'No description yet.' }}</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $project->tasks_count }} tasks</span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-gray-200 p-6 text-sm text-gray-500">No projects yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold">Upcoming Deadlines</h3>
            <div class="space-y-3">
                @forelse ($upcomingTasks as $task)
                    <a href="{{ route('projects.show', $task->project) }}" class="flex items-center justify-between gap-4 rounded-2xl border border-gray-100 p-4 transition hover:bg-gray-50">
                        <div>
                            <p class="font-semibold">{{ $task->title }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ $task->project->title }} · {{ $task->assignee?->name ?? 'Unassigned' }}</p>
                        </div>
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $task->due_date?->format('M d') }}</span>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-gray-200 p-6 text-sm text-gray-500">No due dates scheduled.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
