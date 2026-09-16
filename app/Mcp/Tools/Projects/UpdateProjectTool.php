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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description('Rename a Project. ClickUp Folders carry no other editable field.')]
#[IsOpenWorld]
#[Name('update-project')]
class UpdateProjectTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'project_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'project_id.required' => 'You must pass the ClickUp Folder id of the Project to update.',
            'name.required' => 'You must pass the new name for the Project.',
        ]);

        $project = $this->project($validated['project_id']);

        if ($project === null) {
            return $this->unknown('Project', $validated['project_id'], 'Call list-projects to see the known ids.');
        }

        try {
            $project->updateInClickUp(['name' => $validated['name']]);
        } catch (ClickUpException $exception) {
            return $this->rejected('Project', $exception);
        }

        return Response::structured($this->projectData($project->fresh()));
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
                ->description('The ClickUp Folder id of the Project to rename.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The new name for the Project.')
                ->required(),
        ];
    }
}
