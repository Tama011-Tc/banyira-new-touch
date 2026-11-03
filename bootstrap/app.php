<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureActiveAccount::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
        // Provider webhooks are authenticated by HMAC signature, not by a browser session or CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        // Public-site language choice ("en" or "ny"); nothing secret, so it stays readable.
        $middleware->encryptCookies(except: ['site_locale']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
