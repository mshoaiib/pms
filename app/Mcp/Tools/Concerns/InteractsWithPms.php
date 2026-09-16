<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\Folder;
use App\Models\Space;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\Workspace;
use Laravel\Mcp\Response;
use Maya719\ClickUp\Exceptions\ClickUpException;

/**
 * Looks records up by their ClickUp id and shapes them for an AI client.
 *
 * Reads come from this application's mirror of ClickUp rather than the API, so
 * they are as fresh as the last `clickup:sync` run.
 */
trait InteractsWithPms
{
    protected function workspace(string $workspaceId): ?Workspace
    {
        return Workspace::query()->where('wid', $workspaceId)->first();
    }

    protected function space(string $spaceId): ?Space
    {
        return Space::query()->where('sid', $spaceId)->first();
    }

    /**
     * A top level Folder.
     */
    protected function project(string $projectId): ?Folder
    {
        return Folder::query()->whereNull('parent_id')->where('fid', $projectId)->first();
    }

    /**
     * A Folder nested inside a project Folder.
     */
    protected function version(string $versionId): ?Folder
    {
        return Folder::query()->whereNotNull('parent_id')->where('fid', $versionId)->first();
    }

    protected function taskList(string $listId): ?TaskList
    {
        return TaskList::query()->where('lid', $listId)->first();
    }

    protected function task(string $taskId): ?Task
    {
        return Task::query()->where('tid', $taskId)->first();
    }

    protected function unknown(string $label, string $id, string $hint = ''): Response
    {
        return Response::error(trim(implode(' ', array_filter([
            "No {$label} is known with the ClickUp id [{$id}].",
            $hint,
            'Use sync-workspace to pull in records created outside this application.',
        ]))));
    }

    protected function rejected(string $label, ClickUpException $exception): Response
    {
        return Response::error("ClickUp rejected the {$label}: {$exception->getMessage()}");
    }

    /**
     * @return array<string, mixed>
     */
    protected function spaceData(Space $space): array
    {
        return [
            'space_id' => (string) $space->sid,
            'name' => $space->name,
            'private' => (bool) $space->private,
            'multiple_assignees' => (bool) $space->multiple_assignees,
            'projects' => $space->folders_count ?? $space->folders()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectData(Folder $project): array
    {
        return [
            'project_id' => (string) $project->fid,
            'name' => $project->name,
            'space_id' => (string) $project->space->sid,
            'versions' => $project->versions_count ?? $project->versions()->count(),
            'tasks_in_clickup' => (int) $project->task_count,
            'archived' => (bool) $project->archived,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function versionData(Folder $version): array
    {
        return [
            'version_id' => (string) $version->fid,
            'name' => $version->name,
            'project_id' => (string) $version->parentFolder->fid,
            'lists' => $version->task_lists_count ?? $version->taskLists()->count(),
            'tasks_in_clickup' => (int) $version->task_count,
            'archived' => (bool) $version->archived,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function listData(TaskList $list): array
    {
        return [
            'list_id' => (string) $list->lid,
            'name' => $list->name,
            'version_id' => (string) $list->folder->fid,
            'content' => $list->content,
            'tasks' => $list->tasks_count ?? $list->tasks()->count(),
            'archived' => (bool) $list->archived,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskData(Task $task): array
    {
        return [
            'task_id' => $task->tid,
            'name' => $task->name,
            'list_id' => (string) $task->taskList->lid,
            'status' => $task->status,
            'priority' => $task->priority,
            'priority_label' => Task::priorities()[$task->priority] ?? null,
            'assignees' => collect($task->assignees ?? [])->pluck('username')->filter()->values()->all(),
            'due_date' => $task->due_date?->toIso8601String(),
            'url' => $task->url,
        ];
    }
}
