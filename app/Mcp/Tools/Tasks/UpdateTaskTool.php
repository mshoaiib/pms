<?php

namespace App\Mcp\Tools\Tasks;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use App\Models\Folder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description(
    'Change a Task: rename it, rewrite its description, move its status, set a priority or a due '.
    'date. Only the fields you pass are changed. Use this to mark work done by setting the status.'
)]
#[IsOpenWorld]
#[Name('update-task')]
class UpdateTaskTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'task_id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'integer', 'between:1,4'],
            'due_date' => ['sometimes', 'nullable', 'integer'],
        ], [
            'task_id.required' => 'You must pass the ClickUp Task id to update.',
            'priority.between' => 'Priority must be 1 (Urgent), 2 (High), 3 (Normal), or 4 (Low).',
        ]);

        $task = $this->task($validated['task_id']);

        if ($task === null) {
            return $this->unknown('Task', $validated['task_id'], 'Call list-tasks to see the known ids.');
        }

        unset($validated['task_id']);

        if ($validated === []) {
            return Response::error('Pass at least one field to change: name, description, status, priority, or due_date.');
        }

        if (isset($validated['due_date'])) {
            $validated['due_date'] = Folder::timestampFromClickUp($validated['due_date']);
        }

        try {
            $task->updateInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Task', $exception);
        }

        return Response::structured($this->taskData($task->fresh()));
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
                ->description('The ClickUp Task id to update.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('A new name for the Task.'),

            'description' => $schema
                ->string()
                ->description('A new description for the Task.'),

            'status' => $schema
                ->string()
                ->description('A status configured on the Space, for example "in progress" or "complete".'),

            'priority' => $schema
                ->integer()
                ->description('ClickUp priority: 1 = Urgent, 2 = High, 3 = Normal, 4 = Low.'),

            'due_date' => $schema
                ->integer()
                ->description('Due date as a Unix timestamp in milliseconds.'),
        ];
    }
}
