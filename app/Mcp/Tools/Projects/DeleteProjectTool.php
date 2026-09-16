<?php

namespace App\Mcp\Tools\Projects;

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
    'Delete a Project in ClickUp, with every Version, List and Task inside it. '.
    'This cannot be undone. Confirm with the user before calling it.'
)]
#[IsDestructive]
#[IsOpenWorld]
#[Name('delete-project')]
class DeleteProjectTool extends Tool
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
            'project_id.required' => 'You must pass the ClickUp Folder id of the Project to delete.',
        ]);

        $project = $this->project($validated['project_id']);

        if ($project === null) {
            return $this->unknown('Project', $validated['project_id'], 'Call list-projects to see the known ids.');
        }

        $name = $project->name;
        $versions = $project->versions()->count();

        try {
            $project->deleteInClickUp();
        } catch (ClickUpException $exception) {
            return $this->rejected('Project', $exception);
        }

        return Response::structured([
            'deleted' => 'project',
            'project_id' => $validated['project_id'],
            'name' => $name,
            'versions_removed' => $versions,
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
                ->description('The ClickUp Folder id of the Project to delete, with everything inside it.')
                ->required(),
        ];
    }
}
