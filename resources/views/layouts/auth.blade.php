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
    @php
        $authMode = trim($__env->yieldContent('auth-mode', 'login'));
        $isRegister = $authMode === 'register';
    @endphp
    <style>
        @view-transition {
            navigation: auto;
        }

        ::view-transition-old(auth-form),
        ::view-transition-new(auth-form),
        ::view-transition-old(auth-panel),
        ::view-transition-new(auth-panel) {
            animation-duration: 420ms;
            animation-timing-function: cubic-bezier(.22, 1, .36, 1);
        }

        .auth-form-panel {
            view-transition-name: auth-form;
            animation: {{ $isRegister ? 'auth-form-from-right' : 'auth-form-from-left' }} 520ms cubic-bezier(.22, 1, .36, 1) both;
        }

        .auth-color-panel {
            view-transition-name: auth-panel;
            animation: {{ $isRegister ? 'auth-panel-from-left' : 'auth-panel-from-right' }} 520ms cubic-bezier(.22, 1, .36, 1) both;
        }

        @keyframes auth-form-from-left {
            from { opacity: .35; transform: translateX(-24px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes auth-form-from-right {
            from { opacity: .35; transform: translateX(24px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes auth-panel-from-left {
            from { opacity: .75; transform: translateX(-32px) scale(.98); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }

        @keyframes auth-panel-from-right {
            from { opacity: .75; transform: translateX(32px) scale(.98); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-[#eef2ff] font-sans text-slate-950 antialiased">
    <main class="grid min-h-screen place-items-center px-4 py-8">
        <section class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-2xl shadow-slate-300/60 lg:min-h-[650px] lg:grid-cols-2">
            <div class="auth-form-panel flex items-center justify-center bg-white/95 px-6 py-10 sm:px-10 lg:px-16 {{ $isRegister ? 'lg:order-2' : 'lg:order-1' }}">
                <div class="w-full max-w-sm">
                    <div class="mb-10 flex justify-center">
                        <img src="{{ asset('tasharuky-logo.svg') }}" alt="Tasharuky" class="h-16 w-auto">
                    </div>

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

                    @yield('form')
                </div>
            </div>

            <aside class="auth-color-panel relative isolate flex min-h-[360px] items-center justify-center overflow-hidden bg-blue-950 px-8 py-12 text-center text-white lg:min-h-full {{ $isRegister ? 'lg:order-1' : 'lg:order-2' }}">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,#0f1e52_0%,#2563eb_48%,#4338ca_100%)]"></div>
                <img src="{{ asset('tasharuky-logo.svg') }}" alt="" class="absolute left-1/2 top-1/2 h-72 w-[34rem] -translate-x-1/2 -translate-y-1/2 scale-125 object-contain opacity-30 blur-xl">
                <div class="absolute inset-0 bg-blue-950/25 backdrop-blur-[2px]"></div>
                <div class="relative max-w-sm">
                    @yield('aside')
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
