@extends('layouts.app')

@section('title', 'Register')
@section('eyebrow', 'New workspace')
@section('page-title', 'Create your account')

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-semibold text-gray-700">Name</label>
                <input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required autofocus>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Password</label>
                <input name="password" type="password" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Confirm Password</label>
                <input name="password_confirmation" type="password" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 outline-none transition focus:border-indigo-400" required>
            </div>
            <x-turnstile />
            <button class="w-full rounded-xl bg-slate-950 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">Create account</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-500">Already registered? <a href="{{ route('login') }}" class="font-semibold text-indigo-600">Login</a></p>
    </div>
@endsection
