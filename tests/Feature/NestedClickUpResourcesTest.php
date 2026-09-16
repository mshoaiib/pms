<?php

use App\Filament\Resources\Spaces\RelationManagers\FoldersRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\FolderResource;
use App\Filament\Resources\Spaces\Resources\Folders\RelationManagers\VersionsRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\RelationManagers\TaskListsRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\Resources\Tasks\TaskResource;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\Resources\TaskLists\TaskListResource;
use App\Filament\Resources\Spaces\Resources\Folders\Resources\Versions\VersionResource;
use App\Filament\Resources\Spaces\SpaceResource;
use App\Models\Folder;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Folders;
use Maya719\ClickUp\Resources\Lists;
use Maya719\ClickUp\Resources\Tasks;

uses(RefreshDatabase::class);

it('nests folders in spaces, versions in folders, lists in versions and tasks in lists', function () {
    expect(FolderResource::getParentResourceRegistration()->getParentResource())->toBe(SpaceResource::class)
        ->and(FolderResource::getParentResourceRegistration()->getRelationshipName())->toBe('folders')
        ->and(VersionResource::getParentResourceRegistration()->getParentResource())->toBe(FolderResource::class)
        ->and(VersionResource::getParentResourceRegistration()->getRelationshipName())->toBe('versions')
        ->and(VersionResource::getParentResourceRegistration()->getInverseRelationshipName())->toBe('parentFolder')
        ->and(TaskListResource::getParentResourceRegistration()->getParentResource())->toBe(VersionResource::class)
        ->and(TaskListResource::getParentResourceRegistration()->getRelationshipName())->toBe('taskLists')
        ->and(TaskResource::getParentResourceRegistration()->getParentResource())->toBe(TaskListResource::class)
        ->and(TaskResource::getParentResourceRegistration()->getRelationshipName())->toBe('tasks');
});

it('lists each level through the relation manager of its parent', function () {
    expect(SpaceResource::getRelations())->toBe(['folders' => FoldersRelationManager::class])
        ->and(FolderResource::getRelations())->toBe(['versions' => VersionsRelationManager::class])
        ->and(VersionResource::getRelations())->toBe(['taskLists' => TaskListsRelationManager::class])
        ->and(TaskListResource::getRelations())->toBe(['tasks' => TasksRelationManager::class]);
});

it('registers a route for every level of the hierarchy', function () {
    $routes = collect(app('router')->getRoutes())->map->getName();

    expect($routes)->toContain(
        'filament.admin.resources.spaces.index',
        'filament.admin.resources.spaces.folders.edit',
        'filament.admin.resources.spaces.folders.versions.edit',
        'filament.admin.resources.spaces.folders.versions.task-lists.edit',
        'filament.admin.resources.spaces.folders.versions.task-lists.tasks.edit',
    );
});

it('walks the whole hierarchy back up to its workspace', function () {
    $task = Task::factory()->create();
    $version = $task->taskList->folder;

    expect($task->taskList)->toBeInstanceOf(TaskList::class)
        ->and($version)->toBeInstanceOf(Folder::class)
        ->and($version->isVersion())->toBeTrue()
        ->and($version->parentFolder)->toBeInstanceOf(Folder::class)
        ->and($version->parentFolder->isVersion())->toBeFalse()
        ->and($version->parentFolder->space)->toBeInstanceOf(Space::class)
        ->and($version->parentFolder->space->workspace)->toBeInstanceOf(Workspace::class)
        ->and($task->taskList->space_id)->toBe($version->space_id);
});

it('keeps versions out of the top level folders of a space', function () {
    $version = Folder::factory()->version()->create();
    $space = $version->parentFolder->space;

    expect($space->folders()->pluck('id')->all())->toBe([$version->parent_id])
        ->and($space->allFolders()->count())->toBe(2)
        ->and($version->parentFolder->versions()->pluck('id')->all())->toBe([$version->id]);
});

