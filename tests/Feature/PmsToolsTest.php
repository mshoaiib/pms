<?php

use App\Mcp\Servers\PMSServer;
use App\Mcp\Tools\Lists\DeleteListTool;
use App\Mcp\Tools\Lists\GetListTool;
use App\Mcp\Tools\Lists\ListListsTool;
use App\Mcp\Tools\Lists\UpdateListTool;
use App\Mcp\Tools\Projects\DeleteProjectTool;
use App\Mcp\Tools\Projects\GetProjectTool;
use App\Mcp\Tools\Projects\ListProjectsTool;
use App\Mcp\Tools\Projects\UpdateProjectTool;
use App\Mcp\Tools\Spaces\DeleteSpaceTool;
use App\Mcp\Tools\Spaces\GetSpaceTool;
use App\Mcp\Tools\Spaces\ListSpacesTool;
use App\Mcp\Tools\Spaces\UpdateSpaceTool;
use App\Mcp\Tools\Tasks\DeleteTaskTool;
use App\Mcp\Tools\Tasks\GetTaskTool;
use App\Mcp\Tools\Tasks\ListTasksTool;
use App\Mcp\Tools\Tasks\UpdateTaskTool;
use App\Mcp\Tools\Versions\DeleteVersionTool;
use App\Mcp\Tools\Versions\GetVersionTool;
use App\Mcp\Tools\Versions\ListVersionsTool;
use App\Mcp\Tools\Versions\UpdateVersionTool;
use App\Mcp\Tools\Workspaces\ListWorkspacesTool;
use App\Mcp\Tools\Workspaces\SyncWorkspaceTool;
use App\Models\Folder;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Folders;
use Maya719\ClickUp\Resources\Lists;
use Maya719\ClickUp\Resources\Spaces;
use Maya719\ClickUp\Resources\Tasks;

uses(RefreshDatabase::class);

/**
 * One Workspace with a Space, a Project, a Version, a List and a Task.
 *
 * @return array{workspace: Workspace, space: Space, project: Folder, version: Folder, list: TaskList, task: Task}
 */
function pmsTree(): array
{
    $workspace = Workspace::factory()->create(['wid' => 9001]);
    $space = Space::factory()->create(['workspace_id' => $workspace->id, 'sid' => 90010000000]);
    $project = Folder::factory()->create(['space_id' => $space->id, 'fid' => 901816713501, 'name' => 'PMS']);
    $version = Folder::factory()->version($project)->create(['fid' => 901817207336, 'name' => 'v1.0']);
    $list = TaskList::factory()->create([
        'folder_id' => $version->id,
        'space_id' => $space->id,
        'lid' => 901821115796,
        'name' => 'Developers',
    ]);
    $task = Task::factory()->create([
        'task_list_id' => $list->id,
        'tid' => 'abc123',
        'name' => 'Build the login screen',
        'status' => 'in progress',
        'priority' => 2,
    ]);

    return compact('workspace', 'space', 'project', 'version', 'list', 'task');
}

it('lists the workspaces to start from', function () {
    pmsTree();

    PMSServer::tool(ListWorkspacesTool::class)
        ->assertOk()
        ->assertSee('9001');
});

it('says so when no workspace has been registered', function () {
    PMSServer::tool(ListWorkspacesTool::class)->assertHasErrors();
});

it('walks down the hierarchy with the list tools', function () {
    $tree = pmsTree();

    PMSServer::tool(ListSpacesTool::class, ['workspace_id' => '9001'])
        ->assertOk()
        ->assertSee((string) $tree['space']->sid);

    PMSServer::tool(ListProjectsTool::class, ['space_id' => '90010000000'])
        ->assertOk()
        ->assertSee('PMS');

    PMSServer::tool(ListVersionsTool::class, ['project_id' => '901816713501'])
        ->assertOk()
        ->assertSee('v1.0');

    PMSServer::tool(ListListsTool::class, ['version_id' => '901817207336'])
        ->assertOk()
        ->assertSee('Developers');

    PMSServer::tool(ListTasksTool::class, ['list_id' => '901821115796'])
        ->assertOk()
        ->assertSee('Build the login screen');
});

it('reads one record at every level', function () {
    pmsTree();

    PMSServer::tool(GetSpaceTool::class, ['space_id' => '90010000000'])->assertOk()->assertSee('PMS');
    PMSServer::tool(GetProjectTool::class, ['project_id' => '901816713501'])->assertOk()->assertSee('v1.0');
    PMSServer::tool(GetVersionTool::class, ['version_id' => '901817207336'])->assertOk()->assertSee('Developers');
    PMSServer::tool(GetListTool::class, ['list_id' => '901821115796'])->assertOk()->assertSee('901816713501');
    PMSServer::tool(GetTaskTool::class, ['task_id' => 'abc123'])->assertOk()->assertSee('in progress');
});

it('will not read a version through the project tools', function () {
    pmsTree();

    PMSServer::tool(GetProjectTool::class, ['project_id' => '901817207336'])->assertHasErrors();
    PMSServer::tool(GetVersionTool::class, ['version_id' => '901816713501'])->assertHasErrors();
});

