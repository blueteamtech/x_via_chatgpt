<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('auth.x'));
        // Stripe posts to /stripe/webhook and we verify authenticity via
        // signature instead of CSRF token.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // oauth/authorize is the only OAuth route a human sees, so it must redirect to
        // Sign in with X rather than return a 401 body ChatGPT cannot act on.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => ! $request->is('oauth/authorize')
                && ($request->is('api/*', 'mcp', 'oauth/*') || $request->expectsJson()),
        );
    })->create();
