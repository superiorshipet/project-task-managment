@extends('layouts.auth')

@section('title', 'Login')
@section('auth-mode', 'login')

@section('form')
    <div class="text-center">
        <h1 class="text-3xl font-bold tracking-tight">Sign In</h1>
        <p class="mt-4 text-sm text-slate-500">Use your workspace account</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 grid gap-4">
        @csrf
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email Address" class="w-full rounded-full border border-slate-200 bg-slate-50/80 px-5 py-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:bg-white" required autofocus>
        <input name="password" type="password" placeholder="Password" class="w-full rounded-full border border-slate-200 bg-slate-50/80 px-5 py-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:bg-white" required>

        <div class="flex items-center justify-center">
            <label class="flex items-center gap-2 text-sm text-slate-500">
                <input name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                Remember me
            </label>
        </div>

        <x-turnstile />

        <button class="mx-auto mt-2 w-44 rounded-full bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-800/20 transition hover:bg-blue-800">Sign In</button>
    </form>
@endsection

@section('aside')
    <h2 class="text-4xl font-bold tracking-tight">Hey There!</h2>
    <p class="mt-6 text-sm leading-6 text-white/85">Create your account now and step into your team workspace.</p>
    <a href="{{ route('register') }}" class="mt-8 inline-flex h-12 min-w-44 items-center justify-center rounded-full border border-white px-8 text-sm font-semibold text-white transition hover:bg-white hover:text-blue-800">Sign Up</a>
@endsection
