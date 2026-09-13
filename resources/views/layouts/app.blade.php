<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tasharuky')</title>
    <link rel="icon" href="{{ asset('tasharuky-logo.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ Vite::asset('resources/css/app.css') }}" as="style">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-[#f6f7fb] font-sans text-gray-950 antialiased">
    <div class="flex min-h-screen" x-data="{ mobileSidebarOpen: false }">
        @auth
        <div x-show="mobileSidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="mobileSidebarOpen = false"></div>
        <aside
            class="fixed inset-y-0 left-0 z-50 flex h-screen w-[min(18rem,calc(100vw-2rem))] shrink-0 -translate-x-full overflow-hidden border-r border-white/10 bg-[#1f2029] text-white transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:w-72 lg:translate-x-0"
            :class="{ 'translate-x-0': mobileSidebarOpen, '-translate-x-full': ! mobileSidebarOpen }"
        >
            <div class="flex h-screen min-w-0 flex-1 flex-col overflow-y-auto overscroll-contain px-5 py-6">
                @php
                    $unreadNotificationsCount = \App\Models\WorkspaceNotification::query()
                        ->visibleTo(auth()->user())
                        ->unread()
                        ->count();
                @endphp

                <div class="mb-6">
                    <div class="flex items-start justify-between gap-3">
                        <img src="{{ asset('tasharuky-logo.svg') }}" alt="Tasharuky" class="h-20 min-w-0 flex-1 object-contain object-left">
                        <button type="button" @click="mobileSidebarOpen = false" class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/10 text-lg text-slate-300 lg:hidden">x</button>
                    </div>
                    <p class="mt-3 px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Project Hub</p>
                </div>

                <form action="{{ route('projects.index') }}" class="mb-5">
                    <div class="relative">
                        <input name="q" value="{{ request('q') }}" class="w-full rounded-lg border border-white/10 bg-white/10 px-9 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-indigo-400" placeholder="Search projects">
                        <span class="absolute left-3 top-2.5 text-slate-500">⌕</span>
                        <span class="absolute right-3 top-2.5 text-slate-500"></span>
                    </div>
                </form>

                <nav class="space-y-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}">
                        <span>Dashboard</span>
                        <span class="text-slate-500"></span>
                    </a>
                    <a href="{{ route('projects.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('projects.*') ? 'bg-white/10' : '' }}">
                        <span>Projects</span>
                        <span class="text-slate-500"></span>
                    </a>
                    <a href="{{ route('tasks.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('tasks.*') ? 'bg-white/10' : '' }}">
                        <span>Task Board</span>
                        <span class="text-slate-500"></span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('notifications.*') ? 'bg-white/10' : '' }}">
                        <span>Notifications</span>
                        <span data-notification-count class="rounded-full {{ ($unreadNotificationsCount ?? 0) > 0 ? 'bg-rose-500 text-white' : 'text-slate-500' }} px-2 py-0.5 text-xs">{{ $unreadNotificationsCount ?? 0 }}</span>
                    </a>
                    @if (auth()->user()->canManageProjects())
                        <a href="{{ route('team.index') }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('team.*') ? 'bg-white/10' : '' }}">
                            <span>Team</span>
                            <span class="text-slate-500"></span>
                        </a>
                    @endif
                </nav>

                @php
                    $sidebarProjects = \App\Models\Project::query()
                        ->visibleTo(auth()->user())
                        ->latest()
                        ->limit(5)
                        ->get();
                    $favoriteProjects = auth()->user()
                        ->favoriteProjects()
                        ->visibleTo(auth()->user())
                        ->latest('project_favorites.created_at')
                        ->limit(5)
                        ->get();
                @endphp
                <div class="mt-8" x-data="{ open: true }">
                    <button type="button" @click="open = ! open" class="mb-3 flex w-full items-center justify-between rounded-lg px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:bg-white/5 hover:text-slate-300">
                        <span>Recent Projects</span>
                        <span class="flex items-center gap-2">
                            <span>{{ $sidebarProjects->count() }}</span>
                            <span class="transition" :class="{ 'rotate-180': open }">⌄</span>
                        </span>
                    </button>
                    <div x-show="open" x-transition.opacity.duration.150ms class="space-y-1">
                        @foreach ($sidebarProjects as $sidebarProject)
                            <a href="{{ route('projects.show', $sidebarProject) }}" class="block truncate rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">
                                <span class="mr-2 inline-block size-2 rounded-full bg-emerald-400"></span>{{ $sidebarProject->title }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6" x-data="{ open: true }">
                    <button type="button" @click="open = ! open" class="mb-3 flex w-full items-center justify-between rounded-lg px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:bg-white/5 hover:text-slate-300">
                        <span>Favorite Projects</span>
                        <span class="flex items-center gap-2">
                            <span>{{ $favoriteProjects->count() }}</span>
                            <span class="transition" :class="{ 'rotate-180': open }">⌄</span>
                        </span>
                    </button>
                    <div x-show="open" x-transition.opacity.duration.150ms class="space-y-1">
                        @forelse ($favoriteProjects as $favoriteProject)
                            <a href="{{ route('projects.show', $favoriteProject) }}" class="block truncate rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">
                                <span class="mr-2 inline-block size-2 rounded-full bg-violet-400"></span>{{ $favoriteProject->title }}
                            </a>
                        @empty
                            <p class="px-3 py-2 text-xs text-slate-500">Star projects to pin them here.</p>
                        @endforelse
                    </div>
                </div>

                @php
                    $calendarMonth = now()->startOfMonth();
                    $calendarLeadingBlanks = $calendarMonth->isoWeekday() - 1;
                    $calendarCells = (int) ceil(($calendarLeadingBlanks + $calendarMonth->daysInMonth) / 7) * 7;
                    $calendarDeadlineCounts = \App\Models\Task::query()
                        ->visibleTo(auth()->user())
                        ->whereNotNull('due_date')
                        ->whereBetween('due_date', [$calendarMonth->copy()->startOfDay(), $calendarMonth->copy()->endOfMonth()->endOfDay()])
                        ->selectRaw('DATE(due_date) as day, count(*) as total')
                        ->groupByRaw('DATE(due_date)')
                        ->pluck('total', 'day');
                    $upcomingSidebarTasks = \App\Models\Task::query()
                        ->visibleTo(auth()->user())
                        ->with(['project:id,title'])
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '>=', now()->toDateString())
                        ->orderBy('due_date')
                        ->limit(3)
                        ->get();
                @endphp
                <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-semibold">Calendar</p>
                        <p class="text-xs text-slate-500">{{ $calendarMonth->format('M Y') }}</p>
                    </div>
                    <div class="mb-4 text-[11px] font-semibold">
                        <a href="{{ route('tasks.index', ['due_range' => 'overdue']) }}" class="block rounded-lg bg-rose-500/15 px-2 py-1.5 text-center text-rose-200 transition hover:bg-rose-500/25 hover:text-white">Overdue tasks</a>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-slate-500">
                        @foreach (['M','T','W','T','F','S','S'] as $day)
                            <span>{{ $day }}</span>
                        @endforeach
                        @for ($i = 0; $i < $calendarCells; $i++)
                            @php
                                $calendarDay = $i - $calendarLeadingBlanks + 1;
                                $calendarDate = $calendarDay >= 1 && $calendarDay <= $calendarMonth->daysInMonth
                                    ? $calendarMonth->copy()->day($calendarDay)
                                    : null;
                                $calendarDateKey = $calendarDate?->toDateString();
                                $deadlineCount = $calendarDateKey ? (int) ($calendarDeadlineCounts[$calendarDateKey] ?? 0) : 0;
                                $isToday = $calendarDateKey === now()->toDateString();
                            @endphp

                            @if ($calendarDate)
                                <a href="{{ route('tasks.index', ['due_date' => $calendarDateKey]) }}" class="relative rounded-lg py-1 transition {{ $isToday ? 'bg-indigo-500 text-white' : ($deadlineCount > 0 ? 'bg-white/10 text-white hover:bg-white/15' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200') }}" title="{{ $deadlineCount }} due {{ \Illuminate\Support\Str::plural('task', $deadlineCount) }}">
                                    {{ $calendarDay }}
                                    @if ($deadlineCount > 0)
                                        <span class="absolute bottom-0.5 left-1/2 size-1 -translate-x-1/2 rounded-full {{ $isToday ? 'bg-white' : 'bg-emerald-400' }}"></span>
                                    @endif
                                </a>
                            @else
                                <span></span>
                            @endif
                        @endfor
                    </div>

                    <div class="mt-4 space-y-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming</p>
                        </div>
                        @forelse ($upcomingSidebarTasks as $upcomingTask)
                            <a href="{{ route('tasks.index', ['due_date' => $upcomingTask->due_date?->toDateString()]) }}" class="block rounded-lg bg-white/5 px-3 py-2 transition hover:bg-white/10">
                                <span class="block truncate text-xs font-semibold text-slate-100">{{ $upcomingTask->title }}</span>
                                <span class="mt-1 block truncate text-[11px] text-slate-500">{{ $upcomingTask->project?->title }} · {{ $upcomingTask->due_date?->format('M d') }}</span>
                            </a>
                        @empty
                            <p class="rounded-lg border border-dashed border-white/10 px-3 py-3 text-xs text-slate-500">No upcoming deadlines.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>
        @endauth

        <main class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-gray-200/80 bg-white/85 px-4 py-4 backdrop-blur md:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        @auth
                            <button type="button" @click="mobileSidebarOpen = true" class="grid size-10 shrink-0 place-items-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm lg:hidden" aria-label="Open navigation">
                                <span class="h-0.5 w-5 rounded-full bg-current before:mt-[-6px] before:block before:h-0.5 before:w-5 before:rounded-full before:bg-current after:mt-[10px] after:block after:h-0.5 after:w-5 after:rounded-full after:bg-current"></span>
                            </button>
                        @endauth
                        <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">@yield('eyebrow', 'Workspace')</p>
                            <h2 class="truncate text-xl font-bold tracking-tight text-gray-950 sm:text-2xl">@yield('page-title', 'Dashboard')</h2>
                        </div>
                    </div>
                    @auth
                        @php
                            $headerNotifications = \App\Models\WorkspaceNotification::query()
                                ->visibleTo(auth()->user())
                                ->with(['project:id,title'])
                                ->latest()
                                ->limit(5)
                                ->get();
                            $headerUnreadNotificationsCount = $unreadNotificationsCount ?? \App\Models\WorkspaceNotification::query()
                                ->visibleTo(auth()->user())
                                ->unread()
                                ->count();
                        @endphp
                        <div class="flex min-w-0 flex-wrap items-center justify-end gap-2 sm:gap-3">
                            <div class="relative" x-data="{ openNotifications: false }" @click.outside="openNotifications = false" data-notifications-root data-feed-url="{{ route('notifications.feed') }}" data-user-id="{{ auth()->id() }}">
                                <button type="button" @click="openNotifications = ! openNotifications" class="relative grid size-10 place-items-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50" aria-label="Notifications">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                    <span data-notification-badge class="{{ $headerUnreadNotificationsCount > 0 ? 'grid' : 'hidden' }} absolute -right-1 -top-1 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $headerUnreadNotificationsCount }}</span>
                                </button>

                                <div x-show="openNotifications" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 z-40 mt-3 w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl sm:w-96">
                                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-950">Notifications</p>
                                            <p data-notification-unread-label class="text-xs font-semibold text-gray-400">{{ $headerUnreadNotificationsCount }} unread</p>
                                        </div>
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">Mark all read</button>
                                        </form>
                                    </div>

                                    <div class="max-h-96 overflow-y-auto p-2" data-notifications-list>
                                        @forelse ($headerNotifications as $notification)
                                            <div class="rounded-xl p-3 transition {{ $notification->read_at ? 'hover:bg-gray-50' : 'bg-indigo-50/60 hover:bg-indigo-50' }}">
                                                <div class="flex items-start justify-between gap-3">
                                                    <a href="{{ route('notifications.open', $notification) }}" class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="size-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-gray-300' : 'bg-indigo-500' }}"></span>
                                                            <p class="truncate text-sm font-semibold text-gray-950">{{ $notification->title }}</p>
                                                        </div>
                                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-500">{{ $notification->body }}</p>
                                                        <p class="mt-2 text-[11px] font-semibold text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                                    </a>
                                                    @unless ($notification->read_at)
                                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="rounded-lg px-2 py-1 text-[11px] font-semibold text-indigo-600 transition hover:bg-white">Read</button>
                                                        </form>
                                                    @endunless
                                                </div>
                                            </div>
                                        @empty
                                            <p class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">No notifications yet.</p>
                                        @endforelse
                                    </div>

                                    <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 px-4 py-3 text-center text-sm font-semibold text-indigo-600 transition hover:bg-gray-50">Open notification center</a>
                                </div>
                            </div>
                            @can('create', \App\Models\Project::class)
                                <a href="{{ route('projects.create') }}" class="rounded-xl bg-slate-950 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 sm:px-4">New Project</a>
                            @endcan
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-gray-300 sm:px-4">Logout</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            <section class="px-3 py-5 sm:px-4 md:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Please review the highlighted fields.</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>
    </div>
</body>
</html>
