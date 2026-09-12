<p>Hello {{ $recipient->name }},</p>

<p>You have been assigned a new task in {{ config('app.name') }}.</p>

<p>
    <strong>{{ $task->title }}</strong><br>
    Project: {{ $task->project?->title }}<br>
    Priority: {{ ucfirst($task->priority) }}<br>
    Due date: {{ $task->due_date?->format('Y-m-d') ?? 'Not set' }}
</p>

<p>{{ $task->description }}</p>
