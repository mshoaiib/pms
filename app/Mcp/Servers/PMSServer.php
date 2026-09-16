<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Lists\CreateListTool;
use App\Mcp\Tools\Lists\DeleteListTool;
use App\Mcp\Tools\Lists\GetListTool;
use App\Mcp\Tools\Lists\ListListsTool;
use App\Mcp\Tools\Lists\UpdateListTool;
use App\Mcp\Tools\Projects\CreateProjectTool;
use App\Mcp\Tools\Projects\DeleteProjectTool;
use App\Mcp\Tools\Projects\GetProjectTool;
use App\Mcp\Tools\Projects\ListProjectsTool;
use App\Mcp\Tools\Projects\UpdateProjectTool;
use App\Mcp\Tools\Spaces\CreateSpaceTool;
use App\Mcp\Tools\Spaces\DeleteSpaceTool;
use App\Mcp\Tools\Spaces\GetSpaceTool;
use App\Mcp\Tools\Spaces\ListSpacesTool;
use App\Mcp\Tools\Spaces\UpdateSpaceTool;
use App\Mcp\Tools\Tasks\CreateTaskTool;
use App\Mcp\Tools\Tasks\DeleteTaskTool;
use App\Mcp\Tools\Tasks\GetTaskTool;
use App\Mcp\Tools\Tasks\ListTasksTool;
use App\Mcp\Tools\Tasks\UpdateTaskTool;
use App\Mcp\Tools\Versions\CreateVersionTool;
use App\Mcp\Tools\Versions\DeleteVersionTool;
use App\Mcp\Tools\Versions\GetVersionTool;
use App\Mcp\Tools\Versions\ListVersionsTool;
use App\Mcp\Tools\Versions\UpdateVersionTool;
use App\Mcp\Tools\Workspaces\ListWorkspacesTool;
use App\Mcp\Tools\Workspaces\SyncWorkspaceTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('PMS Server')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
This server plans work in ClickUp through a fixed five level hierarchy:

1. **Space** - the client or business area, created inside a ClickUp Workspace.
2. **Project** - a ClickUp Folder inside a Space.
3. **Version** - a ClickUp sub folder inside a Project, one per release ("v1.0", "Release 2").
4. **List** - a work category inside a Version, named after the discipline doing the work
   (Graphic Designer, Developers, SQA).
5. **Task** - a single work item inside a List.

Every level has create, list, get, update and delete tools, named after the level:
`create-project`, `list-projects`, `get-project`, `update-project`, `delete-project`, and so on.

Start with `list-workspaces` to find a workspace_id, then walk down one level at a time: each
create tool returns the id its child tool needs, and each list tool returns the ids below it.
A Version always belongs to a Project, and a List always belongs to a Version, never directly
to a Project or Space.

Reads come from this application's mirror of ClickUp, which is synced on a schedule, so they
can be up to an hour behind. Call `sync-workspace` to refresh it, and after any change made
outside this server.

Writes go to ClickUp first and are then mirrored locally, so a parent must already exist here.
If a tool reports an unknown id, sync the workspace or create the parent first.

The delete tools remove the record in ClickUp along with everything inside it, and cannot be
undone. Confirm with the user before calling one. To close work instead of destroying it, use
`update-task` to move the task's status.
MARKDOWN)]
class PMSServer extends Server
{
    /**
     * Return every tool on the first page of tools/list. The package default of
     * 15 would split this server's tools across pages, and a client that does
     * not follow the cursor would never see the List and Task tools.
     */
    public int $defaultPaginationLength = 50;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListWorkspacesTool::class,
        SyncWorkspaceTool::class,

        CreateSpaceTool::class,
        ListSpacesTool::class,
        GetSpaceTool::class,
        UpdateSpaceTool::class,
        DeleteSpaceTool::class,

        CreateProjectTool::class,
        ListProjectsTool::class,
        GetProjectTool::class,
        UpdateProjectTool::class,
        DeleteProjectTool::class,

        CreateVersionTool::class,
        ListVersionsTool::class,
        GetVersionTool::class,
        UpdateVersionTool::class,
        DeleteVersionTool::class,

        CreateListTool::class,
        ListListsTool::class,
        GetListTool::class,
        UpdateListTool::class,
        DeleteListTool::class,

        CreateTaskTool::class,
        ListTasksTool::class,
        GetTaskTool::class,
        UpdateTaskTool::class,
        DeleteTaskTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
