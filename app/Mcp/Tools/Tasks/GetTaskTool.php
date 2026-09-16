<?php

namespace App\Mcp\Tools\Tasks;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one Task in full, including its description and where it sits in the hierarchy.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('get-task')]
class GetTaskTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'task_id' => ['required', 'string'],
        ], [
            'task_id.required' => 'You must pass the ClickUp Task id to read.',
        ]);

        $task = $this->task($validated['task_id']);

        if ($task === null) {
            return $this->unknown('Task', $validated['task_id'], 'Call list-tasks to see the known ids.');
        }

        $version = $task->taskList->folder;

        return Response::structured([
            ...$this->taskData($task),
            'description' => $task->description,
            'version_id' => (string) $version->fid,
            'project_id' => (string) $version->parentFolder?->fid,
            'parent_task_id' => $task->parent_tid,
            'subtasks' => $task->subtasks()->count(),
            'status_type' => $task->status_type,
            'is_done' => $task->isDone(),
            'creator' => $task->creator['username'] ?? null,
            'watchers' => collect($task->watchers ?? [])->pluck('username')->filter()->values()->all(),
            'tags' => collect($task->tags ?? [])->pluck('name')->filter()->values()->all(),
            'checklists' => $task->checklists,
            'custom_fields' => collect($task->custom_fields ?? [])
                ->map(fn (array $field): array => [
                    'name' => $field['name'] ?? null,
                    'value' => $field['value'] ?? null,
                ])
                ->all(),
            'dependencies' => count($task->dependencies ?? []),
            'linked_tasks' => count($task->linked_tasks ?? []),
            'attachments' => count($task->attachments ?? []),
            'points' => $task->points,
            'time_estimate_ms' => $task->time_estimate,
            'time_spent_ms' => $task->time_spent,
            'created_in_clickup_at' => $task->clickup_created_at?->toIso8601String(),
            'updated_in_clickup_at' => $task->clickup_updated_at?->toIso8601String(),
            'done_at' => $task->done_at?->toIso8601String(),
            'closed_at' => $task->closed_at?->toIso8601String(),
            'archived' => (bool) $task->archived,
            'synced_at' => $task->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema
                ->string()
                ->description('The ClickUp Task id to read.')
                ->required(),
        ];
    }
}
