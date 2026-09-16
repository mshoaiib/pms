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

#[Description(
    'Create a Space inside a ClickUp Workspace. A Space is the top level of the hierarchy: '.
    'Space > Project > Version > List > Task. Returns the space_id to pass to create-project.'
)]
#[IsOpenWorld]
#[Name('create-space')]
class CreateSpaceTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'multiple_assignees' => ['boolean'],
        ], [
            'workspace_id.required' => 'You must pass the ClickUp Workspace id the Space belongs to.',
            'name.required' => 'You must give the Space a name.',
        ]);

        $workspace = $this->workspace($validated['workspace_id']);

        if ($workspace === null) {
            return $this->unknown('Workspace', $validated['workspace_id'], 'Call list-workspaces to see the known ids.');
        }

        try {
            $space = $workspace->createSpaceInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Space', $exception);
        }

        return Response::structured($this->spaceData($space));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workspace_id' => $schema
                ->string()
                ->description('The ClickUp Workspace (team) id the Space is created in.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The name of the Space, for example "Client Projects".')
                ->required(),

            'multiple_assignees' => $schema
                ->boolean()
                ->description('Whether tasks in this Space may have more than one assignee.')
                ->default(true),
        ];
    }
}
