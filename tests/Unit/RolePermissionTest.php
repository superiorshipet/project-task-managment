<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use PHPUnit\Framework\TestCase;

class RolePermissionTest extends TestCase
{
    public function test_admin_can_manage_any_project(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $project = new Project(['user_id' => 999]);

        $this->assertTrue((new ProjectPolicy())->update($admin, $project));
    }

    public function test_project_manager_can_manage_only_owned_projects(): void
    {
        $manager = new User(['role' => User::ROLE_PROJECT_MANAGER]);
        $manager->id = 10;

        $ownedProject = new Project(['user_id' => 10]);
        $otherProject = new Project(['user_id' => 20]);

        $policy = new ProjectPolicy();

        $this->assertTrue($policy->update($manager, $ownedProject));
        $this->assertFalse($policy->update($manager, $otherProject));
    }

    public function test_user_can_update_assigned_task_status_without_full_task_management(): void
    {
        $user = new User(['role' => User::ROLE_USER]);
        $user->id = 7;

        $project = new Project(['user_id' => 10]);
        $task = new Task(['assigned_to' => 7]);
        $task->setRelation('project', $project);

        $policy = new TaskPolicy();

        $this->assertTrue($policy->updateStatus($user, $task));
        $this->assertFalse($policy->update($user, $task));
        $this->assertFalse($policy->delete($user, $task));
    }
}
