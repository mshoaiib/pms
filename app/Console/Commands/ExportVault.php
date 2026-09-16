<?php

namespace App\Console\Commands;

use App\Models\Folder;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Mirrors the ClickUp hierarchy into an Obsidian vault as one page per record.
 *
 * Pages are generated: a page is only written when its content actually
 * changes, so a run that finds nothing new touches no files and leaves the
 * vault's history alone.
 */
class ExportVault extends Command
{
    protected $signature = 'pms:export-vault
                            {--dry-run : Report what would be written without touching the vault}
                            {--workspace= : Only mirror the Workspace with this ClickUp id}';

    protected $description = 'Mirror Spaces, Projects, Versions, Lists and Tasks into the Obsidian vault';

    private int $written = 0;

    private int $unchanged = 0;

    public function handle(): int
    {
        $vault = config('vault.path');

        if (blank($vault)) {
            $this->components->warn('No vault configured. Set PMS_VAULT_PATH to mirror the hierarchy.');

            return self::SUCCESS;
        }

        if (! is_dir($vault)) {
            $this->components->error("The vault path [{$vault}] does not exist.");

            return self::FAILURE;
        }

        $folder = rtrim($vault, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, config('vault.folder'));

        if (! $this->option('dry-run') && ! is_dir($folder)) {
            mkdir($folder, recursive: true);
        }

        $workspaces = Workspace::query()
            ->when($this->option('workspace'), fn ($query, string $wid) => $query->where('wid', $wid))
            ->with('spaces.allFolders.taskLists.tasks')
            ->get();

        foreach ($workspaces as $workspace) {
            foreach ($workspace->spaces as $space) {
                $this->writeSpace($folder, $space);
            }
        }

        $this->components->info(sprintf(
            '%s %d pages, %d already current.',
            $this->option('dry-run') ? 'Would write' : 'Wrote',
            $this->written,
            $this->unchanged,
        ));

        return self::SUCCESS;
    }

    private function writeSpace(string $folder, Space $space): void
    {
        $projects = $space->allFolders->whereNull('parent_id');

        $this->write($folder, $space->name, $this->page(
            level: 'space',
            name: $space->name,
            domain: config('vault.default_domain'),
            frontmatter: [
                'clickup_id' => (string) $space->sid,
                'workspace' => $space->workspace->name,
                'projects' => $projects->count(),
            ],
            body: [
                'Projects' => $projects
                    ->map(fn (Folder $project): string => '- '.$this->link($project->name))
                    ->join("\n"),
            ],
        ));

        foreach ($projects as $project) {
            $this->writeProject($folder, $space, $project);
        }
    }

    private function writeProject(string $folder, Space $space, Folder $project): void
    {
        $versions = $space->allFolders->where('parent_id', $project->id);

        $this->write($folder, $project->name, $this->page(
            level: 'project',
            name: $project->name,
            domain: $this->domainFor($project->name),
            frontmatter: [
                'clickup_id' => (string) $project->fid,
                'space' => $this->link($space->name),
                'versions' => $versions->count(),
            ],
            body: [
                'Versions' => $versions
                    ->map(fn (Folder $version): string => '- '.$this->link($this->versionTitle($project, $version)))
                    ->join("\n"),
            ],
        ));

        foreach ($versions as $version) {
            $this->writeVersion($folder, $space, $project, $version);
        }
    }

    private function writeVersion(string $folder, Space $space, Folder $project, Folder $version): void
    {
        $title = $this->versionTitle($project, $version);

        $this->write($folder, $title, $this->page(
            level: 'version',
            name: $title,
            domain: $this->domainFor($project->name),
            frontmatter: [
                'clickup_id' => (string) $version->fid,
                'project' => $this->link($project->name),
                'space' => $this->link($space->name),
                'lists' => $version->taskLists->count(),
                'tasks' => $version->taskLists->sum(fn (TaskList $list): int => $list->tasks->count()),
            ],
            body: [
                'Lists' => $version->taskLists
                    ->map(fn (TaskList $list): string => sprintf(
                        '- %s — %d tasks',
                        $this->link($this->listTitle($version, $list)),
                        $list->tasks->count(),
                    ))
                    ->join("\n"),
            ],
        ));

        foreach ($version->taskLists as $list) {
            $this->writeList($folder, $project, $version, $list);
        }
    }

