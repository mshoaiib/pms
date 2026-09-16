<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskList>
 */
class TaskListFactory extends Factory
{
    protected $model = TaskList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folder_id' => Folder::factory()->version(),
            'space_id' => fn (array $attributes): int => Folder::find($attributes['folder_id'])->space_id,
            'lid' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->words(2, true),
            'content' => fake()->sentence(),
            'orderindex' => fake()->numberBetween(0, 20),
            'status' => null,
            'priority' => null,
            'archived' => false,
            'task_count' => 0,
            'due_date' => null,
            'start_date' => null,
        ];
    }
}
