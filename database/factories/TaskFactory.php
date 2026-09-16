<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_list_id' => TaskList::factory(),
            'tid' => Str::lower(Str::random(9)),
            'custom_id' => null,
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'to do',
            'status_color' => '#d3d3d3',
            'priority' => fake()->numberBetween(1, 4),
            'orderindex' => fake()->numberBetween(0, 20),
            'archived' => false,
            'assignees' => null,
            'tags' => null,
            'due_date' => null,
            'start_date' => null,
            'time_estimate' => null,
            'url' => null,
        ];
    }
}
