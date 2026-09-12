<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@taskari.test'],
            [
                'name' => 'Taskari Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        $managers = collect([
            ['name' => 'Sarah Project Manager', 'email' => 'manager@taskari.test'],
            ['name' => 'Omar Delivery Lead', 'email' => 'manager2@taskari.test'],
        ])->map(fn (array $manager) => User::query()->updateOrCreate(
            ['email' => $manager['email']],
            [
                'name' => $manager['name'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_PROJECT_MANAGER,
            ],
        ));

        $users = collect([
            ['name' => 'Mona Designer', 'email' => 'mona@taskari.test'],
            ['name' => 'Youssef Developer', 'email' => 'youssef@taskari.test'],
            ['name' => 'Nour QA Engineer', 'email' => 'nour@taskari.test'],
            ['name' => 'Kareem Analyst', 'email' => 'kareem@taskari.test'],
            ['name' => 'Laila Content Specialist', 'email' => 'laila@taskari.test'],
            ['name' => 'Hassan Support', 'email' => 'hassan@taskari.test'],
        ])->map(fn (array $member) => User::query()->updateOrCreate(
            ['email' => $member['email']],
            [
                'name' => $member['name'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
            ],
        ));

        if (Project::query()->doesntExist()) {
            collect(range(0, 5))->each(function (int $index) use ($managers, $users): void {
                $project = Project::factory()->create([
                    'user_id' => $managers[$index % $managers->count()]->id,
                ]);

                Task::factory(9)->create([
                    'project_id' => $project->id,
                    'assigned_to' => $users->random()->id,
                ]);
            });

        }

        Project::query()
            ->orderBy('id')
            ->get()
            ->each(function (Project $project, int $index) use ($managers): void {
                $project->update([
                    'user_id' => $managers[$index % $managers->count()]->id,
                    'metadata' => $project->metadata ?: [
                        'client' => $index % 2 === 0 ? 'Taskari' : 'Internal',
                        'tags' => $index % 2 === 0 ? ['design', 'workflow'] : ['backend', 'analytics'],
                    ],
                ]);
            });

        Task::query()
            ->whereNull('metadata')
            ->get()
            ->each(function (Task $task, int $index): void {
                $task->update([
                    'metadata' => [
                        'labels' => $index % 2 === 0 ? ['ui', 'review'] : ['api', 'database'],
                        'tags' => $index % 2 === 0 ? ['design', 'qa'] : ['backend', 'urgent'],
                    ],
                ]);
            });

        $this->seedWorkspaceFeatures();
    }

    private function seedWorkspaceFeatures(): void
    {
        if (\DB::table('project_favorites')->count() === 0) {
            User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_PROJECT_MANAGER])
                ->get()
                ->each(function (User $user): void {
                    $projectIds = Project::query()->visibleTo($user)->limit(2)->pluck('id');
                    $user->favoriteProjects()->syncWithoutDetaching($projectIds);
                });
        }

        if (WorkspaceNotification::query()->exists()) {
            return;
        }

        Task::query()
            ->with(['assignee', 'project'])
            ->whereNotNull('assigned_to')
            ->limit(12)
            ->get()
            ->each(function (Task $task): void {
                WorkspaceNotification::query()->create([
                    'user_id' => $task->assigned_to,
                    'project_id' => $task->project_id,
                    'task_id' => $task->id,
                    'type' => 'task_assigned',
                    'title' => 'Task activity',
                    'body' => "{$task->title} is active in your workspace.",
                    'data' => [
                        'task_title' => $task->title,
                        'project_title' => $task->project?->title,
                        'status' => $task->status,
                    ],
                ]);
            });
    }
}
