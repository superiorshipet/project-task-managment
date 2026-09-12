<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['Design Management', 'Client Portal', 'Marketing Launch', 'Mobile Redesign']).' '.fake()->numberBetween(1, 20),
            'description' => fake()->sentence(14),
            'status' => fake()->randomElement(['active', 'active', 'paused', 'completed']),
            'metadata' => [
                'client' => fake()->randomElement(['Taskari', 'Internal', 'Growth Team']),
                'tags' => fake()->randomElements(['design', 'backend', 'crm', 'workflow', 'analytics'], 2),
            ],
        ];
    }
}
