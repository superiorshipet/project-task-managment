<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectMessageController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $messages = $project->messages()
            ->with('user:id,name,email,role')
            ->when($request->filled('after_id'), fn ($query) => $query->where('id', '>', $request->integer('after_id')))
            ->oldest()
            ->limit(80)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn (ProjectMessage $message) => $this->messagePayload($message))->values(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $project->loadMissing(['owner:id,name,email,role', 'members:id,name,email,role']);

        $mentionableUsers = $this->mentionableUsers($project);
        $mentionedUsers = $this->mentionedUsers($validated['body'], $mentionableUsers)
            ->reject(fn (User $user) => $user->is($request->user()))
            ->values();

        $message = $project->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'mentioned_user_ids' => $mentionedUsers->pluck('id')->all(),
        ])->load('user:id,name,email,role');

        foreach ($mentionedUsers as $mentionedUser) {
            WorkspaceNotification::query()->create([
                'user_id' => $mentionedUser->id,
                'project_id' => $project->id,
                'type' => 'project_mention',
                'title' => 'You were mentioned',
                'body' => "{$request->user()->name} mentioned you in {$project->title}.",
                'data' => [
                    'message_id' => $message->id,
                    'project_title' => $project->title,
                    'sender_name' => $request->user()->name,
                ],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $this->messagePayload($message),
            ], 201);
        }

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'mentions'])
            ->with('status', 'Message sent.');
    }

    private function messagePayload(ProjectMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'mentioned' => filled($message->mentioned_user_ids),
            'created_at' => $message->created_at?->diffForHumans(),
            'user' => [
                'id' => $message->user?->id,
                'name' => $message->user?->name,
                'initials' => str($message->user?->name ?? 'NA')->substr(0, 2)->upper()->toString(),
            ],
        ];
    }

    private function mentionableUsers(Project $project)
    {
        return collect([$project->owner])
            ->merge($project->members)
            ->merge(User::query()
                ->whereIn('id', $project->tasks()->pluck('assigned_to')->filter())
                ->get(['id', 'name', 'email', 'role']))
            ->filter()
            ->unique('id')
            ->values();
    }

    private function mentionedUsers(string $body, $users)
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);

        $handles = collect($matches[1] ?? [])
            ->map(fn (string $handle) => Str::lower($handle))
            ->unique();

        if ($handles->isEmpty()) {
            return collect();
        }

        return $users->filter(fn (User $user) => $handles->contains(fn (string $handle) => in_array($handle, $this->handlesFor($user), true)));
    }

    private function handlesFor(User $user): array
    {
        $name = Str::of($user->name)->lower()->replaceMatches('/[^a-z0-9\s._-]/', '')->squish();

        return collect([
            (string) $name->replace(' ', '.'),
            (string) $name->replace(' ', ''),
            (string) $name->before(' '),
            Str::of($user->email)->before('@')->lower()->toString(),
        ])->filter()->unique()->values()->all();
    }
}
