@extends('layouts.app')

@section('title', 'Edit Project')
@section('eyebrow', 'Projects')
@section('page-title', 'Edit project')

@section('content')
    <form method="POST" action="{{ route('projects.update', $project) }}" enctype="multipart/form-data" class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('projects._form', ['project' => $project, 'button' => 'Save Changes'])
    </form>
@endsection
