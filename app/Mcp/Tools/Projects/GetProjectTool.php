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

#[Description('Read one Project and the Versions inside it, as of the last sync.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('get-project')]
class GetProjectTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_id' => ['required', 'string'],
        ], [
            'project_id.required' => 'You must pass the ClickUp Folder id of the Project to read.',
        ]);

        $project = $this->project($validated['project_id']);

        if ($project === null) {
            return $this->unknown('Project', $validated['project_id'], 'Call list-projects to see the known ids.');
        }

        $versions = $project->versions()
            ->withCount('taskLists')
            ->orderBy('orderindex')
            ->get()
            ->map(fn (Folder $version): array => $this->versionData($version));

        return Response::structured([
            ...$this->projectData($project),
            'synced_at' => $project->updated_at?->toIso8601String(),
            'versions_detail' => $versions->all(),
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
            'project_id' => $schema
                ->string()
                ->description('The ClickUp Folder id of the Project to read.')
                ->required(),
        ];
    }
}
