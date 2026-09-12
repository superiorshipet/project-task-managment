<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
        $admin = User::factory()->create([
            'name' => 'Taskari Admin',
            'email' => 'admin@taskari.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $users = User::factory(6)->create([
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        Project::factory(6)
            ->for($admin, 'owner')
            ->create()
            ->each(function (Project $project) use ($users): void {
                Task::factory(9)->create([
                    'project_id' => $project->id,
                    'assigned_to' => $users->random()->id,
                ]);
            });
    }
}
