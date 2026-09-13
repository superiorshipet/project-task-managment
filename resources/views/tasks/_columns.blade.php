<div class="grid gap-4 lg:gap-5 xl:grid-cols-3">
    @foreach (['todo' => ['To Do', 'bg-rose-400'], 'in_progress' => ['In Progress', 'bg-amber-400'], 'completed' => ['Completed', 'bg-emerald-400']] as $status => [$label, $dot])
        @php($columnTasks = $tasksByStatus->get($status, collect()))
        <section class="min-h-[360px] rounded-xl border border-gray-200 bg-[#f3f4f7] p-3 transition sm:min-h-[460px] xl:min-h-[620px]" data-status-column="{{ $status }}">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full {{ $dot }}"></span>
                    <h3 class="font-semibold">{{ $label }}</h3>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-gray-500" data-column-count>{{ $columnTasks->count() }}</span>
                </div>
            </div>

            <div class="space-y-4" data-column-cards>
                @forelse ($columnTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <div data-client-empty class="rounded-2xl border border-dashed border-gray-300 bg-white/70 p-6 text-center text-sm text-gray-500">No tasks here.</div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
