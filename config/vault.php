<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vault Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the Obsidian vault the ClickUp hierarchy is mirrored
    | into. Leave empty to turn the mirror off, which is what a deployed
    | instance does: it has no vault on its filesystem.
    |
    */
    'path' => env('PMS_VAULT_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Mirror Folder
    |--------------------------------------------------------------------------
    |
    | Folder inside the vault that holds the generated pages, relative to the
    | vault root. Everything under it is written by `pms:export-vault` and is
    | overwritten on the next run.
    |
    */
    'folder' => env('PMS_VAULT_FOLDER', 'wiki/work'),

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    |
    | The `domain:` frontmatter key each project's pages are filed under, keyed
    | by project name. Anything not listed falls back to the default.
    |
    */
    'default_domain' => env('PMS_VAULT_DOMAIN', 'pms'),

    'domains' => [
        'Campaign Insights MCP' => 'data-ml',
    ],
];
