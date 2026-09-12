@extends('layouts.app')

@section('title', $project->title)
@section('eyebrow', 'Project Board')
@section('page-title', $project->title)

@section('content')
    @php
        $projectProgress = $project->tasks_count ? round(($project->completed_tasks_count / $project->tasks_count) * 100) : 0;
    @endphp

    <div class="mb-5 border-b border-gray-200 bg-white px-5 pt-5 shadow-sm" x-data="{ inviteOpen: false, copied: false }">
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
                <div class="mt-4 max-w-xl" data-project-progress data-total="{{ $project->tasks_count }}" data-completed="{{ $project->completed_tasks_count }}">
                    <div class="mb-2 flex items-center justify-between text-xs font-semibold text-gray-500">
                        <span>Project progress</span>
                        <span data-project-progress-label>{{ $projectProgress }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div data-project-progress-bar class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $projectProgress }}%"></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="document.querySelector('[data-project-task-search]')?.focus()" class="grid size-9 place-items-center rounded-lg text-gray-500 transition hover:bg-gray-100">⌕</button>
                <a href="{{ route('notifications.index', ['type' => 'task_status_changed']) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Updates</a>
                <button type="button" @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 1400)" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <span x-show="! copied">Share</span>
                    <span x-show="copied" x-cloak>Copied</span>
                </button>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Edit</a>
                @endcan
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <nav class="flex gap-6 text-sm font-semibold text-gray-500">
                @foreach ([
                    'board' => ['Task Board', $project->tasks_count],
                    'files' => ['Files', $projectFiles->count()],
                    'mentions' => ['Mentions', $projectMessages->count()],
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
                @can('update', $project)
                    <button type="button" @click="inviteOpen = true" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">Invite</button>
                @endcan
                <div class="flex -space-x-2">
                    @foreach ($project->members->take(3) as $member)
                        <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-slate-950 text-[10px] font-bold text-white">{{ str($member->name)->substr(0, 2)->upper() }}</span>
                    @endforeach
                    <span class="grid size-8 place-items-center rounded-full border-2 border-white bg-gray-100 text-[10px] font-bold text-gray-600">+{{ max($project->members->count() - 3, 0) }}</span>
                </div>
            </div>
        </div>

        @can('update', $project)
            <div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4">
                <form method="POST" action="{{ route('projects.invitations.store', $project) }}" @click.outside="inviteOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    @csrf
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Invitation</p>
                            <h3 class="text-xl font-bold">Invite to {{ $project->title }}</h3>
                        </div>
                        <button type="button" @click="inviteOpen = false" class="grid size-9 place-items-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50">x</button>
                    </div>
                    <label class="block text-sm font-semibold text-gray-700">Email</label>
                    <input name="email" type="email" required class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" placeholder="member@example.com">
                    <label class="mt-4 block text-sm font-semibold text-gray-700">Message</label>
                    <textarea name="message" rows="3" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" placeholder="Optional note"></textarea>
                    <button class="mt-5 w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Send Invitation</button>
                </form>
            </div>
        @endcan
    </div>

    @if ($activeTab === 'board')
        @include('tasks._board', ['project' => $project, 'projects' => collect([$project])])
    @elseif ($activeTab === 'files')
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($projectFiles as $task)
                    <a href="{{ Storage::disk(config('filesystems.default'))->url($task->attachment) }}" target="_blank" class="rounded-xl border border-gray-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                        <p class="font-semibold">{{ basename($task->attachment) }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $task->title }} · {{ $task->assignees->pluck('name')->filter()->implode(', ') ?: ($task->assignee?->name ?? 'Unassigned') }}</p>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">No files uploaded yet.</p>
                @endforelse
            </div>
        </div>
    @else
        <div>
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold">Project chat</h3>
                        <p class="text-sm text-gray-500">Use @handle to notify a teammate.</p>
                    </div>
                    <span data-chat-count class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $projectMessages->count() }} messages</span>
                </div>

                <div class="mb-5 max-h-[520px] space-y-3 overflow-y-auto rounded-2xl bg-gray-50 p-3" data-chat-messages data-last-message-id="{{ $projectMessages->last()?->id ?? 0 }}">
                    @forelse ($projectMessages as $message)
                        <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm" data-chat-message-id="{{ $message->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-950 text-xs font-bold text-white">{{ str($message->user->name)->substr(0, 2)->upper() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-gray-950">{{ $message->user->name }}</p>
                                        <p class="text-xs font-semibold text-gray-400">{{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                @if (filled($message->mentioned_user_ids))
                                    <span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">Mention</span>
                                @endif
                            </div>
                            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-gray-600">{{ $message->body }}</p>
                        </article>
                    @empty
                        <p data-chat-empty class="rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No chat messages yet.</p>
                    @endforelse
                </div>

                @php
                    $mentionSuggestions = $mentionableUsers->map(function ($mentionableUser) {
                        $handle = Str::of($mentionableUser->name)->lower()->replaceMatches('/[^a-z0-9\s._-]/', '')->squish()->replace(' ', '.')->toString();

                        return [
                            'id' => $mentionableUser->id,
                            'name' => $mentionableUser->name,
                            'email' => $mentionableUser->email,
                            'handle' => $handle,
                            'initials' => str($mentionableUser->name)->substr(0, 2)->upper()->toString(),
                        ];
                    })->values();
                @endphp

                <form method="POST" action="{{ route('projects.messages.store', $project) }}" class="space-y-3" data-mention-chat data-chat-project-id="{{ $project->id }}" data-chat-feed-url="{{ route('projects.messages.index', $project) }}" data-mention-users='@json($mentionSuggestions)'>
                    @csrf
                    <div class="relative">
                        <textarea name="body" rows="4" required maxlength="3000" data-mention-input class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-400" placeholder="Write a message... try {{ '@'.Str::of($mentionableUsers->first()?->name ?? 'teammate')->lower()->replace(' ', '.') }}"></textarea>
                        <div data-mention-list hidden class="absolute bottom-full left-0 z-30 mb-2 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 shadow-xl"></div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-semibold text-gray-400">Mentions create notifications for the mentioned user.</p>
                        <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Send message</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <script>
        (() => {
            const chat = document.querySelector('[data-mention-chat]');
            if (!chat) return;

            const input = chat.querySelector('[data-mention-input]');
            const list = chat.querySelector('[data-mention-list]');
            const messages = document.querySelector('[data-chat-messages]');
            const submitButton = chat.querySelector('button[type="submit"], button:not([type])');
            const tokenInput = chat.querySelector('input[name="_token"]');
            const users = JSON.parse(chat.dataset.mentionUsers || '[]');
            let activeIndex = 0;
            let matches = [];
            let token = null;
            let sending = false;
            let fetchingMessages = false;

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[char]);
            }

            function currentToken() {
                const cursor = input.selectionStart;
                const beforeCursor = input.value.slice(0, cursor);
                const match = beforeCursor.match(/(^|\s)@([A-Za-z0-9._-]*)$/);

                if (!match) return null;

                return {
                    start: cursor - match[2].length - 1,
                    end: cursor,
                    query: match[2].toLowerCase(),
                };
            }

            function hideList() {
                list.hidden = true;
                list.innerHTML = '';
                matches = [];
                activeIndex = 0;
                token = null;
            }

            function renderList() {
                if (!token) {
                    hideList();
                    return;
                }

                matches = users
                    .filter((user) => {
                        const query = token.query;

                        return user.handle.includes(query)
                            || user.name.toLowerCase().includes(query)
                            || String(user.email || '').toLowerCase().includes(query);
                    })
                    .slice(0, 7);

                if (matches.length === 0) {
                    hideList();
                    return;
                }

                list.hidden = false;
                list.innerHTML = matches.map((user, index) => `
                    <button type="button" data-mention-option="${index}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition ${index === activeIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'}">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-950 text-[10px] font-bold text-white">${escapeHtml(user.initials)}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-gray-950">${escapeHtml(user.name)}</span>
                            <span class="block truncate text-xs font-semibold text-indigo-600">@${escapeHtml(user.handle)}</span>
                        </span>
                    </button>
                `).join('');
            }

            function insertMention(user) {
                if (!token || !user) return;

                const before = input.value.slice(0, token.start);
                const after = input.value.slice(token.end);
                const mention = `@${user.handle} `;

                input.value = `${before}${mention}${after}`;
                input.focus();
                input.selectionStart = input.selectionEnd = before.length + mention.length;
                hideList();
            }

            function messageMarkup(message, pending = false) {
                return `
                    <article class="rounded-2xl border ${pending ? 'border-indigo-100 bg-indigo-50/40' : 'border-gray-100 bg-white'} p-4 shadow-sm" data-chat-message-id="${escapeHtml(message.id)}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-950 text-xs font-bold text-white">${escapeHtml(message.user?.initials || 'NA')}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-gray-950">${escapeHtml(message.user?.name || 'Unknown')}</p>
                                    <p class="text-xs font-semibold text-gray-400">${pending ? 'Sending...' : escapeHtml(message.created_at)}</p>
                                </div>
                            </div>
                            ${message.mentioned ? '<span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">Mention</span>' : ''}
                        </div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-gray-600">${escapeHtml(message.body)}</p>
                    </article>
                `;
            }

            function scrollChatToBottom() {
                if (messages) {
                    messages.scrollTop = messages.scrollHeight;
                }
            }

            function appendMessage(message, pending = false) {
                if (!messages || messages.querySelector(`[data-chat-message-id="${message.id}"]`)) {
                    return;
                }

                messages.querySelector('[data-chat-empty]')?.remove();
                messages.insertAdjacentHTML('beforeend', messageMarkup(message, pending));

                if (!pending && Number(message.id) > Number(messages.dataset.lastMessageId || 0)) {
                    messages.dataset.lastMessageId = String(message.id);
                }

                const count = document.querySelector('[data-chat-count]');
                if (count && !pending) {
                    const current = Number.parseInt(count.textContent || '0', 10);
                    count.textContent = `${current + 1} messages`;
                }

                scrollChatToBottom();
            }

            function replacePendingMessage(pendingId, message) {
                messages?.querySelector(`[data-chat-message-id="${pendingId}"]`)?.remove();
                appendMessage(message);
            }

            async function fetchMessages() {
                if (!messages || !chat.dataset.chatFeedUrl || fetchingMessages) return;

                fetchingMessages = true;

                try {
                    const url = new URL(chat.dataset.chatFeedUrl, window.location.origin);
                    url.searchParams.set('after_id', messages.dataset.lastMessageId || '0');
                    url.searchParams.set('_', Date.now());

                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) return;

                    const payload = await response.json();
                    (payload.messages || []).forEach((message) => appendMessage(message));
                } finally {
                    fetchingMessages = false;
                }
            }

            input.addEventListener('input', () => {
                token = currentToken();

                if (!token) {
                    hideList();
                    return;
                }

                activeIndex = 0;
                renderList();
            });

            input.addEventListener('keydown', (event) => {
                if (list.hidden) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeIndex = (activeIndex + 1) % matches.length;
                    renderList();
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeIndex = (activeIndex - 1 + matches.length) % matches.length;
                    renderList();
                }

                if (event.key === 'Enter' || event.key === 'Tab') {
                    event.preventDefault();
                    insertMention(matches[activeIndex]);
                }

                if (event.key === 'Escape') {
                    hideList();
                }
            });

            list.addEventListener('mousedown', (event) => {
                const option = event.target.closest('[data-mention-option]');
                if (!option) return;

                event.preventDefault();
                insertMention(matches[Number(option.dataset.mentionOption)]);
            });

            document.addEventListener('click', (event) => {
                if (!chat.contains(event.target)) {
                    hideList();
                }
            });

            chat.addEventListener('submit', async (event) => {
                event.preventDefault();

                const body = input.value.trim();
                if (!body || sending) return;

                sending = true;
                submitButton.disabled = true;

                const pendingId = `pending-${Date.now()}`;
                appendMessage({
                    id: pendingId,
                    body,
                    mentioned: /@[A-Za-z0-9._-]+/.test(body),
                    user: {
                        name: @json(auth()->user()->name),
                        initials: @json(str(auth()->user()->name)->substr(0, 2)->upper()->toString()),
                    },
                }, true);

                input.value = '';
                hideList();
                const formData = new FormData(chat);
                formData.set('body', body);

                try {
                    const response = await fetch(chat.action, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': tokenInput?.value || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Socket-ID': window.Echo?.socketId?.() || '',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error(`Message send failed with ${response.status}`);
                    }

                    const payload = await response.json();
                    replacePendingMessage(pendingId, payload.message);
                } catch (error) {
                    console.error(error);
                    const pending = messages?.querySelector(`[data-chat-message-id="${pendingId}"]`);
                    pending?.classList.remove('border-indigo-100', 'bg-indigo-50/40');
                    pending?.classList.add('border-rose-200', 'bg-rose-50');
                    input.value = body;
                    alert('Message could not be sent. Please try again.');
                } finally {
                    sending = false;
                    submitButton.disabled = false;
                }
            });

            scrollChatToBottom();

            if (window.Echo && chat.dataset.chatProjectId) {
                window.Echo.private(`projects.${chat.dataset.chatProjectId}`)
                    .listen('.project.message.sent', (event) => appendMessage(event.message));
            } else {
                setInterval(() => fetchMessages().catch(console.error), 300);
            }
        })();
    </script>
@endsection
