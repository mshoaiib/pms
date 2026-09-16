<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;
use Maya719\ClickUp\ClickUp;

class Workspace extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Refresh Workspaces';
    }

    public function mount(): void
    {
        parent::mount();

        $this->workspaces();
        $this->redirect(
            Filament::getPanel('admin')->getUrl()
        );
    }

    private function workspaces(): void
    {
        $clickup = new ClickUp(config('clickup.api_key'));

        foreach ($clickup->workspaces()->all() as $workspace) {
            $name = $workspace['name'];
            $wid = $workspace['id'];

            $slug = Str::slug(
                Str::before($name, "'s Workspace")
            );
            $team = \App\Models\Workspace::updateOrCreate(
                [
                    'wid' => $wid,
                ],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'color' => $workspace['color'] ?? null,
                    'avatar' => $workspace['avatar'] ?? null,
                ]
            );
            $team->users()->syncWithoutDetaching([
                auth()->id(),
            ]);
        }
    }
}
