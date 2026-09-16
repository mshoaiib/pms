<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    protected $model = Space::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'sid' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->words(2, true),
            'color' => fake()->hexColor(),
            'avatar' => null,
            'private' => false,
            'multiple_assignees' => true,
            'statuses' => null,
            'features' => null,
        ];
    }
}
