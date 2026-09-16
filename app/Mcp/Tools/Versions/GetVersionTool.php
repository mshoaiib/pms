<?php

namespace App\Mcp\Tools\Versions;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use App\Models\TaskList;
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

#[Description('Read one Version and the Lists inside it, as of the last sync.')]
#[IsReadOnly]
#[IsIdempotent]
#[Name('get-version')]
class GetVersionTool extends Tool
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
            'version_id.required' => 'You must pass the ClickUp Folder id of the Version to read.',
        ]);

        $version = $this->version($validated['version_id']);

        if ($version === null) {
            return $this->unknown('Version', $validated['version_id'], 'Call list-versions to see the known ids.');
        }

        $lists = $version->taskLists()
            ->withCount('tasks')
            ->orderBy('orderindex')
            ->get()
            ->map(fn (TaskList $list): array => $this->listData($list));

        return Response::structured([
            ...$this->versionData($version),
            'space_id' => (string) $version->space->sid,
            'synced_at' => $version->updated_at?->toIso8601String(),
            'lists_detail' => $lists->all(),
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
                ->description('The ClickUp Folder id of the Version to read.')
                ->required(),
        ];
    }
}
