<?php

namespace App\Mcp\Tools\Tasks;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use App\Models\Folder;
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

#[Description(
    'Create a Task inside a List. Tasks are the work items a team such as Graphic Designer, '.
    'Developers, or SQA picks up for one Version.'
)]
#[IsOpenWorld]
#[Name('create-task')]
class CreateTaskTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'list_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'between:1,4'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer'],
            'due_date' => ['nullable', 'integer'],
        ], [
            'list_id.required' => 'You must pass the ClickUp List id the Task belongs to.',
            'name.required' => 'You must give the Task a name.',
            'priority.between' => 'Priority must be 1 (Urgent), 2 (High), 3 (Normal), or 4 (Low).',
        ]);

        $list = $this->taskList($validated['list_id']);

        if ($list === null) {
            return $this->unknown('List', $validated['list_id'], 'Create it with create-list, or call list-lists.');
        }

        if (isset($validated['due_date'])) {
            $validated['due_date'] = Folder::timestampFromClickUp($validated['due_date']);
        }

        try {
            $task = $list->createTaskInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('Task', $exception);
        }

        return Response::structured($this->taskData($task));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'list_id' => $schema
                ->string()
                ->description('The ClickUp List id the Task is created in.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The task name.')
                ->required(),

            'description' => $schema
                ->string()
                ->description('Detailed description of the task.'),

            'status' => $schema
                ->string()
                ->description('A status configured on the Space, for example "to do". Defaults to the first status.'),

            'priority' => $schema
                ->integer()
                ->description('ClickUp priority: 1 = Urgent, 2 = High, 3 = Normal, 4 = Low.'),

            'assignees' => $schema
                ->array()
                ->items($schema->integer())
                ->description('ClickUp user ids to assign the task to.'),

            'due_date' => $schema
                ->integer()
                ->description('Due date as a Unix timestamp in milliseconds.'),
        ];
    }
}
