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

#[Description(
    'Create a Project inside a Space. A Project is a ClickUp Folder, and holds one Version '.
    'sub folder per release. Returns the project_id to pass to create-version.'
)]
#[IsOpenWorld]
#[Name('create-project')]
class CreateProjectTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'space_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'space_id.required' => 'You must pass the ClickUp Space id the Project belongs to.',
            'name.required' => 'You must give the Project a name.',
        ]);

        $space = $this->space($validated['space_id']);

        if ($space === null) {
            return $this->unknown('Space', $validated['space_id'], 'Create it with create-space, or call list-spaces.');
        }

        try {
            $project = $space->createFolderInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Project', $exception);
        }

        return Response::structured($this->projectData($project));
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
                ->description('The ClickUp Space id the Project is created in.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The name of the Project, for example "PMS" or "Acme Website".')
                ->required(),
        ];
    }
}
