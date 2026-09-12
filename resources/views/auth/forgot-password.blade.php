@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('auth-mode', 'login')

@section('form')
    <div class="text-center">
        <h1 class="text-3xl font-bold tracking-tight">Reset Password</h1>
        <p class="mt-4 text-sm leading-6 text-slate-500">Enter your email and we will send you a secure reset link.</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 grid gap-4">
        @csrf
        <input name="email" type="email" value="{{ old('email') }}" placeholder="Email Address" class="w-full rounded-full border border-slate-200 bg-slate-50/80 px-5 py-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:bg-white" required autofocus>

        <button class="mx-auto mt-2 w-48 rounded-full bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-800/20 transition hover:bg-blue-800">Send Reset Link</button>
    </form>
@endsection

@section('aside')
    <h2 class="text-4xl font-bold tracking-tight">Almost There</h2>
    <p class="mt-6 text-sm leading-6 text-white/85">Use the link in your inbox to create a new password and return to your workspace.</p>
    <a href="{{ route('login') }}" data-auth-transition="login" class="mt-8 inline-flex h-12 min-w-44 items-center justify-center rounded-full border border-white px-8 text-sm font-semibold text-white transition hover:bg-white hover:text-blue-800">Back to Sign In</a>
@endsection
