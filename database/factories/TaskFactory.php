<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $status = fake()->randomElement(Task::STATUSES);

        return [
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
            'title' => fake()->randomElement(['Analytics dashboard', 'Appointment form', 'Landing page', 'Dashboard prototype', 'Notification center']),
            'description' => fake()->sentence(10),
            'status' => $status,
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'due_date' => fake()->dateTimeBetween('now', '+45 days')->format('Y-m-d'),
            'progress' => match ($status) {
                'pending' => fake()->numberBetween(0, 20),
                'in_progress' => fake()->numberBetween(30, 85),
                'completed' => 100,
            },
        ];
    }
}
