<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProjectInvitationController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $invitation = ProjectInvitation::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'email' => Str::lower($data['email']),
                'status' => ProjectInvitation::STATUS_PENDING,
            ],
            [
                'invited_by' => $request->user()->id,
                'token' => Str::random(48),
                'message' => $data['message'] ?? null,
            ],
        );

        $existingUser = User::query()->where('email', $invitation->email)->first();

        if ($existingUser) {
            $project->members()->syncWithoutDetaching([$existingUser->id]);
            WorkspaceNotification::query()->create([
                'user_id' => $existingUser->id,
                'project_id' => $project->id,
                'type' => 'project_invitation',
                'title' => 'Project invitation',
                'body' => "{$request->user()->name} invited you to {$project->title}.",
                'data' => ['project_title' => $project->title],
            ]);
        }

        Mail::raw($this->invitationBody($request, $project, $invitation), function ($message) use ($invitation, $project): void {
            $message->to($invitation->email)->subject("Invitation to {$project->title}");
        });

        return back()->with('status', "Invitation sent to {$invitation->email}.");
    }

    public function accept(Request $request, ProjectInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->status !== ProjectInvitation::STATUS_PENDING, 410);
        abort_unless(Str::lower($request->user()->email) === Str::lower($invitation->email), 403);

        $invitation->project->members()->syncWithoutDetaching([$request->user()->id]);
        $invitation->update([
            'status' => ProjectInvitation::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return redirect()->route('projects.show', $invitation->project)->with('status', 'Project invitation accepted.');
    }

    private function invitationBody(Request $request, Project $project, ProjectInvitation $invitation): string
    {
        $acceptUrl = route('project-invitations.accept', $invitation);

        return trim(implode("\n\n", [
            "{$request->user()->name} invited you to join {$project->title} on Tasharuky.",
            $invitation->message,
            "Open the invitation: {$acceptUrl}",
        ]));
    }
}
