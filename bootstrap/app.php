<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 月初に定期支出を自動生成
        $schedule->command('recurring:generate')
            ->monthlyOn(1, '00:00')
            ->timezone('Asia/Tokyo');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
