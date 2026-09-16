<?php

namespace App\Models;

use App\Models\Concerns\ComparesJsonAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Maya719\ClickUp\Facades\ClickUp;

class Task extends Model
{
    use ComparesJsonAttributes, HasFactory;

    protected $fillable = [
        'task_list_id',
        'parent_id',
        'parent_tid',
        'top_level_parent_tid',
        'tid',
        'custom_id',
        'custom_item_id',
        'name',
        'description',
        'text_content',
        'status',
        'status_id',
        'status_color',
        'status_type',
        'priority',
        'priority_label',
        'priority_color',
        'orderindex',
        'archived',
        'assignees',
        'creator',
        'group_assignees',
        'watchers',
        'tags',
        'checklists',
        'custom_fields',
        'dependencies',
        'linked_tasks',
        'locations',
        'attachments',
        'sharing',
        'due_date',
        'start_date',
        'clickup_created_at',
        'clickup_updated_at',
        'closed_at',
        'done_at',
        'time_estimate',
        'points',
        'time_spent',
        'url',
        'team_id',
        'permission_level',
    ];

    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'assignees' => 'array',
            'creator' => 'array',
            'group_assignees' => 'array',
            'watchers' => 'array',
            'tags' => 'array',
            'checklists' => 'array',
            'custom_fields' => 'array',
            'dependencies' => 'array',
            'linked_tasks' => 'array',
            'locations' => 'array',
            'attachments' => 'array',
            'sharing' => 'array',
            'due_date' => 'datetime',
            'start_date' => 'datetime',
            'clickup_created_at' => 'datetime',
            'clickup_updated_at' => 'datetime',
            'closed_at' => 'datetime',
            'done_at' => 'datetime',
            'points' => 'float',
        ];
    }

    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isSubtask(): bool
    {
        return filled($this->parent_tid);
    }

    /**
     * Whether the status this Task sits in counts as finished in ClickUp.
     */
    public function isDone(): bool
    {
        return in_array($this->status_type, ['closed', 'done'], strict: true);
    }

    /**
     * Push the changed attributes to ClickUp, then persist them locally.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateInClickUp(array $attributes): void
    {
        $dueDate = filled($attributes['due_date'] ?? null)
            ? Carbon::parse($attributes['due_date'])
            : null;

        ClickUp::tasks()->update($this->tid, array_filter([
            'name' => $attributes['name'] ?? $this->name,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? null,
            'priority' => isset($attributes['priority']) ? (int) $attributes['priority'] : null,
            'due_date' => $dueDate?->getTimestampMs(),
        ], filled(...)));

        $this->update($attributes);
    }

    public function deleteInClickUp(): void
    {
        ClickUp::tasks()->delete($this->tid);

        $this->delete();
    }

    /**
     * The local columns of a ClickUp task payload.
     *
     * The `list`, `project`, `folder` and `space` keys are skipped: they repeat
     * the parents this table already reaches through its List.
     *
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    public static function attributesFromClickUp(array $task): array
    {
        return [
            'custom_id' => $task['custom_id'] ?? null,
            'custom_item_id' => $task['custom_item_id'] ?? null,
            'name' => $task['name'],
            'description' => $task['description'] ?? null,
            'text_content' => $task['text_content'] ?? null,
            'status' => $task['status']['status'] ?? null,
            'status_id' => $task['status']['id'] ?? null,
            'status_color' => $task['status']['color'] ?? null,
            'status_type' => $task['status']['type'] ?? null,
            'priority' => isset($task['priority']['id']) ? (int) $task['priority']['id'] : null,
            'priority_label' => $task['priority']['priority'] ?? null,
            'priority_color' => $task['priority']['color'] ?? null,
            'orderindex' => $task['orderindex'] ?? 0,
            'archived' => (bool) ($task['archived'] ?? false),
            'parent_tid' => $task['parent'] ?? null,
            'top_level_parent_tid' => $task['top_level_parent'] ?? null,
            'assignees' => $task['assignees'] ?? null,
            'creator' => $task['creator'] ?? null,
            'group_assignees' => $task['group_assignees'] ?? null,
            'watchers' => $task['watchers'] ?? null,
            'tags' => $task['tags'] ?? null,
            'checklists' => $task['checklists'] ?? null,
            'custom_fields' => $task['custom_fields'] ?? null,
            'dependencies' => $task['dependencies'] ?? null,
            'linked_tasks' => $task['linked_tasks'] ?? null,
            'locations' => $task['locations'] ?? null,
            'attachments' => $task['attachments'] ?? null,
            'sharing' => $task['sharing'] ?? null,
            'due_date' => Folder::timestampFromClickUp($task['due_date'] ?? null),
            'start_date' => Folder::timestampFromClickUp($task['start_date'] ?? null),
            'clickup_created_at' => Folder::timestampFromClickUp($task['date_created'] ?? null),
            'clickup_updated_at' => Folder::timestampFromClickUp($task['date_updated'] ?? null),
            'closed_at' => Folder::timestampFromClickUp($task['date_closed'] ?? null),
            'done_at' => Folder::timestampFromClickUp($task['date_done'] ?? null),
            'time_estimate' => $task['time_estimate'] ?? null,
            'points' => $task['points'] ?? null,
            'time_spent' => $task['time_spent'] ?? null,
            'url' => $task['url'] ?? null,
            'team_id' => $task['team_id'] ?? null,
            'permission_level' => $task['permission_level'] ?? null,
        ];
    }

    /**
     * Point subtasks at the local row of the parent ClickUp named, now that
     * every Task of the sync is stored.
     */
    public static function linkSubtasksToParents(): void
    {
        self::query()
            ->whereNotNull('parent_tid')
            ->whereNull('parent_id')
            ->get()
            ->each(function (self $task): void {
                $parentId = self::query()->where('tid', $task->parent_tid)->value('id');

                if ($parentId !== null) {
                    $task->update(['parent_id' => $parentId]);
                }
            });
    }

    /**
     * ClickUp's priority ids, highest first.
     *
     * @return array<int, string>
     */
    public static function priorities(): array
    {
        return [
            1 => 'Urgent',
            2 => 'High',
            3 => 'Normal',
            4 => 'Low',
        ];
    }
}
