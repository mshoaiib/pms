<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'parent_id' => null,
            'fid' => fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->words(2, true),
            'orderindex' => fake()->numberBetween(0, 20),
            'override_statuses' => false,
            'hidden' => false,
            'archived' => false,
            'task_count' => 0,
            'statuses' => null,
        ];
    }

    /**
     * A sub folder of another Folder, which the application treats as a version.
     */
    public function version(?Folder $parent = null): static
    {
        if ($parent instanceof Folder) {
            return $this->state([
                'parent_id' => $parent->id,
                'space_id' => $parent->space_id,
                'name' => 'v'.fake()->numberBetween(1, 9).'.0',
            ]);
        }

        return $this->state([
            /** Resolved after space_id, so the parent lands in the same Space. */
            'parent_id' => fn (array $attributes): int => Folder::factory()
                ->create(['space_id' => $attributes['space_id']])
                ->id,
            'name' => 'v'.fake()->numberBetween(1, 9).'.0',
        ]);
    }
}
