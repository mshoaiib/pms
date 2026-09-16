<?php

namespace App\Mcp\Tools\Spaces;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use App\Models\Space;
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

#[Description('List the Spaces of a Workspace, as of the last sync.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('list-spaces')]
class ListSpacesTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
        ], [
            'workspace_id.required' => 'You must pass the ClickUp Workspace id. Call list-workspaces to see them.',
        ]);

        $workspace = $this->workspace($validated['workspace_id']);

        if ($workspace === null) {
            return $this->unknown('Workspace', $validated['workspace_id'], 'Call list-workspaces to see the known ids.');
        }

        $spaces = $workspace->spaces()
            ->withCount('folders')
            ->orderBy('name')
            ->get()
            ->map(fn (Space $space): array => $this->spaceData($space));

        return Response::structured([
            'workspace_id' => (string) $workspace->wid,
            'spaces' => $spaces->all(),
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
            'workspace_id' => $schema
                ->string()
                ->description('The ClickUp Workspace id whose Spaces should be listed.')
                ->required(),
        ];
    }
}
