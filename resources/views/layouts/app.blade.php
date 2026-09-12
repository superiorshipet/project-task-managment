<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Taskari PM'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-950 antialiased">
    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-72 shrink-0 flex-col border-r border-white/10 bg-slate-950 px-5 py-6 text-white lg:flex">
            <div class="mb-8 flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-2xl bg-indigo-500 shadow-lg shadow-indigo-500/30">
                    <span class="text-lg font-bold">T</span>
                </div>
                <div>
                    <p class="text-sm text-slate-400">Taskari</p>
                    <h1 class="font-semibold">Project Hub</h1>
                </div>
            </div>

            <form action="{{ route('projects.index') }}" class="mb-5">
                <div class="relative">
                    <input name="q" value="{{ request('q') }}" class="w-full rounded-xl border border-white/10 bg-white/10 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-indigo-400" placeholder="Search projects">
                    <span class="absolute right-3 top-2.5 text-slate-500">/</span>
                </div>
            </form>

            <nav class="space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}">
                    <span>Dashboard</span>
                    <span class="text-slate-500">01</span>
                </a>
                <a href="{{ route('projects.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('projects.*') ? 'bg-white/10' : '' }}">
                    <span>Projects</span>
                    <span class="text-slate-500">02</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('tasks.*') ? 'bg-white/10' : '' }}">
                    <span>Task Board</span>
                    <span class="text-slate-500">03</span>
                </a>
            </nav>

            @auth
                @php
                    $sidebarProjects = \App\Models\Project::query()
                        ->visibleTo(auth()->user())
                        ->latest()
                        ->limit(5)
                        ->get();
                @endphp
                <div class="mt-8">
                    <div class="mb-3 flex items-center justify-between px-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>Recent Projects</span>
                        <span>{{ $sidebarProjects->count() }}</span>
                    </div>
                    <div class="space-y-1">
                        @foreach ($sidebarProjects as $sidebarProject)
                            <a href="{{ route('projects.show', $sidebarProject) }}" class="block truncate rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white">
                                <span class="mr-2 inline-block size-2 rounded-full bg-emerald-400"></span>{{ $sidebarProject->title }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-semibold">Calendar</p>
                        <p class="text-xs text-slate-500">{{ now()->format('M Y') }}</p>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-slate-500">
                        @foreach (['M','T','W','T','F','S','S'] as $day)
                            <span>{{ $day }}</span>
                        @endforeach
                        @for ($i = 1; $i <= 35; $i++)
                            <span class="rounded-lg py-1 {{ $i === (int) now()->format('j') ? 'bg-indigo-500 text-white' : 'text-slate-300' }}">{{ $i <= now()->daysInMonth ? $i : '' }}</span>
                        @endfor
                    </div>
                </div>
            @endauth
        </aside>

        <main class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-gray-200/80 bg-white/85 px-4 py-4 backdrop-blur md:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">@yield('eyebrow', 'Workspace')</p>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950">@yield('page-title', 'Dashboard')</h2>
                    </div>
                    @auth
                        <div class="flex items-center gap-3">
                            <a href="{{ route('projects.create') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">New Project</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-gray-300">Logout</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            <section class="px-4 py-6 md:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
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
