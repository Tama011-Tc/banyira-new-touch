<?php

use App\Http\Middleware\EnsureCustomerType;
use App\Http\Middleware\EnsureStaffSession;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hosting platforms terminate HTTPS at their proxy and forward the original scheme.
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [HandleInertiaRequests::class, SecurityHeaders::class]);
        $middleware->alias(['customer' => EnsureCustomerType::class, 'staff' => EnsureStaffSession::class]);
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(fn (Request $r) => $r->is('internal', 'internal/*') ? '/internal-access' : '/login');
        $middleware->redirectUsersTo(fn (Request $r) => $r->is('internal-access*') ? '/internal' : (auth('web')->user()?->type === 'investor' ? '/investor' : '/business'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*', 'webhooks/*') || $request->expectsJson());

        // Friendly error pages. Server errors keep the debug page in local development.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            $friendly = in_array($status, [403, 404], true) || (in_array($status, [500, 503], true) && ! app()->environment('local'));
            if ($friendly && ! $request->expectsJson()) {
                return Inertia::render('Error', ['status' => $status])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
