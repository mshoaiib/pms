<?php

namespace App\Mcp\Tools\Spaces;

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

#[Description('Rename a Space or change its privacy. Only the fields you pass are changed.')]
#[IsOpenWorld]
#[Name('update-space')]
class UpdateSpaceTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'space_id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'private' => ['sometimes', 'boolean'],
            'multiple_assignees' => ['sometimes', 'boolean'],
        ], [
            'space_id.required' => 'You must pass the ClickUp Space id to update.',
        ]);

        $space = $this->space($validated['space_id']);

        if ($space === null) {
            return $this->unknown('Space', $validated['space_id'], 'Call list-spaces to see the known ids.');
        }

        unset($validated['space_id']);

        if ($validated === []) {
            return Response::error('Pass at least one field to change: name, private, or multiple_assignees.');
        }

        try {
            $space->updateInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Space', $exception);
        }

        return Response::structured($this->spaceData($space->fresh()));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'space_id' => $schema
                ->string()
                ->description('The ClickUp Space id to update.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('A new name for the Space.'),

            'private' => $schema
                ->boolean()
                ->description('Whether the Space is only visible to its members.'),

            'multiple_assignees' => $schema
                ->boolean()
                ->description('Whether tasks in this Space may have more than one assignee.'),
        ];
    }
}
