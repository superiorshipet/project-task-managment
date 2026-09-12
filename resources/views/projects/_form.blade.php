@csrf
<div class="grid gap-5">
    <div>
        <label class="text-sm font-semibold text-gray-700">Project title</label>
        <input name="title" value="{{ old('title', $project->title ?? '') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
    </div>
    <div>
        <label class="text-sm font-semibold text-gray-700">Description</label>
        <textarea name="description" rows="5" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">{{ old('description', $project->description ?? '') }}</textarea>
    </div>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="text-sm font-semibold text-gray-700">Status</label>
            <select name="status" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">
                @foreach (['active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $project->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Cover image</label>
            <input name="cover_image" type="file" accept="image/*" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-white focus:border-indigo-400">
        </div>
    </div>
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('projects.index') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</a>
        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ $button }}</button>
    </div>
</div>
