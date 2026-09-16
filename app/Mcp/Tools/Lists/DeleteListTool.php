<?php

namespace App\Mcp\Tools\Lists;

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
    'Delete a List in ClickUp, with every Task inside it. This cannot be undone. '.
    'Confirm with the user before calling it.'
)]
#[IsDestructive]
#[IsOpenWorld]
#[Name('delete-list')]
class DeleteListTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'list_id' => ['required', 'string'],
        ], [
            'list_id.required' => 'You must pass the ClickUp List id to delete.',
        ]);

        $list = $this->taskList($validated['list_id']);

        if ($list === null) {
            return $this->unknown('List', $validated['list_id'], 'Call list-lists to see the known ids.');
        }

        $name = $list->name;
        $tasks = $list->tasks()->count();

        try {
            $list->deleteInClickUp();
        } catch (ClickUpException $exception) {
            return $this->rejected('List', $exception);
        }

        return Response::structured([
            'deleted' => 'list',
            'list_id' => $validated['list_id'],
            'name' => $name,
            'tasks_removed' => $tasks,
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
            'list_id' => $schema
                ->string()
                ->description('The ClickUp List id to delete, with every Task inside it.')
                ->required(),
        ];
    }
}
