@php
    $priorityClasses = [
        'low' => 'bg-emerald-50 text-emerald-700',
        'medium' => 'bg-amber-50 text-amber-700',
        'high' => 'bg-rose-50 text-rose-700',
    ][$task->priority] ?? 'bg-gray-100 text-gray-700';
@endphp

<article
    class="group rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    x-data="{ taskMenuOpen: false }"
    data-task-card
    data-task-id="{{ $task->id }}"
    data-task-status="{{ $task->status }}"
    data-task-project-id="{{ $task->project_id }}"
    data-task-assigned-to="{{ $task->assigned_to }}"
    data-task-search="{{ str($task->title.' '.$task->description.' '.$task->priority.' '.$task->status.' '.$task->project?->title.' '.$task->assignee?->name)->lower() }}"
    draggable="true"
>
    @if ($task->attachment && str($task->attachment)->endsWith(['jpg', 'jpeg', 'png', 'webp']))
        <img src="{{ Storage::disk(config('filesystems.default'))->url($task->attachment) }}" alt="{{ $task->title }}" loading="lazy" class="mb-3 h-32 w-full rounded-lg object-cover">
    @else
        <div class="mb-3 flex h-32 w-full items-center justify-center rounded-lg bg-gradient-to-br from-indigo-50 via-white to-amber-50">
            <div class="rounded-lg border border-gray-200 bg-white/80 px-4 py-3 text-center shadow-sm">
                <p class="text-[10px] font-semibold uppercase text-gray-400">Task Preview</p>
                <p class="mt-1 max-w-36 truncate text-sm font-bold text-gray-900">{{ $task->title }}</p>
            </div>
        </div>
    @endif

    <div class="mb-2 flex items-start justify-between gap-3">
        <div>
            <h4 class="font-semibold text-gray-950">{{ $task->title }}</h4>
            <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">{{ $task->description ?: 'No description.' }}</p>
        </div>
        @if (auth()->user()->can('update', $task) || auth()->user()->can('delete', $task))
            <div class="relative shrink-0" @click.outside="taskMenuOpen = false">
                <button type="button" @click.stop="taskMenuOpen = ! taskMenuOpen" class="grid size-8 place-items-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Task actions">
                    ...
                </button>
                <div x-show="taskMenuOpen" x-cloak class="absolute right-0 z-30 mt-1 w-32 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-sm shadow-lg">
                    @can('update', $task)
                        <a href="{{ route('tasks.edit', $task) }}" class="block px-3 py-2 font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                    @endcan
                    @can('delete', $task)
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?');">
                            @csrf
                            @method('DELETE')
                            <button class="block w-full px-3 py-2 text-left font-semibold text-rose-600 transition hover:bg-rose-50">Delete</button>
                        </form>
                    @endcan
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4">
        <div class="mb-1 flex justify-between text-[11px] font-semibold text-gray-500">
            <span>Progress</span>
            <span>{{ $task->progress }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $task->progress }}%"></div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $priorityClasses }}">{{ ucfirst($task->priority) }}</span>
        @if ($task->due_date)
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600">{{ $task->due_date->format('d M Y') }}</span>
        @endif
        @if ($task->attachment)
            <a href="{{ Storage::disk(config('filesystems.default'))->url($task->attachment) }}" target="_blank" class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">1 file</a>
        @endif
    </div>

    <div class="mt-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="grid size-8 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white">
                {{ str($task->assignee?->name ?? 'NA')->substr(0, 2)->upper() }}
            </div>
            <span class="max-w-24 truncate text-xs font-medium text-gray-500">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-[11px] font-semibold text-gray-500">
        <span class="rounded-lg bg-gray-100 px-2 py-1">⚑ {{ $task->due_date?->format('d M Y') ?? 'No date' }}</span>
        <span>☷ {{ $task->attachment ? 1 : 0 }}</span>
        <span>☰ {{ strlen((string) $task->description) > 0 ? 1 : 0 }}</span>
    </div>

    <div class="mt-3 grid grid-cols-3 gap-1">
        @foreach (['todo' => 'To Do', 'in_progress' => 'Doing', 'completed' => 'Done'] as $status => $label)
            <form method="POST" action="{{ route('tasks.status', $task) }}" data-status-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $status }}">
                <button class="w-full rounded-lg border px-2 py-1 text-[11px] font-semibold transition {{ $task->status === $status ? 'border-slate-950 bg-slate-950 text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{{ $label }}</button>
            </form>
        @endforeach
    </div>
</article>
