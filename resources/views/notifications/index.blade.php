@extends('layouts.app')

@section('title', 'Notifications')
@section('eyebrow', 'Workspace')
@section('page-title', 'Notification Center')

@section('content')
    <div class="mb-5">
        <form method="GET" action="{{ route('notifications.index') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[180px_220px_auto]">
            <select name="state" class="rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All notifications</option>
                <option value="unread" @selected(request('state') === 'unread')>Unread</option>
                <option value="read" @selected(request('state') === 'read')>Read</option>
            </select>
            <select name="type" class="rounded-lg border border-gray-200 px-4 py-2.5 outline-none transition focus:border-indigo-400">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ str($type)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Filter</button>
        </form>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-gray-500">Unread</p>
            <p class="mt-3 text-3xl font-bold">{{ $unreadCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-gray-500">Total Shown</p>
            <p class="mt-3 text-3xl font-bold">{{ $notifications->total() }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-gray-500">Live Types</p>
            <p class="mt-3 text-3xl font-bold">{{ $types->count() }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <article class="rounded-xl border {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-indigo-200 bg-indigo-50/30' }} p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="size-2 rounded-full {{ $notification->read_at ? 'bg-gray-300' : 'bg-indigo-500' }}"></span>
                            <h3 class="font-semibold">{{ $notification->title }}</h3>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600">{{ str($notification->type)->replace('_', ' ')->title() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">{{ $notification->body }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-gray-500">
                            @if ($notification->project)
                                <a href="{{ route('projects.show', ['project' => $notification->project, 'tab' => $notification->type === 'project_mention' ? 'mentions' : 'board']) }}" class="rounded-full bg-white px-3 py-1 text-indigo-600">{{ $notification->project->title }}</a>
                            @endif
                            @if ($notification->task)
                                <span class="rounded-full bg-white px-3 py-1">{{ $notification->task->title }}</span>
                            @endif
                            <span class="rounded-full bg-white px-3 py-1">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-500">Read</span>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No notifications yet.</p>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $notifications->links() }}
    </div>
@endsection
