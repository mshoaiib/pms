<?php

use App\Models\Folder;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Workspace;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maya719\ClickUp\Exceptions\AuthenticationException;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Folders;
use Maya719\ClickUp\Resources\Spaces;
use Maya719\ClickUp\Resources\Tasks;

uses(RefreshDatabase::class);

/**
 * The Space, Folder and Task responses a full sync walks through.
 */
function fakeClickUp(): void
{
    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('all')
        ->with('9001')
        ->andReturn([
            ['id' => '90010000000', 'name' => 'Product', 'private' => false],
        ]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')
        ->with('90010000000')
        ->andReturn([
            [
                'id' => '901817207336',
                'name' => 'vesion1.0.0',
                'task_count' => '2',
                'parent_folder' => '901816713501',
                'lists' => [
                    ['id' => '901111', 'name' => 'Developers', 'task_count' => '2'],
                ],
            ],
            [
                'id' => '901816713501',
                'name' => 'pms',
                'task_count' => '0',
                'lists' => [],
            ],
        ]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('cursor')
        ->with('901111', ['subtasks' => true])
        ->andReturn((function () {
            yield ['id' => 'abc123', 'name' => 'Build the sync', 'status' => ['status' => 'open']];
            yield ['id' => 'def456', 'name' => 'Schedule the sync', 'status' => ['status' => 'open']];
        })());

    ClickUp::shouldReceive('spaces')->andReturn($spaces);
    ClickUp::shouldReceive('folders')->andReturn($folders);
    ClickUp::shouldReceive('tasks')->andReturn($tasks);
}

it('pulls the whole hierarchy down in one command', function () {
    $workspace = Workspace::factory()->create(['wid' => 9001]);

    fakeClickUp();

    $this->artisan('clickup:sync')->assertSuccessful();

    $space = $workspace->spaces()->sole();
    $project = $space->folders()->sole();
    $version = $project->versions()->sole();
    $list = $version->taskLists()->sole();

    expect($space->name)->toBe('Product')
        ->and($project->name)->toBe('pms')
        ->and($version->name)->toBe('vesion1.0.0')
        ->and($list->name)->toBe('Developers')
        ->and($list->tasks()->pluck('name')->all())->toBe(['Build the sync', 'Schedule the sync']);
});

it('leaves tasks alone when they are skipped', function () {
    Workspace::factory()->create(['wid' => 9001]);

    fakeClickUp();

    $this->artisan('clickup:sync', ['--skip-tasks' => true])->assertSuccessful();

    expect(TaskList::query()->count())->toBe(1)
        ->and(Task::query()->count())->toBe(0);
});

it('only syncs the workspace it is given', function () {
    Workspace::factory()->create(['wid' => 9001]);
    $other = Workspace::factory()->create(['wid' => 9002]);

    fakeClickUp();

    $this->artisan('clickup:sync', ['--workspace' => '9001'])->assertSuccessful();

    expect($other->spaces()->count())->toBe(0)
        ->and(Folder::query()->count())->toBe(2);
});

it('reports a failure without stopping the other workspaces', function () {
    Workspace::factory()->create(['wid' => 9000]);
    $healthy = Workspace::factory()->create(['wid' => 9001]);

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('all')
        ->with('9000')
        ->andThrow(new AuthenticationException('Token rejected.'));
    $spaces->shouldReceive('all')
        ->with('9001')
        ->andReturn([['id' => '90010000000', 'name' => 'Product']]);

    $folders = Mockery::mock(Folders::class);
    $folders->shouldReceive('all')->andReturn([]);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);
    ClickUp::shouldReceive('folders')->andReturn($folders);

    $this->artisan('clickup:sync')->assertFailed();

    expect($healthy->spaces()->count())->toBe(1);
});

it('says so when there is nothing to sync', function () {
    $this->artisan('clickup:sync')
        ->expectsOutputToContain('No workspaces to sync.')
        ->assertSuccessful();
});

it('runs the sync on an hourly schedule', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains($event->command ?? '', 'clickup:sync'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});
