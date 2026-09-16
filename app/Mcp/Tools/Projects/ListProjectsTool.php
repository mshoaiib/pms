<?php

namespace App\Mcp\Tools\Projects;

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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the Projects of a Space, as of the last sync. Versions are not included; call list-versions.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('list-projects')]
class ListProjectsTool extends Tool
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
            'space_id.required' => 'You must pass the ClickUp Space id whose Projects you want.',
        ]);

        $space = $this->space($validated['space_id']);

        if ($space === null) {
            return $this->unknown('Space', $validated['space_id'], 'Call list-spaces to see the known ids.');
        }

        $projects = $space->folders()
            ->withCount('versions')
            ->orderBy('orderindex')
            ->get()
            ->map(fn (Folder $project): array => $this->projectData($project));

        return Response::structured([
            'space_id' => (string) $space->sid,
            'projects' => $projects->all(),
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
                ->description('The ClickUp Space id whose Projects should be listed.')
                ->required(),
        ];
    }
}
