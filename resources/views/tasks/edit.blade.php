@extends('layouts.app')

@section('title', 'Edit Task')
@section('eyebrow', $task->project->title)
@section('page-title', 'Edit task')

@section('content')
    <form method="POST" action="{{ route('tasks.update', $task) }}" enctype="multipart/form-data" class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('tasks._form', ['task' => $task, 'button' => 'Save Task'])
    </form>
@endsection
