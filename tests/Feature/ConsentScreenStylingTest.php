<?php

/**
 * The OAuth consent screen is published from laravel/mcp and is written against
 * a shadcn-style palette. Tailwind emits nothing for a colour utility whose
 * token is undefined, so a missing token does not fail the build: the page just
 * renders unstyled, which reads as a blank screen to whoever is approving the
 * connection.
 */
it('defines a theme token for every colour utility the consent screen uses', function () {
    $view = file_get_contents(resource_path('views/mcp/authorize.blade.php'));
    $stylesheet = file_get_contents(resource_path('css/app.css'));

    preg_match_all(
        '/\b(?:bg|text|border|ring|divide|from|via|to)-(background|foreground|card|card-foreground|muted|muted-foreground|primary|primary-foreground|secondary|secondary-foreground|accent|accent-foreground|destructive|destructive-foreground|border|input|ring)\b/',
        $view,
        $matches
    );

    $tokens = array_unique($matches[1]);

    expect($tokens)->not->toBeEmpty();

    $undefined = array_values(array_filter(
        $tokens,
        fn (string $token): bool => ! str_contains($stylesheet, "--color-{$token}:")
    ));

    expect($undefined)->toBe([]);
});
