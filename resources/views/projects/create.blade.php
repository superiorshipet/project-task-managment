@extends('layouts.app')

@section('title', 'New Project')
@section('eyebrow', 'Projects')
@section('page-title', 'Create project')

@section('content')
    <form method="POST" action="{{ route('projects.store') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @include('projects._form', ['project' => null, 'button' => 'Create Project'])
    </form>
@endsection
