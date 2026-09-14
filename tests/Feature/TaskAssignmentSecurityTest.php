<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TaskAssignmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required for task assignment security tests.');
        }

        parent::setUp();
    }

    public function test_task_assignment_cannot_add_unaccepted_user_to_project(): void
    {
        Mail::fake();

        $manager = User::factory()->create(['role' => User::ROLE_PROJECT_MANAGER]);
        $outsideUser = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::factory()->create(['user_id' => $manager->id]);

        $this->actingAs($manager)
            ->from(route('projects.show', $project))
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'assigned_users' => [$outsideUser->id],
                'title' => 'Secure assignment',
                'description' => 'Should not bypass invitations.',
                'status' => 'todo',
                'priority' => 'medium',
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('assigned_users');

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $outsideUser->id,
        ]);

        $this->assertDatabaseMissing('tasks', [
            'project_id' => $project->id,
            'assigned_to' => $outsideUser->id,
            'title' => 'Secure assignment',
        ]);
    }

    public function test_task_assignment_allows_existing_project_member(): void
    {
        Mail::fake();

        $manager = User::factory()->create(['role' => User::ROLE_PROJECT_MANAGER]);
        $member = User::factory()->create(['role' => User::ROLE_USER]);
        $project = Project::factory()->create(['user_id' => $manager->id]);
        $project->members()->attach($member->id);

        $this->actingAs($manager)
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'assigned_users' => [$member->id],
                'title' => 'Member assignment',
                'description' => 'Allowed after joining the project.',
                'status' => 'todo',
                'priority' => 'medium',
            ])
            ->assertSessionHas('status');

        $task = Task::query()->where('title', 'Member assignment')->firstOrFail();

        $this->assertSame($member->id, $task->assigned_to);
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $member->id,
        ]);
    }
}
