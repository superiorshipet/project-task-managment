<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProjectInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required for project invitation feature tests.');
        }

        parent::setUp();
    }

    public function test_existing_user_is_added_to_project_only_after_accepting_invitation(): void
    {
        Mail::fake();

        $manager = User::factory()->create(['role' => User::ROLE_PROJECT_MANAGER]);
        $invitedUser = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::factory()->create(['user_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('projects.invitations.store', $project), [
                'email' => $invitedUser->email,
                'message' => 'Join this project.',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $invitedUser->id,
        ]);

        $invitation = ProjectInvitation::query()->where('email', $invitedUser->email)->firstOrFail();
        $notification = WorkspaceNotification::query()
            ->where('user_id', $invitedUser->id)
            ->where('type', 'project_invitation')
            ->firstOrFail();

        $this->assertSame($invitation->id, data_get($notification->data, 'invitation_id'));

        $this->actingAs($invitedUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('project-invitations.accept', $invitation));

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $invitedUser->id,
        ]);

        $this->actingAs($invitedUser)
            ->get(route('project-invitations.accept', $invitation))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $invitedUser->id,
        ]);

        $this->assertSame(ProjectInvitation::STATUS_ACCEPTED, $invitation->refresh()->status);
    }
}
