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
 * A ClickUp List. Named TaskList because "List" is a reserved word in PHP.
 */
class TaskList extends Model
{
    use ComparesJsonAttributes, HasFactory;

    protected $fillable = [
        'space_id',
        'folder_id',
        'lid',
        'name',
        'content',
        'orderindex',
        'status',
        'priority',
        'archived',
        'task_count',
        'due_date',
        'start_date',
    ];

    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'due_date' => 'datetime',
            'start_date' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * The local columns of a ClickUp List payload.
     *
     * @param  array<string, mixed>  $list
     * @return array<string, mixed>
     */
    public static function attributesFromClickUp(array $list): array
    {
        return [
            'name' => $list['name'],
            'content' => $list['content'] ?? null,
            'orderindex' => (int) ($list['orderindex'] ?? 0),
            'status' => $list['status']['status'] ?? null,
            'priority' => $list['priority']['priority'] ?? null,
            'archived' => (bool) ($list['archived'] ?? false),
            'task_count' => (int) ($list['task_count'] ?? 0),
            'due_date' => Folder::timestampFromClickUp($list['due_date'] ?? null),
            'start_date' => Folder::timestampFromClickUp($list['start_date'] ?? null),
        ];
    }

    /**
     * Pull every Task of this List down from ClickUp, walking each page lazily.
     *
     * @return int The number of Tasks ClickUp returned.
     */
    public function syncTasksFromClickUp(): int
    {
        $count = 0;

        foreach (ClickUp::tasks()->cursor((string) $this->lid, ['subtasks' => true]) as $task) {
            $this->tasks()->updateOrCreate(
                ['tid' => $task['id']],
                Task::attributesFromClickUp($task)
            );

            $count++;
        }

        Task::linkSubtasksToParents();

        return $count;
    }

    /**
     * Create the Task in ClickUp first, then mirror it locally.
     *
     * @param  array{name: string, description?: ?string, status?: ?string, priority?: ?int, assignees?: array<int, int>, due_date?: Carbon|string|null}  $attributes
     */
    public function createTaskInClickUp(array $attributes): Task
    {
        $dueDate = filled($attributes['due_date'] ?? null)
            ? Carbon::parse($attributes['due_date'])
            : null;

        $task = ClickUp::tasks()->create((string) $this->lid, array_filter([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? null,
            'priority' => isset($attributes['priority']) ? (int) $attributes['priority'] : null,
            'assignees' => $attributes['assignees'] ?? null,
            'due_date' => $dueDate?->getTimestampMs(),
        ], filled(...)));

        return $this->tasks()->create([
            'tid' => $task['id'],
            ...Task::attributesFromClickUp($task),
            'description' => $task['description'] ?? ($attributes['description'] ?? null),
            'due_date' => Folder::timestampFromClickUp($task['due_date'] ?? null) ?? $dueDate,
        ]);
    }

    /**
     * Push the changed attributes to ClickUp, then persist them locally.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateInClickUp(array $attributes): void
    {
        ClickUp::lists()->update((string) $this->lid, array_filter([
            'name' => $attributes['name'] ?? $this->name,
            'content' => $attributes['content'] ?? null,
        ], filled(...)));

        $this->update($attributes);
    }

    public function deleteInClickUp(): void
    {
        ClickUp::lists()->delete((string) $this->lid);

        $this->delete();
    }
}
