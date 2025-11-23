<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting()
    ->withMiddleware(function (Middleware $middleware): void {
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
