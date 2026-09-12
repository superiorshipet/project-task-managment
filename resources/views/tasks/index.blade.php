@extends('layouts.app')

@section('title', 'Task Board')
@section('eyebrow', 'Kanban')
@section('page-title', 'Task Board')

@section('content')
    @include('tasks._board', ['project' => null])
@endsection
