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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description(
    'Delete a Task in ClickUp. This cannot be undone; to close work instead, use update-task to '.
    'move its status.'
)]
#[IsDestructive]
#[IsOpenWorld]
#[Name('delete-task')]
class DeleteTaskTool extends Tool
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
            'task_id.required' => 'You must pass the ClickUp Task id to delete.',
        ]);

        $task = $this->task($validated['task_id']);

        if ($task === null) {
            return $this->unknown('Task', $validated['task_id'], 'Call list-tasks to see the known ids.');
        }

        $name = $task->name;
        $listId = (string) $task->taskList->lid;

        try {
            $task->deleteInClickUp();
        } catch (ClickUpException $exception) {
            return $this->rejected('Task', $exception);
        }

        return Response::structured([
            'deleted' => 'task',
            'task_id' => $validated['task_id'],
            'name' => $name,
            'list_id' => $listId,
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
                ->description('The ClickUp Task id to delete.')
                ->required(),
        ];
    }
}