    private function writeList(string $folder, Folder $project, Folder $version, TaskList $list): void
    {
        $title = $this->listTitle($version, $list);

        $this->write($folder, $title, $this->page(
            level: 'list',
            name: $title,
            domain: $this->domainFor($project->name),
            frontmatter: [
                'clickup_id' => (string) $list->lid,
                'version' => $this->link($this->versionTitle($project, $version)),
                'project' => $this->link($project->name),
                'tasks' => $list->tasks->count(),
            ],
            body: array_filter([
                'About' => $list->content,
                'Tasks' => $list->tasks
                    ->sortBy('orderindex')
                    ->map(fn (Task $task): string => sprintf(
                        '- %s — %s%s',
                        $this->link($task->name),
                        $task->status ?? 'no status',
                        filled($task->priority_label) ? ', '.$task->priority_label : '',
                    ))
                    ->join("\n"),
            ]),
        ));

        foreach ($list->tasks as $task) {
            $this->writeTask($folder, $project, $version, $list, $task);
        }
    }

    private function writeTask(string $folder, Folder $project, Folder $version, TaskList $list, Task $task): void
    {
        $this->write($folder, $task->name, $this->page(
            level: 'task',
            name: $task->name,
            domain: $this->domainFor($project->name),
            frontmatter: array_filter([
                'clickup_id' => $task->tid,
                'clickup_url' => $task->url,
                'status' => $task->status,
                'priority' => $task->priority_label,
                'assignees' => collect($task->assignees ?? [])->pluck('username')->join(', '),
                'due' => $task->due_date?->toDateString(),
                'list' => $list->name,
                'version' => $this->link($this->versionTitle($project, $version)),
                'project' => $this->link($project->name),
            ], fn (mixed $value): bool => filled($value)),
            body: array_filter([
                'Description' => $task->description,
                'Subtasks' => $task->subtasks
                    ->map(fn (Task $subtask): string => '- '.$this->link($subtask->name))
                    ->join("\n"),
            ]),
        ));
    }

    /**
     * @param  array<string, mixed>  $frontmatter
     * @param  array<string, string>  $body
     */
    private function page(string $level, string $name, string $domain, array $frontmatter, array $body): string
    {
        $keys = [
            'type' => 'work',
            'level' => $level,
            'domain' => $domain,
            'aliases' => '[]',
            'tags' => '[clickup]',
            ...$frontmatter,
        ];

        $lines = ['---'];

        foreach ($keys as $key => $value) {
            $lines[] = $key.': '.(is_string($value) && str_contains($value, '[[') ? '"'.$value.'"' : $value);
        }

        $lines[] = '---';
        $lines[] = '';
        $lines[] = '# '.$name;
        $lines[] = '';
        $lines[] = '> Mirrored from ClickUp by the PMS. Edit the record in ClickUp, not this page —';
        $lines[] = '> anything written here is replaced on the next `pms:export-vault` run.';

        foreach ($body as $heading => $content) {
            if (blank($content)) {
                continue;
            }

            $lines[] = '';
            $lines[] = '## '.$heading;
            $lines[] = '';
            $lines[] = trim($content);
        }

        return implode("\n", $lines)."\n";
    }

    private function write(string $folder, string $title, string $contents): void
    {
        $path = $folder.DIRECTORY_SEPARATOR.$this->filename($title);

        if (is_file($path) && file_get_contents($path) === $contents) {
            $this->unchanged++;

            return;
        }

        $this->written++;

        if ($this->option('dry-run')) {
            $this->components->twoColumnDetail(basename($path), is_file($path) ? 'CHANGED' : 'NEW');

            return;
        }

        file_put_contents($path, $contents);
    }

    /**
     * Obsidian resolves a wikilink by filename, so the page title is the link.
     */
    private function link(string $title): string
    {
        return '[['.$this->pageTitle($title).']]';
    }

    private function filename(string $title): string
    {
        return $this->pageTitle($title).'.md';
    }

    /**
     * Titles become filenames, so characters Windows and Obsidian reject go.
     */
    private function pageTitle(string $title): string
    {
        return Str::of($title)
            ->replaceMatches('/[\\\\\\/:*?"<>|#\\[\\]^]/', ' ')
            ->squish()
            ->limit(90, '')
            ->trim()
            ->value();
    }

    private function versionTitle(Folder $project, Folder $version): string
    {
        return $project->name.' '.$version->name;
    }

    private function listTitle(Folder $version, TaskList $list): string
    {
        return $list->name.' ('.$version->name.')';
    }

    private function domainFor(string $project): string
    {
        return config('vault.domains')[$project] ?? config('vault.default_domain');
    }
}
