<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'wid' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'avatar' => null,
        ];
    }
}
