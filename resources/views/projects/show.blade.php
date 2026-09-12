@extends('layouts.app')

@section('title', $project->title)
@section('eyebrow', 'Project Board')
@section('page-title', $project->title)

@section('content')
    <div class="mb-5 border-b border-gray-200 bg-white px-5 pt-5 shadow-sm">
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
                <button class="grid size-9 place-items-center rounded-lg text-gray-500 transition hover:bg-gray-100">⌕</button>
                <button class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Updates</button>
                <button class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Share</button>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                @endcan
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <nav class="flex gap-6 text-sm font-semibold text-gray-500">
                <a href="#task-board-columns" class="border-b-2 border-slate-950 pb-3 text-slate-950">Task Board</a>
                <span class="pb-3">Timeline</span>
                <span class="pb-3">Files</span>
                <span class="pb-3">Mentions <span class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px]">3</span></span>
            </nav>

            <div class="flex items-center gap-3 pb-3">
                <button class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600">Invite</button>
                <div class="flex -space-x-2">
                    @foreach ($users->take(3) as $member)
                        <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-slate-950 text-[10px] font-bold text-white">{{ str($member->name)->substr(0, 2)->upper() }}</span>
                    @endforeach
                    <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-gray-100 text-[10px] font-bold text-gray-600">+{{ max($users->count() - 3, 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    @include('tasks._board', ['project' => $project, 'projects' => collect([$project])])
@endsection
