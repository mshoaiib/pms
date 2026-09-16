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

#[Description('Rename a Version, for example from "v1.0" to "v1.0 (shipped)".')]
#[IsOpenWorld]
#[Name('update-version')]
class UpdateVersionTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'version_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'version_id.required' => 'You must pass the ClickUp Folder id of the Version to update.',
            'name.required' => 'You must pass the new name for the Version.',
        ]);

        $version = $this->version($validated['version_id']);

        if ($version === null) {
            return $this->unknown('Version', $validated['version_id'], 'Call list-versions to see the known ids.');
        }

        try {
            $version->updateInClickUp(['name' => $validated['name']]);
        } catch (ClickUpException $exception) {
            return $this->rejected('Version', $exception);
        }

        return Response::structured($this->versionData($version->fresh()));
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
                ->description('The ClickUp Folder id of the Version to rename.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The new name for the Version.')
                ->required(),
        ];
    }
}
