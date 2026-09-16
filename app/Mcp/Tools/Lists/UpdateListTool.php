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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description('Rename a List or change its description. Only the fields you pass are changed.')]
#[IsOpenWorld]
#[Name('update-list')]
class UpdateListTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'list_id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string'],
        ], [
            'list_id.required' => 'You must pass the ClickUp List id to update.',
        ]);

        $list = $this->taskList($validated['list_id']);

        if ($list === null) {
            return $this->unknown('List', $validated['list_id'], 'Call list-lists to see the known ids.');
        }

        unset($validated['list_id']);

        if ($validated === []) {
            return Response::error('Pass at least one field to change: name or content.');
        }

        try {
            $list->updateInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('List', $exception);
        }

        return Response::structured($this->listData($list->fresh()));
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
                ->description('The ClickUp List id to update.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('A new name for the List.'),

            'content' => $schema
                ->string()
                ->description('A new description for the List.'),
        ];
    }
}
