<?php

/**
 * The deployment sits behind a platform proxy that terminates TLS and forwards
 * over HTTP. If the proxy is not trusted, Laravel builds http:// URLs into an
 * https:// page and browsers block every asset as mixed content.
 */
it('treats a request forwarded by the proxy as secure', function () {
    $this->get('/', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect('/admin');

    expect(request()->isSecure())->toBeTrue();
});

it('builds https urls for a request the proxy forwarded', function () {
    $this->get('/', ['X-Forwarded-Proto' => 'https']);

    expect(url('/css/filament/filament/app.css'))->toStartWith('https://');
});

it('leaves an unforwarded request on http', function () {
    $this->get('/');

    expect(request()->isSecure())->toBeFalse();
});
