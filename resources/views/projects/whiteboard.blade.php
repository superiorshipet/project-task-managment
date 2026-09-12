@extends('layouts.app')

@section('title', $project->title.' Whiteboard')
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
                <a href="{{ route('notifications.index', ['type' => 'task_status_changed']) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Updates</a>
                <button type="button" onclick="navigator.clipboard.writeText(window.location.href)" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Share</button>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                @endcan
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <nav class="flex gap-6 text-sm font-semibold text-gray-500">
                @foreach ([
                    'board' => ['Task Board', $project->tasks_count],
                    'files' => ['Files', null],
                    'mentions' => ['Mentions', null],
                    'whiteboard' => ['Whiteboard', null],
                ] as $tab => [$label, $count])
                    <a href="{{ $tab === 'whiteboard' ? route('projects.whiteboard.show', $project) : route('projects.show', ['project' => $project, 'tab' => $tab]) }}" class="{{ $activeTab === $tab ? 'border-slate-950 text-slate-950' : 'border-transparent text-gray-500' }} border-b-2 pb-3 transition hover:text-slate-950">
                        {{ $label }}
                        @if ($count !== null)
                            <span class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px]">{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3 pb-3">
                <div class="flex -space-x-2">
                    @foreach ($project->members->take(3) as $member)
                        <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-slate-950 text-[10px] font-bold text-white">{{ str($member->name)->substr(0, 2)->upper() }}</span>
                    @endforeach
                    <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-gray-100 text-[10px] font-bold text-gray-600">+{{ max($project->members->count() - 3, 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    <section
        class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
        data-whiteboard
        data-save-url="{{ route('projects.whiteboard.update', $project) }}"
        data-load-url="{{ route('projects.whiteboard.show', $project) }}"
        data-sync-url="{{ route('projects.whiteboard.sync', $project) }}"
        data-initial='@json($whiteboard?->data ?? ['items' => []])'
        data-updated-at="{{ $whiteboard?->updated_at?->toISOString() }}"
        data-revision="{{ md5(json_encode($whiteboard?->data ?? ['items' => []])) }}"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-tool="select" class="whiteboard-tool rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Select</button>
                <button type="button" data-tool="note" class="whiteboard-tool rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Note</button>
                <button type="button" data-tool="text" class="whiteboard-tool rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Text</button>
                <button type="button" data-tool="rect" class="whiteboard-tool rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Box</button>
                <button type="button" data-tool="line" class="whiteboard-tool rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Line</button>
                <button type="button" data-tool="path" class="whiteboard-tool rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Pen</button>
                <select data-color class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 outline-none transition focus:border-indigo-400">
                    <option value="indigo">Indigo</option>
                    <option value="emerald">Emerald</option>
                    <option value="amber">Amber</option>
                    <option value="rose">Rose</option>
                    <option value="slate">Slate</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span data-save-state class="rounded-full bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-500">Ready</span>
                <button type="button" data-delete class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Delete</button>
                <button type="button" data-clear class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">Clear</button>
                <button type="button" data-save class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Save</button>
            </div>
        </div>

        <div class="relative h-[640px] overflow-hidden rounded-2xl border border-gray-200 bg-[#f8fafc]">
            <svg data-canvas class="h-full w-full cursor-crosshair touch-none" viewBox="0 0 1400 760" role="img" aria-label="Project whiteboard canvas">
                <defs>
                    <pattern id="whiteboard-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                        <path d="M 32 0 L 0 0 0 32" fill="none" stroke="#e5e7eb" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="1400" height="760" fill="url(#whiteboard-grid)"></rect>
                <g data-items></g>
            </svg>
        </div>
    </section>

    <script>
        (() => {
            const root = document.querySelector('[data-whiteboard]');
            if (!root) return;

            const canvas = root.querySelector('[data-canvas]');
            const itemsLayer = root.querySelector('[data-items]');
            const saveState = root.querySelector('[data-save-state]');
            const tools = root.querySelectorAll('[data-tool]');
            const colorInput = root.querySelector('[data-color]');
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const colors = {
                indigo: { fill: '#eef2ff', stroke: '#6366f1', text: '#312e81' },
                emerald: { fill: '#ecfdf5', stroke: '#10b981', text: '#064e3b' },
                amber: { fill: '#fffbeb', stroke: '#f59e0b', text: '#78350f' },
                rose: { fill: '#fff1f2', stroke: '#f43f5e', text: '#881337' },
                slate: { fill: '#f1f5f9', stroke: '#0f172a', text: '#0f172a' },
            };

            let mode = 'select';
            let selectedId = null;
            let activeDrag = null;
            let activePath = null;
            let dirty = false;
            let saveTimer = null;
            let saveInFlight = false;
            let saveQueued = false;
            let lastLiveSaveAt = 0;
            let lastUpdatedAt = root.dataset.updatedAt || null;
            let lastRevision = root.dataset.revision || null;
            let items = (JSON.parse(root.dataset.initial || '{"items":[]}').items || []);

            function point(event) {
                const pt = canvas.createSVGPoint();
                pt.x = event.clientX;
                pt.y = event.clientY;
                const transformed = pt.matrixTransform(canvas.getScreenCTM().inverse());

                return { x: Math.round(transformed.x), y: Math.round(transformed.y) };
            }

            function setMode(nextMode) {
                mode = nextMode;
                tools.forEach((tool) => {
                    const active = tool.dataset.tool === mode;
                    tool.classList.toggle('bg-slate-950', active);
                    tool.classList.toggle('text-white', active);
                    tool.classList.toggle('border', !active);
                    tool.classList.toggle('border-gray-200', !active);
                    tool.classList.toggle('text-gray-700', !active);
                });
            }

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[char]);
            }

            function markDirty(live = false) {
                dirty = true;
                saveState.textContent = 'Editing';
                saveState.className = 'rounded-full bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700';
                clearTimeout(saveTimer);

                if (live) {
                    const delay = Math.max(0, 260 - (Date.now() - lastLiveSaveAt));
                    saveTimer = setTimeout(save, delay);
                    return;
                }

                saveTimer = setTimeout(save, 450);
            }

            function itemMarkup(item) {
                const palette = colors[item.color] || colors.indigo;
                const selected = selectedId === item.id ? '#020617' : palette.stroke;

                if (item.type === 'note') {
                    return `<g data-item-id="${item.id}" class="cursor-move">
                        <rect x="${item.x}" y="${item.y}" width="${item.width || 210}" height="${item.height || 110}" rx="16" fill="${palette.fill}" stroke="${selected}" stroke-width="2"/>
                        <text x="${Number(item.x) + 18}" y="${Number(item.y) + 34}" fill="${palette.text}" font-size="18" font-weight="700">${escapeHtml(item.text || 'New note')}</text>
                    </g>`;
                }

                if (item.type === 'text') {
                    return `<text data-item-id="${item.id}" class="cursor-move" x="${item.x}" y="${item.y}" fill="${palette.text}" font-size="28" font-weight="800">${escapeHtml(item.text || 'Text')}</text>`;
                }

                if (item.type === 'rect') {
                    return `<rect data-item-id="${item.id}" class="cursor-move" x="${item.x}" y="${item.y}" width="${item.width || 220}" height="${item.height || 130}" rx="20" fill="${palette.fill}" stroke="${selected}" stroke-width="3"/>`;
                }

                if (item.type === 'line') {
                    return `<line data-item-id="${item.id}" class="cursor-move" x1="${item.x1}" y1="${item.y1}" x2="${item.x2}" y2="${item.y2}" stroke="${selected}" stroke-width="5" stroke-linecap="round"/>`;
                }

                const points = (item.points || []).map((pathPoint) => `${pathPoint.x},${pathPoint.y}`).join(' ');

                return `<polyline data-item-id="${item.id}" class="cursor-move" points="${points}" fill="none" stroke="${selected}" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>`;
            }

            function render() {
                itemsLayer.innerHTML = items.map(itemMarkup).join('');
            }

            function createItem(event) {
                const cursor = point(event);
                const id = `item-${Date.now()}-${Math.round(Math.random() * 1000)}`;
                const color = colorInput.value;

                if (mode === 'note') {
                    items.push({ id, type: 'note', x: cursor.x, y: cursor.y, width: 220, height: 120, color, text: prompt('Note text') || 'Project idea' });
                } else if (mode === 'text') {
                    items.push({ id, type: 'text', x: cursor.x, y: cursor.y, color, text: prompt('Text') || 'Milestone' });
                } else if (mode === 'rect') {
                    items.push({ id, type: 'rect', x: cursor.x, y: cursor.y, width: 240, height: 140, color });
                } else if (mode === 'line') {
                    items.push({ id, type: 'line', x1: cursor.x, y1: cursor.y, x2: cursor.x + 180, y2: cursor.y + 80, color });
                } else {
                    return;
                }

                selectedId = id;
                setMode('select');
                render();
                markDirty(true);
            }

            function moveItem(item, dx, dy) {
                if (['note', 'text', 'rect'].includes(item.type)) {
                    item.x = Math.round(Number(item.x || 0) + dx);
                    item.y = Math.round(Number(item.y || 0) + dy);
                } else if (item.type === 'line') {
                    item.x1 = Math.round(Number(item.x1 || 0) + dx);
                    item.y1 = Math.round(Number(item.y1 || 0) + dy);
                    item.x2 = Math.round(Number(item.x2 || 0) + dx);
                    item.y2 = Math.round(Number(item.y2 || 0) + dy);
                } else if (item.type === 'path') {
                    item.points = (item.points || []).map((pathPoint) => ({
                        x: Math.round(pathPoint.x + dx),
                        y: Math.round(pathPoint.y + dy),
                    }));
                }
            }

            async function save() {
                if (!dirty && !saveQueued) return;

                if (saveInFlight) {
                    saveQueued = true;
                    return;
                }

                clearTimeout(saveTimer);
                saveInFlight = true;
                saveQueued = false;
                lastLiveSaveAt = Date.now();
                saveState.textContent = 'Saving';
                saveState.className = 'rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700';

                const payloadToSave = { data: { items } };
                let failed = false;

                try {
                    const response = await fetch(root.dataset.saveUrl, {
                        method: 'PUT',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify(payloadToSave),
                    });

                    if (!response.ok) {
                        failed = true;
                        saveState.textContent = 'Save failed';
                        saveState.className = 'rounded-full bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600';
                        return;
                    }

                    const payload = await response.json();
                    lastUpdatedAt = payload.updated_at;
                    lastRevision = payload.revision;
                    dirty = saveQueued;
                    saveState.textContent = dirty ? 'Syncing' : 'Live';
                    saveState.className = dirty
                        ? 'rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700'
                        : 'rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700';
                } catch (error) {
                    failed = true;
                    saveState.textContent = 'Save failed';
                    saveState.className = 'rounded-full bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600';
                } finally {
                    saveInFlight = false;

                    if (failed) {
                        dirty = true;
                        saveTimer = setTimeout(save, 1500);
                        return;
                    }

                    if (saveQueued || dirty) {
                        saveQueued = false;
                        saveTimer = setTimeout(save, 80);
                    }
                }
            }

            async function refresh() {
                if (dirty || saveInFlight || activeDrag || activePath) return;

                const response = await fetch(root.dataset.syncUrl, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) return;

                const payload = await response.json();
                if (payload.revision && payload.revision !== lastRevision) {
                    items = payload.data.items || [];
                    lastUpdatedAt = payload.updated_at;
                    lastRevision = payload.revision;
                    selectedId = null;
                    saveState.textContent = 'Live';
                    saveState.className = 'rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600';
                    render();
                }
            }

            tools.forEach((tool) => tool.addEventListener('click', () => setMode(tool.dataset.tool)));
            root.querySelector('[data-save]').addEventListener('click', save);
            root.querySelector('[data-delete]').addEventListener('click', () => {
                if (!selectedId) return;
                items = items.filter((item) => item.id !== selectedId);
                selectedId = null;
                render();
                markDirty(true);
            });
            root.querySelector('[data-clear]').addEventListener('click', () => {
                if (!confirm('Clear the whiteboard?')) return;
                items = [];
                selectedId = null;
                render();
                markDirty(true);
            });

            canvas.addEventListener('pointerdown', (event) => {
                const target = event.target.closest('[data-item-id]');
                const cursor = point(event);

                if (mode === 'path' && !target) {
                    activePath = { id: `item-${Date.now()}`, type: 'path', color: colorInput.value, points: [cursor] };
                    items.push(activePath);
                    render();
                    markDirty(true);
                    return;
                }

                if (target) {
                    selectedId = target.dataset.itemId;
                    activeDrag = { id: selectedId, last: cursor };
                    render();
                    return;
                }

                selectedId = null;
                if (mode !== 'select') {
                    createItem(event);
                } else {
                    render();
                }
            });

            canvas.addEventListener('pointermove', (event) => {
                const cursor = point(event);

                if (activePath) {
                activePath.points.push(cursor);
                render();
                markDirty(true);
                return;
                }

                if (!activeDrag) return;

                const item = items.find((entry) => entry.id === activeDrag.id);
                if (!item) return;

                moveItem(item, cursor.x - activeDrag.last.x, cursor.y - activeDrag.last.y);
                activeDrag.last = cursor;
                render();
                markDirty(true);
            });

            window.addEventListener('pointerup', () => {
                activeDrag = null;
                activePath = null;
                save();
            });

            render();
            setMode('select');
            setInterval(refresh, 650);
        })();
    </script>
@endsection
