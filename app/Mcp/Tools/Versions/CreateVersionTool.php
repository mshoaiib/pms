<?php

namespace App\Mcp\Tools\Versions;

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
    'Create a Version inside a Project. A Version is a ClickUp sub folder such as "v1.0" or '.
    '"Release 2", and holds the Lists for that release. Returns the version_id to pass to create-list.'
)]
#[IsOpenWorld]
#[Name('create-version')]
class CreateVersionTool extends Tool
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
            'project_id.required' => 'You must pass the ClickUp Folder id of the Project this Version belongs to.',
            'name.required' => 'You must name the Version, for example "v1.0".',
        ]);

        $project = $this->project($validated['project_id']);

        if ($project === null) {
            return $this->unknown(
                'Project',
                $validated['project_id'],
                'Create it with create-project, or call list-projects. A Version cannot hold another Version.'
            );
        }

        try {
            $version = $project->createVersionInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Version', $exception);
        }

        return Response::structured($this->versionData($version));
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
                ->description('The ClickUp Folder id of the Project the Version is created under.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The name of the Version, for example "v1.0", "v2.0", or "Release 1".')
                ->required(),
        ];
    }
}
