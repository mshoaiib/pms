<?php

namespace App\Models;

use App\Models\Concerns\ComparesJsonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maya719\ClickUp\Facades\ClickUp;

class Space extends Model
{
    use ComparesJsonAttributes, HasFactory;

    protected $fillable = [
        'sid',
        'workspace_id',
        'name',
        'color',
        'avatar',
        'private',
        'statuses',
        'multiple_assignees',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'private' => 'boolean',
            'multiple_assignees' => 'boolean',
            'statuses' => 'array',
            'features' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The top level Folders of this Space. Sub folders (versions) hang off
     * their own Folder through {@see Folder::versions()}.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class)->whereNull('parent_id');
    }

    public function allFolders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class);
    }

    /**
     * Pull every Folder of this Space down from ClickUp, top level Folders
     * first so that sub folders (versions) can be attached to their parent.
     *
     * @return int The number of Folders ClickUp returned.
     */
    public function syncFoldersFromClickUp(): int
    {
        $folders = collect(ClickUp::folders()->all((string) $this->sid))
            ->sortBy(fn (array $folder): int => Folder::parentFolderIdFromClickUp($folder) === null ? 0 : 1);

        foreach ($folders as $folder) {
            $parentFolderId = Folder::parentFolderIdFromClickUp($folder);

            $record = $this->allFolders()->updateOrCreate(
                ['fid' => $folder['id']],
                [
                    'parent_id' => filled($parentFolderId)
                        ? $this->allFolders()->where('fid', $parentFolderId)->value('id')
                        : null,
                    ...Folder::attributesFromClickUp($folder),
                ]
            );

            $record->syncTaskListsFromPayload($folder['lists'] ?? []);
        }

        return $folders->count();
    }

    /**
     * Create the Folder in ClickUp first, then mirror it locally.
     *
     * @param  array{name: string}  $attributes
     */
    public function createFolderInClickUp(array $attributes): Folder
    {
        $folder = ClickUp::folders()->create((string) $this->sid, [
            'name' => $attributes['name'],
        ]);

        return $this->folders()->create([
            'fid' => $folder['id'],
            ...Folder::attributesFromClickUp($folder),
        ]);
    }

    /**
     * Push the changed attributes to ClickUp, then persist them locally.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateInClickUp(array $attributes): void
    {
        ClickUp::spaces()->update((string) $this->sid, [
            'name' => $attributes['name'] ?? $this->name,
            'color' => $attributes['color'] ?? $this->color,
            'private' => (bool) ($attributes['private'] ?? $this->private),
            'multiple_assignees' => (bool) ($attributes['multiple_assignees'] ?? $this->multiple_assignees),
        ]);

        $this->update($attributes);
    }

    public function deleteInClickUp(): void
    {
        ClickUp::spaces()->delete((string) $this->sid);

        $this->delete();
    }
}
