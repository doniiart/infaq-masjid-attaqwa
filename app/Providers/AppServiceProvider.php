<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Login::class, function (Login $event) {
            AuditLogger::log(
                'auth.login',
                "Pengguna {$event->user->name} ({$event->user->email}) berhasil login.",
                $event->user,
                null,
                null,
                $event->user->id
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLogger::log(
                    'auth.logout',
                    "Pengguna {$event->user->name} ({$event->user->email}) logout dari sistem.",
                    $event->user,
                    null,
                    null,
                    $event->user->id
                );
            }
        });
    }
}