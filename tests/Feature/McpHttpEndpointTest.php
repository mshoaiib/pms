<?php

use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Maya719\ClickUp\Facades\ClickUp;
use Maya719\ClickUp\Resources\Spaces;

uses(RefreshDatabase::class);

/**
 * The JSON-RPC call the Postman collection makes.
 *
 * @param  array<string, mixed>  $params
 */
function callMcp(string $method, array $params = [], int $id = 1): TestResponse
{
    return test()->postJson('/mcp/pms', array_filter([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params ?: null,
    ]), ['Accept' => 'application/json, text/event-stream']);
}

/**
 * The Postman collection, when it is kept in the repository.
 */
function postmanCollectionPath(): string
{
    return dirname(__DIR__, 2).'/clickup-pms.postman_collection.json';
}

it('answers the initialize handshake with a session id', function () {
    $response = callMcp('initialize', [
        'protocolVersion' => '2025-11-25',
        'capabilities' => [],
        'clientInfo' => ['name' => 'Postman', 'version' => '1.0.0'],
    ]);

    $response->assertOk();

    expect($response->headers->get('MCP-Session-Id'))->not->toBeEmpty()
        ->and($response->json('result.protocolVersion'))->toBe('2025-11-25');
});

it('only accepts posts', function () {
    $this->get('/mcp/pms')->assertStatus(405);
});

it('exposes every pms tool on one page over http', function () {
    $response = callMcp('tools/list')->assertOk();

    $tools = collect($response->json('result.tools'))->pluck('name')->all();

    expect($response->json('result.nextCursor'))->toBeNull()
        ->and($tools)->toEqualCanonicalizing([
            'list-workspaces',
            'sync-workspace',
            'create-space', 'list-spaces', 'get-space', 'update-space', 'delete-space',
            'create-project', 'list-projects', 'get-project', 'update-project', 'delete-project',
            'create-version', 'list-versions', 'get-version', 'update-version', 'delete-version',
            'create-list', 'list-lists', 'get-list', 'update-list', 'delete-list',
            'create-task', 'list-tasks', 'get-task', 'update-task', 'delete-task',
        ]);
});

it('returns structured content a client can chain ids from', function () {
    Workspace::factory()->create(['wid' => 9001]);

    $spaces = Mockery::mock(Spaces::class);
    $spaces->shouldReceive('create')->andReturn(['id' => '90010000000', 'name' => 'MCP Test Space']);

    ClickUp::shouldReceive('spaces')->andReturn($spaces);

    $response = callMcp('tools/call', [
        'name' => 'create-space',
        'arguments' => ['workspace_id' => '9001', 'name' => 'MCP Test Space'],
    ]);

    $response->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.space_id', '90010000000');
});

it('flags a rejected call without failing the transport', function () {
    $response = callMcp('tools/call', [
        'name' => 'create-project',
        'arguments' => ['space_id' => '90010000000', 'name' => 'PMS'],
    ]);

    $response->assertOk()->assertJsonPath('result.isError', true);
});

it('keeps the postman collection pointed at tools that exist', function () {
    $collection = json_decode(
        file_get_contents(postmanCollectionPath()),
        associative: true,
        flags: JSON_THROW_ON_ERROR
    );

    $registered = collect(callMcp('tools/list')->json('result.tools'))->pluck('name');

    $called = collect($collection['item'])
        ->pluck('item')
        ->flatten(1)
        ->map(fn (array $request): mixed => json_decode(
            $request['request']['body']['raw'] ?? '{}',
            associative: true
        )['params']['name'] ?? null)
        ->filter()
        ->unique()
        ->values();

    expect($called)->not->toBeEmpty()
        ->and($called->diff($registered)->all())->toBe([]);
})->skip(
    fn (): bool => ! file_exists(postmanCollectionPath()),
    'The Postman collection is not kept in this repository.'
);
