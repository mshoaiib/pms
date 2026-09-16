<?php

use App\Models\Folder;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->vault = base_path('tests/tmp/vault');

    File::deleteDirectory($this->vault);
    File::makeDirectory($this->vault, recursive: true);

    config([
        'vault.path' => $this->vault,
        'vault.folder' => 'wiki/work',
        'vault.default_domain' => 'pms',
        'vault.domains' => ['Campaign Insights MCP' => 'data-ml'],
    ]);
});

afterEach(function () {
    File::deleteDirectory(base_path('tests/tmp'));
});

/**
 * A Space holding one project, one version, one list and one task.
 */
function mirroredTree(string $project = 'Campaign Insights MCP'): Task
{
    $space = Space::factory()->create(['name' => 'Orcha']);
    $folder = Folder::factory()->create(['space_id' => $space->id, 'name' => $project]);
    $version = Folder::factory()->version($folder)->create(['name' => 'v1.0.0']);
    $list = TaskList::factory()->create([
        'folder_id' => $version->id,
        'space_id' => $space->id,
        'name' => 'Developer',
        'content' => 'The build work.',
    ]);

    return Task::factory()->create([
        'task_list_id' => $list->id,
        'tid' => 'abc123',
        'name' => 'Google Ads connector',
        'description' => 'Pull campaign performance into BigQuery.',
        'status' => 'to do',
        'priority' => 1,
        'priority_label' => 'urgent',
        'url' => 'https://app.clickup.com/t/abc123',
    ]);
}

it('writes a page for every level of the hierarchy', function () {
    mirroredTree();

    $this->artisan('pms:export-vault')->assertSuccessful();

    $folder = $this->vault.'/wiki/work';

    expect(File::exists($folder.'/Orcha.md'))->toBeTrue()
        ->and(File::exists($folder.'/Campaign Insights MCP.md'))->toBeTrue()
        ->and(File::exists($folder.'/Campaign Insights MCP v1.0.0.md'))->toBeTrue()
        ->and(File::exists($folder.'/Developer (v1.0.0).md'))->toBeTrue()
        ->and(File::exists($folder.'/Google Ads connector.md'))->toBeTrue();
});

it('carries the clickup identity and the parents in frontmatter', function () {
    mirroredTree();

    $this->artisan('pms:export-vault');

    $page = File::get($this->vault.'/wiki/work/Google Ads connector.md');

    expect($page)->toContain('type: work')
        ->toContain('level: task')
        ->toContain('domain: data-ml')
        ->toContain('clickup_id: abc123')
        ->toContain('clickup_url: https://app.clickup.com/t/abc123')
        ->toContain('status: to do')
        ->toContain('priority: urgent')
        ->toContain('version: "[[Campaign Insights MCP v1.0.0]]"')
        ->toContain('Pull campaign performance into BigQuery.')
        ->toContain('Edit the record in ClickUp, not this page');
});

it('files a project under the domain configured for it', function () {
    mirroredTree('pms');

    $this->artisan('pms:export-vault');

    expect(File::get($this->vault.'/wiki/work/pms.md'))->toContain('domain: pms');
});

it('leaves a page alone when nothing about the record changed', function () {
    $task = mirroredTree();

    $this->artisan('pms:export-vault');

    $page = $this->vault.'/wiki/work/Google Ads connector.md';
    $writtenAt = File::lastModified($page);

    $this->artisan('pms:export-vault')
        ->expectsOutputToContain('already current')
        ->assertSuccessful();

    expect(File::lastModified($page))->toBe($writtenAt);

    $task->update(['status' => 'in progress']);

    $this->artisan('pms:export-vault');

    expect(File::get($page))->toContain('status: in progress');
});

it('does nothing when no vault is configured', function () {
    mirroredTree();

    config(['vault.path' => null]);

    $this->artisan('pms:export-vault')
        ->expectsOutputToContain('No vault configured')
        ->assertSuccessful();
});

it('fails loudly when the vault path is wrong', function () {
    config(['vault.path' => base_path('tests/tmp/not-a-vault')]);

    $this->artisan('pms:export-vault')->assertFailed();
});

it('keeps a name that would be an illegal filename', function () {
    $task = mirroredTree();
    $task->update(['name' => 'Ads: cost/clicks report? [v2]']);

    $this->artisan('pms:export-vault')->assertSuccessful();

    $files = collect(File::files($this->vault.'/wiki/work'))
        ->map(fn ($file): string => $file->getFilename());

    expect($files)->toContain('Ads cost clicks report v2.md')
        ->and(File::get($this->vault.'/wiki/work/Developer (v1.0.0).md'))
        ->toContain('[[Ads cost clicks report v2]]');
});
