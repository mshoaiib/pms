<?php

namespace App\Mcp\Tools\Tasks;

use App\Mcp\Tools\Concerns\InteractsWithPms;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(
    'List the Tasks of a List, as of the last sync. Filter by status or priority, and raise the '.
    'limit for a long backlog.'
)]
#[IsReadOnly]
#[IsIdempotent]
#[Name('list-tasks')]
class ListTasksTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'list_id' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'between:1,4'],
            'limit' => ['nullable', 'integer', 'between:1,200'],
        ], [
            'list_id.required' => 'You must pass the ClickUp List id whose Tasks you want.',
            'priority.between' => 'Priority must be 1 (Urgent), 2 (High), 3 (Normal), or 4 (Low).',
        ]);

        $list = $this->taskList($validated['list_id']);

        if ($list === null) {
            return $this->unknown('List', $validated['list_id'], 'Call list-lists to see the known ids.');
        }

        $query = $list->tasks()
            ->when(
                $validated['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status)
            )
            ->when(
                $validated['priority'] ?? null,
                fn (Builder $query, int $priority): Builder => $query->where('priority', $priority)
            );

        $total = $query->clone()->count();

        $tasks = $query->orderBy('orderindex')
            ->limit($validated['limit'] ?? 50)
            ->get()
            ->map(fn (Task $task): array => $this->taskData($task));

        return Response::structured([
            'list_id' => (string) $list->lid,
            'returned' => $tasks->count(),
            'total' => $total,
            'tasks' => $tasks->all(),
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
            'list_id' => $schema
                ->string()
                ->description('The ClickUp List id whose Tasks should be listed.')
                ->required(),

            'status' => $schema
                ->string()
                ->description('Only return Tasks with this exact status, for example "in progress".'),

            'priority' => $schema
                ->integer()
                ->description('Only return Tasks with this priority: 1 Urgent, 2 High, 3 Normal, 4 Low.'),

            'limit' => $schema
                ->integer()
                ->description('How many Tasks to return, 1 to 200.')
                ->default(50),
        ];
    }
}
