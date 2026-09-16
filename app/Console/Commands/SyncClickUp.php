<?php

namespace App\Console\Commands;

use App\Models\Space;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Maya719\ClickUp\Exceptions\ClickUpException;

class SyncClickUp extends Command
{
    protected $signature = 'clickup:sync
                            {--workspace= : Only sync the Workspace with this ClickUp id}
                            {--skip-tasks : Stop after the Lists and leave Tasks untouched}';

    protected $description = 'Pull Spaces, Folders, versions, Lists and Tasks down from ClickUp';

    public function handle(): int
    {
        $workspaces = Workspace::query()
            ->when(
                $this->option('workspace'),
                fn (Builder $query, string $wid): Builder => $query->where('wid', $wid)
            )
            ->get();

        if ($workspaces->isEmpty()) {
            $this->components->warn('No workspaces to sync.');

            return self::SUCCESS;
        }

        $hasFailed = false;

        foreach ($workspaces as $workspace) {
            try {
                $this->syncWorkspace($workspace);
            } catch (ClickUpException $exception) {
                $hasFailed = true;

                $this->components->error(
                    "Workspace [{$workspace->name}] failed: {$exception->getMessage()}"
                );
            }
        }

        return $hasFailed ? self::FAILURE : self::SUCCESS;
    }

    private function syncWorkspace(Workspace $workspace): void
    {
        $spaces = $workspace->syncSpacesFromClickUp();

        $this->components->info("Workspace [{$workspace->name}]: {$spaces} spaces.");

        foreach ($workspace->spaces()->get() as $space) {
            $this->syncSpace($space);
        }
    }

    /**
     * Folders arrive with their sub folders (versions) and their Lists embedded,
     * so a Space costs one request plus one per List when Tasks are included.
     */
    private function syncSpace(Space $space): void
    {
        $folders = $space->syncFoldersFromClickUp();
        $lists = $space->taskLists()->count();

        $summary = "  {$space->name}: {$folders} folders, {$lists} lists";

        if (! $this->option('skip-tasks')) {
            $tasks = 0;

            foreach ($space->taskLists()->get() as $taskList) {
                $tasks += $taskList->syncTasksFromClickUp();
            }

            $summary .= ", {$tasks} tasks";
        }

        $this->components->twoColumnDetail($summary, 'DONE');
    }
}
