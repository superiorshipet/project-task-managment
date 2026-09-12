@extends('layouts.app')

@section('title', $project->title)
@section('eyebrow', 'Project Board')
@section('page-title', $project->title)

@section('content')
    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-0 lg:grid-cols-[320px_1fr]">
            <div class="h-56 bg-gray-100 lg:h-auto">
                @if ($project->cover_image)
                    <img src="{{ Storage::disk(config('filesystems.default'))->url($project->cover_image) }}" alt="{{ $project->title }}" loading="lazy" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-950 via-slate-800 to-indigo-500 text-white">
                        <span class="text-4xl font-bold">{{ str($project->title)->substr(0, 2)->upper() }}</span>
                    </div>
                @endif
            </div>
            <div class="p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($project->status) }}</span>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $project->tasks_count }} tasks</span>
                        </div>
                        <p class="max-w-3xl text-sm leading-6 text-gray-500">{{ $project->description ?: 'No description yet.' }}</p>
                        <p class="mt-4 text-xs font-semibold text-gray-400">Owner: {{ $project->owner->name }}</p>
                    </div>
                    @can('update', $project)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('projects.edit', $project) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">Archive</button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @include('tasks._board', ['project' => $project, 'projects' => collect([$project])])
@endsection
