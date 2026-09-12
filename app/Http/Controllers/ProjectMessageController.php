<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectMessageController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
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
        ]);

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

        return redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'mentions'])
            ->with('status', 'Message sent.');
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