it('filters and caps the task list', function () {
    $tree = pmsTree();

    Task::factory()->count(3)->create([
        'task_list_id' => $tree['list']->id,
        'status' => 'to do',
        'priority' => 4,
    ]);

    $response = PMSServer::tool(ListTasksTool::class, [
        'list_id' => '901821115796',
        'status' => 'to do',
    ])->assertOk();

    $response->assertSee('"returned":3');

    PMSServer::tool(ListTasksTool::class, ['list_id' => '901821115796', 'limit' => 1])
        ->assertOk()
        ->assertSee('"total":4');
});

it('renames a space through clickup', function () {
    pmsTree();

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('update')
        ->once()
        ->withArgs(fn (string $id, array $data): bool => $id === '90010000000' && $data['name'] === 'Renamed')
        ->andReturn([]);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);

    PMSServer::tool(UpdateSpaceTool::class, ['space_id' => '90010000000', 'name' => 'Renamed'])
        ->assertOk()
        ->assertSee('Renamed');

    expect(Space::query()->sole()->name)->toBe('Renamed');
});

it('renames a project and a version through clickup', function () {
    pmsTree();

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('update')->twice()->andReturn([]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(UpdateProjectTool::class, ['project_id' => '901816713501', 'name' => 'PMS v2'])->assertOk();
    PMSServer::tool(UpdateVersionTool::class, ['version_id' => '901817207336', 'name' => 'v1.1'])->assertOk();

    expect(Folder::query()->whereNull('parent_id')->sole()->name)->toBe('PMS v2')
        ->and(Folder::query()->whereNotNull('parent_id')->sole()->name)->toBe('v1.1');
});

it('updates a list description', function () {
    pmsTree();

    $lists = Mockery::mock(Lists::class);
    $lists->shouldReceive('update')->once()->andReturn([]);

    ClickUp::shouldReceive('lists')->andReturn($lists);

    PMSServer::tool(UpdateListTool::class, ['list_id' => '901821115796', 'content' => 'Backend work'])
        ->assertOk();

    expect(TaskList::query()->sole()->content)->toBe('Backend work');
});

it('moves a task status', function () {
    pmsTree();

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('update')
        ->once()
        ->withArgs(fn (string $id, array $data): bool => $id === 'abc123' && $data['status'] === 'complete')
        ->andReturn([]);

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    PMSServer::tool(UpdateTaskTool::class, ['task_id' => 'abc123', 'status' => 'complete'])
        ->assertOk()
        ->assertSee('complete');

    expect(Task::query()->sole()->status)->toBe('complete');
});

it('asks for a field when an update would change nothing', function () {
    pmsTree();

    PMSServer::tool(UpdateSpaceTool::class, ['space_id' => '90010000000'])->assertHasErrors();
    PMSServer::tool(UpdateTaskTool::class, ['task_id' => 'abc123'])->assertHasErrors();
});

it('deletes a task in clickup and locally', function () {
    pmsTree();

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('delete')->once()->with('abc123')->andReturn([]);

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    PMSServer::tool(DeleteTaskTool::class, ['task_id' => 'abc123'])
        ->assertOk()
        ->assertSee('deleted');

    expect(Task::query()->count())->toBe(0);
});

it('deletes a list with the tasks it held', function () {
    pmsTree();

    $lists = Mockery::mock(Lists::class);
    $lists->shouldReceive('delete')->once()->with('901821115796')->andReturn([]);

    ClickUp::shouldReceive('lists')->andReturn($lists);

    PMSServer::tool(DeleteListTool::class, ['list_id' => '901821115796'])
        ->assertOk()
        ->assertSee('"tasks_removed":1');

    expect(TaskList::query()->count())->toBe(0)
        ->and(Task::query()->count())->toBe(0);
});

it('deletes a version and leaves its project standing', function () {
    pmsTree();

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('delete')->once()->with('901817207336')->andReturn([]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(DeleteVersionTool::class, ['version_id' => '901817207336'])->assertOk();

    expect(Folder::query()->count())->toBe(1)
        ->and(TaskList::query()->count())->toBe(0);
});

it('deletes a project with its versions', function () {
    pmsTree();

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('delete')->once()->with('901816713501')->andReturn([]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(DeleteProjectTool::class, ['project_id' => '901816713501'])
        ->assertOk()
        ->assertSee('"versions_removed":1');

    expect(Folder::query()->count())->toBe(0);
});

it('deletes a space with everything under it', function () {
    pmsTree();

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('delete')->once()->with('90010000000')->andReturn([]);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);

    PMSServer::tool(DeleteSpaceTool::class, ['space_id' => '90010000000'])->assertOk();

    expect(Space::query()->count())->toBe(0)
        ->and(Folder::query()->count())->toBe(0)
        ->and(TaskList::query()->count())->toBe(0)
        ->and(Task::query()->count())->toBe(0);
});

it('refreshes a workspace on demand', function () {
    pmsTree();

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('all')
        ->once()
        ->with('9001')
        ->andReturn([['id' => '90010000000', 'name' => 'Product']]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')->andReturn([
        ['id' => '901816713501', 'name' => 'PMS', 'lists' => []],
    ]);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);
    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(SyncWorkspaceTool::class, ['workspace_id' => '9001'])
        ->assertOk()
        ->assertSee('"spaces":1');

    expect(Space::query()->sole()->name)->toBe('Product');
});

it('tells the agent to sync when an id is unknown', function () {
    PMSServer::tool(GetTaskTool::class, ['task_id' => 'nope'])
        ->assertHasErrors();

    PMSServer::tool(ListListsTool::class, ['version_id' => '404'])
        ->assertHasErrors();
});
