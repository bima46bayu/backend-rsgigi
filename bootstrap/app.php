<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\CheckItemLocation;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // [PRODUCTION] Jadwal berjalan otomatis pada jam tertentu
        $schedule->command('inventory:check-alert')->dailyAt('07:00');
        $schedule->command('inventory:check-expiry')->daily();

        // [TESTING] Hapus tanda "//" di bawah jika ingin testing berjalan tiap 1 menit:
        // $schedule->command('inventory:check-alert')->everyMinute();
        // $schedule->command('inventory:check-expiry')->everyMinute();

        // [QUEUE WORKER] Eksekusi antrean setiap menit lalu langsung berhenti jika kosong
        $schedule->command('queue:work --stop-when-empty')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
    })
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();