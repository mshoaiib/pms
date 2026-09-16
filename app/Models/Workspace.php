<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maya719\ClickUp\Facades\ClickUp;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'wid',
        'name',
        'slug',
        'color',
        'avatar',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    /**
     * Pull every Space of this Workspace down from ClickUp.
     *
     * @return int The number of Spaces ClickUp returned.
     */
    public function syncSpacesFromClickUp(): int
    {
        $spaces = ClickUp::spaces()->all((string) $this->wid);

        foreach ($spaces as $space) {
            $this->spaces()->updateOrCreate(
                ['sid' => $space['id']],
                [
                    'name' => $space['name'],
                    'color' => $space['color'] ?? null,
                    'avatar' => $space['avatar'] ?? null,
                    'private' => (bool) ($space['private'] ?? false),
                    'multiple_assignees' => (bool) ($space['multiple_assignees'] ?? true),
                    'statuses' => $space['statuses'] ?? null,
                    'features' => $space['features'] ?? null,
                ]
            );
        }

        return count($spaces);
    }

    /**
     * Create the Space in ClickUp first, then mirror it locally.
     *
     * @param  array{name: string, multiple_assignees?: bool, private?: bool, color?: ?string}  $attributes
     */
    public function createSpaceInClickUp(array $attributes): Space
    {
        $space = ClickUp::spaces()->create((string) $this->wid, [
            'name' => $attributes['name'],
            'multiple_assignees' => (bool) ($attributes['multiple_assignees'] ?? true),
        ]);

        return $this->spaces()->create([
            'sid' => $space['id'],
            'name' => $space['name'],
            'color' => $space['color'] ?? ($attributes['color'] ?? null),
            'avatar' => $space['avatar'] ?? null,
            'private' => (bool) ($space['private'] ?? ($attributes['private'] ?? true)),
            'multiple_assignees' => (bool) ($space['multiple_assignees'] ?? true),
            'statuses' => $space['statuses'] ?? null,
            'features' => $space['features'] ?? null,
        ]);
    }
}
