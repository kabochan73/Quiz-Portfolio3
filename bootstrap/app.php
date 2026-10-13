<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ログイン済みでログイン画面を開いたときの移動先(requirements.md 3.1.1)
        $middleware->redirectUsersTo(fn () => route('home'));

        // 本番(Railway)では、前に立つプロキシが HTTPS を受けてアプリへ渡す。
        // プロキシからのヘッダーを信頼しないと、URL やリダイレクト先が http になってしまう(architecture.md 8.5)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
