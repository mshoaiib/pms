<?php

use App\Mcp\Servers\PMSServer;
use App\Mcp\Tools\Lists\CreateListTool;
use App\Mcp\Tools\Projects\CreateProjectTool;
use App\Mcp\Tools\Spaces\CreateSpaceTool;
use App\Mcp\Tools\Tasks\CreateTaskTool;
use App\Mcp\Tools\Versions\CreateVersionTool;
use App\Models\Folder;
use App\Models\Space;
use App\Models\TaskList;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maya719\ClickUp\Exceptions\ValidationException;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Folders;
use Maya719\ClickUp\Resources\Lists;
use Maya719\ClickUp\Resources\Spaces;
use Maya719\ClickUp\Resources\Tasks;

uses(RefreshDatabase::class);

it('creates a space in clickup and mirrors it locally', function () {
    $workspace = Workspace::factory()->create(['wid' => 9001]);

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('create')
        ->once()
        ->with('9001', ['name' => 'Client Projects', 'multiple_assignees' => true])
        ->andReturn(['id' => '90010000000', 'name' => 'Client Projects']);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);

    PMSServer::tool(CreateSpaceTool::class, [
        'workspace_id' => '9001',
        'name' => 'Client Projects',
    ])->assertOk()->assertSee('90010000000');

    expect($workspace->spaces()->sole()->name)->toBe('Client Projects');
});

it('creates a project as a folder in the space', function () {
    $space = Space::factory()->create(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('create')
        ->once()
        ->with('90010000000', ['name' => 'PMS'])
        ->andReturn(['id' => '901816713501', 'name' => 'PMS']);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(CreateProjectTool::class, [
        'space_id' => '90010000000',
        'name' => 'PMS',
    ])->assertOk()->assertSee('901816713501');

    expect((string) $space->folders()->sole()->fid)->toBe('901816713501');
});

it('creates a version as a sub folder of the project', function () {
    $project = Folder::factory()->create(['fid' => 901816713501]);
    $project->space->update(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('create')
        ->once()
        ->with('90010000000', ['name' => 'v1.0', 'parent_folder_id' => '901816713501'])
        ->andReturn(['id' => '901817207336', 'name' => 'v1.0']);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(CreateVersionTool::class, [
        'project_id' => '901816713501',
        'name' => 'v1.0',
    ])->assertOk()->assertSee('901817207336');

    expect($project->versions()->sole()->isVersion())->toBeTrue();
});

it('refuses to hang a version off another version', function () {
    Folder::factory()->version()->create(['fid' => 901817207336]);

    PMSServer::tool(CreateVersionTool::class, [
        'project_id' => '901817207336',
        'name' => 'v2.0',
    ])->assertHasErrors();
});

it('creates a list inside a version', function () {
    $version = Folder::factory()->version()->create(['fid' => 901817207336]);

    $lists = Mockery::mock(Lists::class);
    $lists->shouldReceive('create')
        ->once()
        ->with('901817207336', ['name' => 'Developers'])
        ->andReturn(['id' => '901821115796', 'name' => 'Developers']);

    ClickUp::shouldReceive('lists')->andReturn($lists);

    PMSServer::tool(CreateListTool::class, [
        'version_id' => '901817207336',
        'name' => 'Developers',
    ])->assertOk()->assertSee('901821115796');

    expect($version->taskLists()->sole()->name)->toBe('Developers');
});

it('refuses to put a list directly in a project', function () {
    Folder::factory()->create(['fid' => 901816713501]);

    PMSServer::tool(CreateListTool::class, [
        'version_id' => '901816713501',
        'name' => 'Developers',
    ])->assertHasErrors();
});

it('creates a task inside a list', function () {
    $list = TaskList::factory()->create(['lid' => 901821115796]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('create')
        ->once()
        ->with('901821115796', [
            'name' => 'Build the login screen',
            'description' => 'Email and password only.',
            'priority' => 2,
            'assignees' => [183],
        ])
        ->andReturn([
            'id' => 'abc123',
            'name' => 'Build the login screen',
            'url' => 'https://app.clickup.com/t/abc123',
        ]);

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    PMSServer::tool(CreateTaskTool::class, [
        'list_id' => '901821115796',
        'name' => 'Build the login screen',
        'description' => 'Email and password only.',
        'priority' => 2,
        'assignees' => [183],
    ])->assertOk()->assertSee('abc123');

    expect($list->tasks()->sole()->name)->toBe('Build the login screen');
});

it('tells the agent when the parent has never been synced', function () {
    PMSServer::tool(CreateProjectTool::class, [
        'space_id' => '90010000000',
        'name' => 'PMS',
    ])->assertHasErrors();
});

it('reports what clickup rejected', function () {
    Space::factory()->create(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('create')->andThrow(new ValidationException('Folder name invalid.'));

    ClickUp::shouldReceive('folders')->andReturn($folders);

    PMSServer::tool(CreateProjectTool::class, [
        'space_id' => '90010000000',
        'name' => 'PMS',
    ])->assertHasErrors(['ClickUp rejected the Project: Folder name invalid.']);
});

it('validates the arguments an agent sends', function () {
    PMSServer::tool(CreateTaskTool::class, [
        'list_id' => '901821115796',
        'name' => 'Build the login screen',
        'priority' => 9,
    ])->assertHasErrors();
});
