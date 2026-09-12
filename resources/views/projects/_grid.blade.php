<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($projects as $project)
        @php
            $progress = $project->tasks_count ? round(($project->completed_tasks_count / $project->tasks_count) * 100) : 0;
        @endphp
        <a href="{{ route('projects.show', $project) }}" data-prefetch class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="h-36 bg-gray-100">
                @if ($project->cover_image)
                    <img src="{{ Storage::disk(config('filesystems.default'))->url($project->cover_image) }}" alt="{{ $project->title }}" loading="lazy" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-slate-900 via-slate-700 to-indigo-500 text-white">
                        <span class="text-lg font-semibold">{{ str($project->title)->substr(0, 2)->upper() }}</span>
                    </div>
                @endif
            </div>
            <div class="p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-semibold group-hover:text-indigo-600">{{ $project->title }}</h3>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ ucfirst($project->status) }}</span>
                </div>
                <p class="line-clamp-2 text-sm text-gray-500">{{ $project->description ?: 'No description yet.' }}</p>
                <div class="mt-5">
                    <div class="mb-2 flex justify-between text-xs font-semibold text-gray-500">
                        <span>Progress</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500 md:col-span-2 xl:col-span-3">No projects match your filters.</div>
    @endforelse
</div>

<div class="mt-6">{{ $projects->links() }}</div>
