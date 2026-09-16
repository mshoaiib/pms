<?php

namespace App\Mcp\Tools\Workspaces;

use App\Models\Workspace;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(
    'List the ClickUp Workspaces this application knows about. Start here: every other tool '.
    'needs an id that hangs off one of these Workspaces.'
)]
#[IsReadOnly]
#[IsIdempotent]
#[Name('list-workspaces')]
class ListWorkspacesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $workspaces = Workspace::query()
            ->withCount('spaces')
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace): array => [
                'workspace_id' => (string) $workspace->wid,
                'name' => $workspace->name,
                'spaces' => $workspace->spaces_count,
            ]);

        if ($workspaces->isEmpty()) {
            return Response::error(
                'This application has no Workspaces yet. A user has to register one in the admin panel first.'
            );
        }

        return Response::structured(['workspaces' => $workspaces->all()]);
    }
}
