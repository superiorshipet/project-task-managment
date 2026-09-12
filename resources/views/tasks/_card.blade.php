@php
    $priorityClasses = [
        'low' => 'bg-emerald-50 text-emerald-700',
        'medium' => 'bg-amber-50 text-amber-700',
        'high' => 'bg-rose-50 text-rose-700',
    ][$task->priority] ?? 'bg-gray-100 text-gray-700';
@endphp

<article class="group rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md" data-task-card data-task-status="{{ $task->status }}">
    @if ($task->attachment && str($task->attachment)->endsWith(['jpg', 'jpeg', 'png', 'webp']))
        <img src="{{ Storage::disk(config('filesystems.default'))->url($task->attachment) }}" alt="{{ $task->title }}" loading="lazy" class="mb-3 h-32 w-full rounded-xl object-cover">
    @endif

    <div class="mb-2 flex items-start justify-between gap-3">
        <div>
            <h4 class="font-semibold text-gray-950">{{ $task->title }}</h4>
            <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">{{ $task->description ?: 'No description.' }}</p>
        </div>
        @can('update', $task)
            <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg px-2 py-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">...</a>
        @endcan
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
        @can('delete', $task)
            <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                @csrf
                @method('DELETE')
                <button class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-500 opacity-0 transition hover:bg-rose-50 group-hover:opacity-100">Archive</button>
            </form>
        @endcan
    </div>

    <div class="mt-3 grid grid-cols-3 gap-1">
        @foreach (['pending' => 'To Do', 'in_progress' => 'Doing', 'completed' => 'Done'] as $status => $label)
            <form method="POST" action="{{ route('tasks.status', $task) }}" data-status-form>
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $status }}">
                <button class="w-full rounded-lg border px-2 py-1 text-[11px] font-semibold transition {{ $task->status === $status ? 'border-slate-950 bg-slate-950 text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{{ $label }}</button>
            </form>
        @endforeach
    </div>
</article>