it('syncs the folders of a space from clickup, nesting sub folders under their parent', function () {
    $space = Space::factory()->create(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')
        ->once()
        ->with('90010000000')
        ->andReturn([
            ['id' => '9011', 'name' => 'v1.0', 'parent_folder_id' => '901'],
            ['id' => '901', 'name' => 'Marketing', 'orderindex' => 1, 'task_count' => 4],
            ['id' => '902', 'name' => 'Engineering', 'hidden' => true],
        ]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    expect($space->syncFoldersFromClickUp())->toBe(3)
        ->and($space->folders()->pluck('name')->all())->toBe(['Marketing', 'Engineering'])
        ->and($space->allFolders()->count())->toBe(3);

    $project = $space->folders()->where('fid', 901)->first();

    expect($project->versions()->pluck('name')->all())->toBe(['v1.0'])
        ->and($project->versions()->first()->space_id)->toBe($space->id);
});

it('nests sub folders from the payload clickup actually returns', function () {
    $space = Space::factory()->create(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')->andReturn([
        [
            'id' => '901817207336',
            'name' => 'vesion1.0.0',
            'orderindex' => 0,
            'override_statuses' => false,
            'hidden' => false,
            'space' => ['id' => '90010000000', 'name' => 'Space'],
            'task_count' => '2',
            'archived' => false,
            'statuses' => [],
            'lists' => [],
            'parent_folder' => '901816713501',
            'permission_level' => 'create',
        ],
        [
            'id' => '901816713501',
            'name' => 'pms',
            'orderindex' => 1,
            'override_statuses' => false,
            'hidden' => false,
            'space' => ['id' => '90010000000', 'name' => 'Space'],
            'task_count' => '0',
            'archived' => false,
            'statuses' => [],
            'lists' => [],
            'permission_level' => 'create',
        ],
    ]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    expect($space->syncFoldersFromClickUp())->toBe(2)
        ->and($space->folders()->pluck('name')->all())->toBe(['pms']);

    $project = $space->folders()->first();
    $version = $project->versions()->first();

    expect($version->name)->toBe('vesion1.0.0')
        ->and($version->task_count)->toBe(2)
        ->and($version->space_id)->toBe($space->id);
});

it('does not duplicate folders that were already synced', function () {
    $space = Space::factory()->create(['sid' => 90010000000]);
    $space->folders()->create(['fid' => 901, 'name' => 'Old name']);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')->andReturn([
        ['id' => '901', 'name' => 'Marketing'],
    ]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    $space->syncFoldersFromClickUp();

    expect($space->folders()->count())->toBe(1)
        ->and($space->folders()->first()->name)->toBe('Marketing');
});

it('syncs the versions of a folder from clickup', function () {
    $project = Folder::factory()->create(['fid' => 901]);
    $project->space->update(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')
        ->once()
        ->with('90010000000')
        ->andReturn([
            ['id' => '901', 'name' => 'Marketing'],
            ['id' => '9011', 'name' => 'v1.0', 'parent_folder_id' => '901'],
            ['id' => '9012', 'name' => 'v2.0', 'parent_folder_id' => '901'],
            ['id' => '9013', 'name' => 'v1.0', 'parent_folder_id' => '902'],
        ]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    expect($project->syncVersionsFromClickUp())->toBe(2)
        ->and($project->versions()->pluck('fid')->all())->toBe([9011, 9012]);
});

it('creates a version as a sub folder in clickup before storing it locally', function () {
    $project = Folder::factory()->create(['fid' => 901]);
    $project->space->update(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('create')
        ->once()
        ->with('90010000000', ['name' => 'v1.0', 'parent_folder_id' => '901'])
        ->andReturn(['id' => '9011', 'name' => 'v1.0']);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    $version = $project->createVersionInClickUp(['name' => 'v1.0']);

    expect($version->fid)->toBe('9011')
        ->and($version->parent_id)->toBe($project->id)
        ->and($version->space_id)->toBe($project->space_id)
        ->and($version->isVersion())->toBeTrue();
});

it('syncs the lists of a version from clickup', function () {
    $version = Folder::factory()->version()->create(['fid' => 9011]);

    $lists = Mockery::mock(Lists::class);
    $lists->shouldReceive('all')
        ->once()
        ->with('9011')
        ->andReturn([
            [
                'id' => '90111',
                'name' => 'Developers',
                'task_count' => 7,
                'status' => ['status' => 'open'],
                'priority' => ['priority' => 'high'],
                'due_date' => '1735689600000',
            ],
        ]);

    ClickUp::shouldReceive('lists')->andReturn($lists);

    expect($version->syncTaskListsFromClickUp())->toBe(1);

    $list = $version->taskLists()->first();

    expect($list->name)->toBe('Developers')
        ->and($list->space_id)->toBe($version->space_id)
        ->and($list->status)->toBe('open')
        ->and($list->priority)->toBe('high')
        ->and($list->due_date->timestamp)->toBe(1735689600);
});

it('syncs the tasks of a list from clickup', function () {
    $list = TaskList::factory()->create(['lid' => 90111]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('cursor')
        ->once()
        ->with('90111', ['subtasks' => true])
        ->andReturn((function () {
            yield [
                'id' => 'abc123',
                'name' => 'Write the release notes',
                'status' => ['status' => 'in progress', 'color' => '#4194f6'],
                'priority' => ['id' => '2', 'priority' => 'high'],
                'assignees' => [['id' => 183, 'username' => 'Ada']],
                'url' => 'https://app.clickup.com/t/abc123',
            ];
        })());

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    expect($list->syncTasksFromClickUp())->toBe(1);

    $task = $list->tasks()->first();

    expect($task->tid)->toBe('abc123')
        ->and($task->status)->toBe('in progress')
        ->and($task->priority)->toBe(2)
        ->and($task->assignees)->toBe([['id' => 183, 'username' => 'Ada']]);
});

it('stores the very large order index clickup gives a task', function () {
    $list = TaskList::factory()->create(['lid' => 90111]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('cursor')->andReturn((function () {
        yield [
            'id' => 'abc123',
            'name' => 'Build the sync',
            'orderindex' => '10001789058254.00000000000000000000000000000000',
        ];
    })());

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    $list->syncTasksFromClickUp();

    expect((float) $list->tasks()->sole()->orderindex)->toBe(10001789058254.0);
});

it('creates a folder in clickup before storing it locally', function () {
    $space = Space::factory()->create(['sid' => 90010000000]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('create')
        ->once()
        ->with('90010000000', ['name' => 'Design'])
        ->andReturn(['id' => '903', 'name' => 'Design', 'orderindex' => 2]);

    ClickUp::shouldReceive('folders')->andReturn($folders);

    $folder = $space->createFolderInClickUp(['name' => 'Design']);

    expect($folder->fid)->toBe('903')
        ->and($folder->space_id)->toBe($space->id)
        ->and($folder->parent_id)->toBeNull()
        ->and($space->folders()->count())->toBe(1);
});

it('creates a list in clickup before storing it locally', function () {
    $version = Folder::factory()->version()->create(['fid' => 9011]);

    $lists = Mockery::mock(Lists::class);
    $lists->shouldReceive('create')
        ->once()
        ->with('9011', ['name' => 'Developers'])
        ->andReturn(['id' => '90112', 'name' => 'Developers']);

    ClickUp::shouldReceive('lists')->andReturn($lists);

    $list = $version->createTaskListInClickUp(['name' => 'Developers', 'content' => null]);

    expect($list->lid)->toBe('90112')
        ->and($list->folder_id)->toBe($version->id)
        ->and($list->space_id)->toBe($version->space_id);
});

it('creates a task in clickup before storing it locally', function () {
    $list = TaskList::factory()->create(['lid' => 90111]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('create')
        ->once()
        ->with('90111', ['name' => 'Ship it', 'priority' => 1])
        ->andReturn([
            'id' => 'def456',
            'name' => 'Ship it',
            'priority' => ['id' => '1', 'priority' => 'urgent'],
        ]);

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    $task = $list->createTaskInClickUp(['name' => 'Ship it', 'priority' => 1]);

    expect($task->tid)->toBe('def456')
        ->and($task->priority)->toBe(1)
        ->and($task->task_list_id)->toBe($list->id);
});

it('renders each level of the hierarchy', function (string $level) {
    $task = Task::factory()->create();
    $list = $task->taskList;
    $version = $list->folder;
    $folder = $version->parentFolder;
    $space = $folder->space;
    $workspace = $space->workspace;

    $user = User::factory()->create();
    $user->workspaces()->attach($workspace);

    $folderUrl = "/admin/{$workspace->wid}/spaces/{$space->id}/folders";
    $versionUrl = "{$folderUrl}/{$folder->id}/versions";
    $listUrl = "{$versionUrl}/{$version->id}/task-lists";

    $urls = [
        'spaces' => "/admin/{$workspace->wid}/spaces",
        'space' => "/admin/{$workspace->wid}/spaces/{$space->id}/edit",
        'folder' => "{$folderUrl}/{$folder->id}/edit",
        'version' => "{$versionUrl}/{$version->id}/edit",
        'list' => "{$listUrl}/{$list->id}/edit",
        'task' => "{$listUrl}/{$list->id}/tasks/{$task->id}/edit",
    ];

    $this->actingAs($user)
        ->get($urls[$level])
        ->assertSuccessful();
})->with(['spaces', 'space', 'folder', 'version', 'list', 'task']);
