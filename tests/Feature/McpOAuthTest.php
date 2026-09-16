<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

/**
 * The hosted Claude surfaces discover how to authenticate from these documents
 * and reject a server that advertises the wrong thing, so each assertion here
 * mirrors a requirement of Claude's OAuth discovery flow rather than a detail
 * of our own choosing.
 */
it('advertises the mcp endpoint as a protected resource', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp/pms')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp/pms'))
        ->assertJsonStructure(['resource', 'authorization_servers']);
});

it('advertises an authorization server that supports pkce', function () {
    $response = $this->getJson('/.well-known/oauth-authorization-server')->assertOk();

    expect($response->json('code_challenge_methods_supported'))->toContain('S256')
        ->and($response->json('registration_endpoint'))->not->toBeEmpty();
});

it('points a rejected call at the discovery document', function () {
    $response = $this->postJson('/mcp/pms', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], ['Accept' => 'application/json, text/event-stream'])->assertUnauthorized();

    expect($response->headers->get('WWW-Authenticate'))
        ->toContain('resource_metadata=');
});

it('serves a caller holding an oauth access token', function () {
    Passport::actingAs(User::factory()->create());

    $this->postJson('/mcp/pms', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
    ], ['Accept' => 'application/json, text/event-stream'])
        ->assertOk()
        ->assertJsonPath('result.tools.0.name', 'list-workspaces');
});
