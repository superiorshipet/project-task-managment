@extends('layouts.app')

@section('title', 'Login')
@section('eyebrow', 'Welcome back')
@section('page-title', 'Sign in to your workspace')

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-semibold text-gray-700">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required autofocus>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Password</label>
                <input name="password" type="password" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input name="remember" type="checkbox" value="1" class="rounded border-gray-300">
                Remember me
            </label>
            <button class="w-full rounded-xl bg-slate-950 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Login</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-500">No account? <a href="{{ route('register') }}" class="font-semibold text-indigo-600">Create one</a></p>
    </div>
@endsection
