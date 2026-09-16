<?php

namespace App\Mcp\Tools\Lists;

use App\Mcp\Tools\Concerns\InteractsWithPms;
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
    'Create a List inside a Version. A List is a work category for that release, such as '.
    'Graphic Designer, Developers, or SQA. Returns the list_id to pass to create-task.'
)]
#[IsOpenWorld]
#[Name('create-list')]
class CreateListTool extends Tool
{
    use InteractsWithPms;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'version_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ], [
            'version_id.required' => 'You must pass the ClickUp Folder id of the Version this List belongs to.',
            'name.required' => 'You must name the List, for example "Developers".',
        ]);

        $version = $this->version($validated['version_id']);

        if ($version === null) {
            return $this->unknown(
                'Version',
                $validated['version_id'],
                'Create it with create-version, or call list-versions. Lists belong to a Version, not to a Project.'
            );
        }

        try {
            $list = $version->createTaskListInClickUp($validated);
        } catch (ClickUpException $exception) {
            return $this->rejected('List', $exception);
        }

        return Response::structured($this->listData($list));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'version_id' => $schema
                ->string()
                ->description('The ClickUp Folder id of the Version the List is created in.')
                ->required(),

            'name' => $schema
                ->string()
                ->description('The name of the List. Examples: Graphic Designer, Developers, SQA.')
                ->required(),

            'content' => $schema
                ->string()
                ->description('An optional description of what this List covers.'),
        ];
    }
}
