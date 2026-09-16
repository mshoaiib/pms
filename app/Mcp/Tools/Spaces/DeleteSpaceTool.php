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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description(
    'Delete a Space in ClickUp. This removes every Project, Version, List and Task inside it, '.
    'and cannot be undone. Confirm with the user before calling it.'
)]
#[IsDestructive]
#[IsOpenWorld]
#[Name('delete-space')]
class DeleteSpaceTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'space_id' => ['required', 'string'],
        ], [
            'space_id.required' => 'You must pass the ClickUp Space id to delete.',
        ]);

        $space = $this->space($validated['space_id']);

        if ($space === null) {
            return $this->unknown('Space', $validated['space_id'], 'Call list-spaces to see the known ids.');
        }

        $name = $space->name;
        $projects = $space->folders()->count();

        try {
            $space->deleteInClickUp();
        } catch (ClickUpException $exception) {
            return $this->rejected('Space', $exception);
        }

        return Response::structured([
            'deleted' => 'space',
            'space_id' => $validated['space_id'],
            'name' => $name,
            'projects_removed' => $projects,
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
            'space_id' => $schema
                ->string()
                ->description('The ClickUp Space id to delete, with everything inside it.')
                ->required(),
        ];
    }
}
