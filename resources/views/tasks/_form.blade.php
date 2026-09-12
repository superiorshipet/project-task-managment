@csrf
@php
    $selectedAssignees = collect(old('assigned_users', isset($task)
        ? $task->assignees->pluck('id')->when($task->assigned_to, fn ($ids) => $ids->push($task->assigned_to))->unique()->values()->all()
        : []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp
<div class="grid gap-5">
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="text-sm font-semibold text-gray-700">Project</label>
            <select name="project_id" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((int) old('project_id', $task->project_id ?? request('project_id')) === $project->id)>{{ $project->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Assignees</label>
            <div class="mt-2 grid max-h-44 gap-2 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2">
                @foreach ($users as $user)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-indigo-50">
                        <input
                            type="checkbox"
                            name="assigned_users[]"
                            value="{{ $user->id }}"
                            @checked(in_array($user->id, $selectedAssignees, true))
                            class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="grid size-7 place-items-center rounded-full bg-slate-950 text-[10px] font-bold text-white">{{ str($user->name)->substr(0, 2)->upper() }}</span>
                        <span class="min-w-0 truncate font-medium text-gray-700">{{ $user->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div>
        <label class="text-sm font-semibold text-gray-700">Task title</label>
        <input name="title" value="{{ old('title', $task->title ?? '') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
    </div>

    <div>
        <label class="text-sm font-semibold text-gray-700">Description</label>
        <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">{{ old('description', $task->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div>
            <label class="text-sm font-semibold text-gray-700">Status</label>
            <select name="status" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">
                @foreach (['todo' => 'To Do', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $task->status ?? 'todo') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Priority</label>
            <select name="priority" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">
                @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $task->priority ?? 'medium') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Due date</label>
            <input name="due_date" type="date" value="{{ old('due_date', isset($task) && $task->due_date ? $task->due_date->toDateString() : '') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Progress</label>
            <input name="progress" type="number" min="0" max="100" value="{{ old('progress', $task->progress ?? 0) }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400">
        </div>
    </div>

    <div>
        <label class="text-sm font-semibold text-gray-700">Attachment</label>
        <input name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-white focus:border-indigo-400">
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ isset($task) ? route('projects.show', $task->project) : url()->previous() }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</a>
        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ $button }}</button>
    </div>
</div>
