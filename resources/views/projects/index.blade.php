@extends('layouts.app')

@section('title', 'Projects')
@section('eyebrow', 'Workspace')
@section('page-title', 'All Projects')

@section('content')
    <form class="mb-5 grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_180px_180px_auto]" data-live-search data-live-target="#projects-grid" data-live-partial="1">
        <input name="q" value="{{ request('q') }}" placeholder="Search by title or description" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
        <select name="status" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="favorite" class="rounded-xl border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
            <option value="">All projects</option>
            <option value="1" @selected(request('favorite') === '1')>Favorites only</option>
        </select>
        <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
    </form>

    <div id="projects-grid">
        @include('projects._grid', ['projects' => $projects, 'favoriteProjectIds' => $favoriteProjectIds])
    </div>
@endsection
