<?php

use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Tasks;

uses(RefreshDatabase::class);

/**
 * A ClickUp task payload with every field the API returns populated.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function clickUpTask(array $overrides = []): array
{
    return [
        'id' => '86eyvrne1',
        'custom_id' => 'PMS-1',
        'custom_item_id' => 0,
        'name' => 'Create the PMS',
        'text_content' => 'Plain text body',
        'description' => 'Markdown body',
        'status' => [
            'id' => 'p901812596227_X8v1rz7W',
            'status' => 'complete',
            'color' => '#6bc950',
            'orderindex' => 2,
            'type' => 'closed',
        ],
        'orderindex' => '10001789058254.00000000000000000000000000000000',
        'date_created' => '1788976146096',
        'date_updated' => '1788976164474',
        'date_closed' => '1788976200000',
        'date_done' => '1788976200000',
        'archived' => false,
        'creator' => ['id' => 234123358, 'username' => 'Muhammad Shoaib', 'email' => 'ms@example.com'],
        'assignees' => [['id' => 183, 'username' => 'Ada']],
        'group_assignees' => [['id' => 'g1', 'name' => 'Developers']],
        'watchers' => [['id' => 234123358, 'username' => 'Muhammad Shoaib']],
        'checklists' => [['id' => 'c1', 'name' => 'Steps', 'items' => []]],
        'tags' => [['name' => 'ui']],
        'parent' => null,
        'top_level_parent' => null,
        'priority' => ['id' => '2', 'priority' => 'high', 'color' => '#ffcc00'],
        'due_date' => '1735689600000',
        'start_date' => '1735603200000',
        'points' => 5,
        'time_estimate' => 3600000,
        'time_spent' => 1800000,
        'custom_fields' => [['id' => 'f1', 'name' => 'Sprint', 'value' => '12']],
        'dependencies' => [['task_id' => 'abc', 'depends_on' => 'def']],
        'linked_tasks' => [['task_id' => 'abc', 'link_id' => 'ghi']],
        'locations' => [['id' => '901821115796']],
        'team_id' => '90182991956',
        'url' => 'https://app.clickup.com/t/86eyvrne1',
        'sharing' => ['public' => false, 'token' => null],
        'permission_level' => 'create',
        'attachments' => [['id' => 'a1', 'title' => 'spec.pdf']],
        ...$overrides,
    ];
}

it('mirrors every field of a clickup task', function () {
    $list = TaskList::factory()->create(['lid' => 901821115796]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('cursor')->andReturn((function () {
        yield clickUpTask();
    })());

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    $list->syncTasksFromClickUp();

    $task = $list->tasks()->sole();

    expect($task->tid)->toBe('86eyvrne1')
        ->and($task->custom_id)->toBe('PMS-1')
        ->and($task->custom_item_id)->toBe(0)
        ->and($task->name)->toBe('Create the PMS')
        ->and($task->description)->toBe('Markdown body')
        ->and($task->text_content)->toBe('Plain text body')
        ->and($task->status)->toBe('complete')
        ->and($task->status_id)->toBe('p901812596227_X8v1rz7W')
        ->and($task->status_color)->toBe('#6bc950')
        ->and($task->status_type)->toBe('closed')
        ->and($task->isDone())->toBeTrue()
        ->and($task->priority)->toBe(2)
        ->and($task->priority_label)->toBe('high')
        ->and($task->priority_color)->toBe('#ffcc00')
        ->and((float) $task->orderindex)->toBe(10001789058254.0)
        ->and($task->archived)->toBeFalse();

    expect($task->creator['username'])->toBe('Muhammad Shoaib')
        ->and($task->assignees)->toBe([['id' => 183, 'username' => 'Ada']])
        ->and($task->group_assignees)->toBe([['id' => 'g1', 'name' => 'Developers']])
        ->and($task->watchers)->toHaveCount(1)
        ->and($task->checklists)->toHaveCount(1)
        ->and($task->tags)->toBe([['name' => 'ui']])
        ->and($task->custom_fields)->toBe([['id' => 'f1', 'name' => 'Sprint', 'value' => '12']])
        ->and($task->dependencies)->toHaveCount(1)
        ->and($task->linked_tasks)->toHaveCount(1)
        ->and($task->locations)->toHaveCount(1)
        ->and($task->attachments)->toHaveCount(1)
        ->and($task->sharing['public'])->toBeFalse();

    expect($task->due_date->timestamp)->toBe(1735689600)
        ->and($task->start_date->timestamp)->toBe(1735603200)
        ->and($task->clickup_created_at->timestamp)->toBe(1788976146)
        ->and($task->clickup_updated_at->timestamp)->toBe(1788976164)
        ->and($task->closed_at->timestamp)->toBe(1788976200)
        ->and($task->done_at->timestamp)->toBe(1788976200)
        ->and($task->points)->toBe(5.0)
        ->and($task->time_estimate)->toBe(3600000)
        ->and($task->time_spent)->toBe(1800000)
        ->and($task->url)->toBe('https://app.clickup.com/t/86eyvrne1')
        ->and($task->team_id)->toBe('90182991956')
        ->and($task->permission_level)->toBe('create');
});

it('leaves no clickup field unmapped', function () {
    $ignored = ['id', 'list', 'project', 'folder', 'space'];

    $mapped = array_keys(Task::attributesFromClickUp(clickUpTask()));

    $missing = collect(array_keys(clickUpTask()))
        ->reject(fn (string $field): bool => in_array($field, $ignored, strict: true))
        ->reject(fn (string $field): bool => in_array($field, $mapped, strict: true))
        ->reject(fn (string $field): bool => in_array($field, [
            'date_created', 'date_updated', 'date_closed', 'date_done', 'parent', 'top_level_parent',
        ], strict: true))
        ->values();

    expect($missing->all())->toBe([]);
});

it('hangs subtasks off their parent whichever order they arrive in', function () {
    $list = TaskList::factory()->create(['lid' => 901821115796]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('cursor')->andReturn((function () {
        yield clickUpTask([
            'id' => 'child1',
            'name' => 'Subtask',
            'parent' => '86eyvrne1',
            'top_level_parent' => '86eyvrne1',
        ]);
        yield clickUpTask();
    })());

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    $list->syncTasksFromClickUp();

    $parent = Task::query()->where('tid', '86eyvrne1')->sole();
    $child = Task::query()->where('tid', 'child1')->sole();

    expect($child->parent_tid)->toBe('86eyvrne1')
        ->and($child->parent_id)->toBe($parent->id)
        ->and($child->top_level_parent_tid)->toBe('86eyvrne1')
        ->and($child->isSubtask())->toBeTrue()
        ->and($parent->subtasks()->pluck('tid')->all())->toBe(['child1'])
        ->and($parent->isSubtask())->toBeFalse();
});

it('mirrors the full payload when a task is created through the app', function () {
    $list = TaskList::factory()->create(['lid' => 901821115796]);

    $tasks = Mockery::mock(Tasks::class);
    $tasks->shouldReceive('create')->once()->andReturn(clickUpTask());

    ClickUp::shouldReceive('tasks')->andReturn($tasks);

    $task = $list->createTaskInClickUp(['name' => 'Create the PMS']);

    expect($task->status_type)->toBe('closed')
        ->and($task->creator['username'])->toBe('Muhammad Shoaib')
        ->and($task->team_id)->toBe('90182991956')
        ->and($task->clickup_created_at->timestamp)->toBe(1788976146);
});
