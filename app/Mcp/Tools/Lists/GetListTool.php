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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one List and where it sits in the hierarchy. Use list-tasks for its Tasks.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('get-list')]
class GetListTool extends Tool
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
            'list_id.required' => 'You must pass the ClickUp List id to read.',
        ]);

        $list = $this->taskList($validated['list_id']);

        if ($list === null) {
            return $this->unknown('List', $validated['list_id'], 'Call list-lists to see the known ids.');
        }

        return Response::structured([
            ...$this->listData($list),
            'project_id' => (string) $list->folder->parentFolder?->fid,
            'space_id' => (string) $list->space->sid,
            'status' => $list->status,
            'priority' => $list->priority,
            'due_date' => $list->due_date?->toIso8601String(),
            'synced_at' => $list->updated_at?->toIso8601String(),
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
                ->description('The ClickUp List id to read.')
                ->required(),
        ];
    }
}
