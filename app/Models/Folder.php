<?php

namespace App\Models;

use App\Models\Concerns\ComparesJsonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Maya719\ClickUp\Facades\ClickUp;

/**
 * A ClickUp Folder. A top level Folder is a project; a Folder nested inside
 * another one (through `parent_id`) is one of that project's versions.
 */
class Folder extends Model
{
    use ComparesJsonAttributes, HasFactory;

    protected $fillable = [
        'space_id',
        'parent_id',
        'fid',
        'name',
        'orderindex',
        'override_statuses',
        'hidden',
        'archived',
        'task_count',
        'statuses',
    ];

    protected function casts(): array
    {
        return [
            'override_statuses' => 'boolean',
            'hidden' => 'boolean',
            'archived' => 'boolean',
            'statuses' => 'array',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function parentFolder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * The sub folders of this Folder, which the application treats as versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class);
    }

    public function isVersion(): bool
    {
        return filled($this->parent_id);
    }

    /**
     * Pull the sub folders of this Folder down from ClickUp.
     *
     * ClickUp only lists Folders per Space, so the Space is read and then
     * filtered down to the sub folders of this Folder.
     *
     * @return int The number of versions ClickUp returned.
     */
    public function syncVersionsFromClickUp(): int
    {
        $versions = collect(ClickUp::folders()->all((string) $this->space->sid))
            ->filter(fn (array $folder): bool => (string) self::parentFolderIdFromClickUp($folder) === (string) $this->fid);

        foreach ($versions as $version) {
            $this->versions()->updateOrCreate(
                ['fid' => $version['id']],
                [
                    'space_id' => $this->space_id,
                    ...self::attributesFromClickUp($version),
                ]
            );
        }

        return $versions->count();
    }

    /**
     * Create the sub folder in ClickUp first, then mirror it locally.
     *
     * @param  array{name: string}  $attributes
     */
    public function createVersionInClickUp(array $attributes): self
    {
        $version = ClickUp::folders()->create((string) $this->space->sid, [
            'name' => $attributes['name'],
            'parent_folder_id' => (string) $this->fid,
        ]);

        return $this->versions()->create([
            'space_id' => $this->space_id,
            'fid' => $version['id'],
            ...self::attributesFromClickUp($version),
        ]);
    }

    /**
     * Pull every List of this Folder down from ClickUp.
     *
     * @return int The number of Lists ClickUp returned.
     */
    public function syncTaskListsFromClickUp(): int
    {
        return $this->syncTaskListsFromPayload(ClickUp::lists()->all((string) $this->fid));
    }

    /**
     * Store Lists that came back inside another response, such as the `lists`
     * key ClickUp embeds in each Folder of a Space.
     *
     * @param  array<int, array<string, mixed>>  $lists
     * @return int The number of Lists stored.
     */
    public function syncTaskListsFromPayload(array $lists): int
    {
        foreach ($lists as $list) {
            $this->taskLists()->updateOrCreate(
                ['lid' => $list['id']],
                [
                    'space_id' => $this->space_id,
                    ...TaskList::attributesFromClickUp($list),
                ]
            );
        }

        return count($lists);
    }

    /**
     * Create the List in ClickUp first, then mirror it locally.
     *
     * @param  array{name: string, content?: ?string, priority?: ?int}  $attributes
     */
    public function createTaskListInClickUp(array $attributes): TaskList
    {
        $list = ClickUp::lists()->create((string) $this->fid, array_filter([
            'name' => $attributes['name'],
            'content' => $attributes['content'] ?? null,
            'priority' => $attributes['priority'] ?? null,
        ], filled(...)));

        return $this->taskLists()->create([
            'space_id' => $this->space_id,
            'lid' => $list['id'],
            ...TaskList::attributesFromClickUp($list),
            'content' => $list['content'] ?? ($attributes['content'] ?? null),
        ]);
    }

    /**
     * Push the changed attributes to ClickUp, then persist them locally.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateInClickUp(array $attributes): void
    {
        ClickUp::folders()->update((string) $this->fid, [
            'name' => $attributes['name'] ?? $this->name,
        ]);

        $this->update($attributes);
    }

    public function deleteInClickUp(): void
    {
        ClickUp::folders()->delete((string) $this->fid);

        $this->delete();
    }

    /**
     * The local columns of a ClickUp Folder payload.
     *
     * @param  array<string, mixed>  $folder
     * @return array<string, mixed>
     */
    public static function attributesFromClickUp(array $folder): array
    {
        return [
            'name' => $folder['name'],
            'orderindex' => (int) ($folder['orderindex'] ?? 0),
            'override_statuses' => (bool) ($folder['override_statuses'] ?? false),
            'hidden' => (bool) ($folder['hidden'] ?? false),
            'archived' => (bool) ($folder['archived'] ?? false),
            'task_count' => (int) ($folder['task_count'] ?? 0),
            'statuses' => $folder['statuses'] ?? null,
        ];
    }

    /**
     * The ClickUp id of the Folder a payload is nested inside, if any.
     *
     * ClickUp returns `parent_folder` as a bare id string, while the create
     * endpoint is given `parent_folder_id`. Both shapes are accepted, as is a
     * nested object, so a change on ClickUp's side does not flatten the tree.
     *
     * @param  array<string, mixed>  $folder
     */
    public static function parentFolderIdFromClickUp(array $folder): ?string
    {
        $parent = $folder['parent_folder_id']
            ?? $folder['parent_folder']
            ?? $folder['parent']
            ?? null;

        $parentId = is_array($parent) ? ($parent['id'] ?? null) : $parent;

        return filled($parentId) ? (string) $parentId : null;
    }

    /**
     * ClickUp hands dates back as millisecond timestamps.
     */
    public static function timestampFromClickUp(mixed $milliseconds): ?Carbon
    {
        return filled($milliseconds)
            ? Carbon::createFromTimestampMs((int) $milliseconds)
            : null;
    }
}
