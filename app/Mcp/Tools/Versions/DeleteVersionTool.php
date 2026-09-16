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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description(
    'Delete a Version in ClickUp, with every List and Task inside it. This cannot be undone. '.
    'Confirm with the user before calling it.'
)]
#[IsDestructive]
#[IsOpenWorld]
#[Name('delete-version')]
class DeleteVersionTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'version_id' => ['required', 'string'],
        ], [
            'version_id.required' => 'You must pass the ClickUp Folder id of the Version to delete.',
        ]);

        $version = $this->version($validated['version_id']);

        if ($version === null) {
            return $this->unknown('Version', $validated['version_id'], 'Call list-versions to see the known ids.');
        }

        $name = $version->name;
        $lists = $version->taskLists()->count();

        try {
            $version->deleteInClickUp();
        } catch (ClickUpException $exception) {
            return $this->rejected('Version', $exception);
        }

        return Response::structured([
            'deleted' => 'version',
            'version_id' => $validated['version_id'],
            'name' => $name,
            'lists_removed' => $lists,
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
            'version_id' => $schema
                ->string()
                ->description('The ClickUp Folder id of the Version to delete, with everything inside it.')
                ->required(),
        ];
    }
}
