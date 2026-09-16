<?php

namespace App\Mcp\Tools\Workspaces;

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
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Maya719\ClickUp\Exceptions\ClickUpException;

#[Description(
    'Pull a Workspace down from ClickUp: its Spaces, Projects, Versions and Lists. Use this when '.
    'a tool reports an unknown id, or when work was created in ClickUp itself. Tasks are only '.
    'refreshed when with_tasks is true, which costs one request per List.'
)]
#[IsOpenWorld]
#[IsIdempotent]
#[Name('sync-workspace')]
class SyncWorkspaceTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'string'],
            'with_tasks' => ['boolean'],
        ], [
            'workspace_id.required' => 'You must pass the ClickUp Workspace id to sync. Call list-workspaces to see them.',
        ]);

        $workspace = $this->workspace($validated['workspace_id']);

        if ($workspace === null) {
            return $this->unknown('Workspace', $validated['workspace_id'], 'Call list-workspaces to see the known ids.');
        }

        try {
            $spaces = $workspace->syncSpacesFromClickUp();
            $tasks = 0;

            foreach ($workspace->spaces()->get() as $space) {
                $space->syncFoldersFromClickUp();

                if ($validated['with_tasks'] ?? false) {
                    $tasks += $this->syncTasksOf($space);
                }
            }
        } catch (ClickUpException $exception) {
            return $this->rejected('sync', $exception);
        }

        return Response::structured([
            'workspace_id' => (string) $workspace->wid,
            'spaces' => $spaces,
            'projects' => $workspace->spaces()->withCount('folders')->get()->sum('folders_count'),
            'lists' => $workspace->spaces()->withCount('taskLists')->get()->sum('task_lists_count'),
            'tasks' => $tasks,
        ]);
    }

    private function syncTasksOf(Space $space): int
    {
        $tasks = 0;

        foreach ($space->taskLists()->get() as $list) {
            $tasks += $list->syncTasksFromClickUp();
        }

        return $tasks;
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
                ->description('The ClickUp Workspace id to pull down.')
                ->required(),

            'with_tasks' => $schema
                ->boolean()
                ->description('Also refresh the Tasks of every List. Slower, one request per List.')
                ->default(false),
        ];
    }
}
