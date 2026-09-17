<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Kadry dostają e-mail dopiero, gdy paczka zmian przestanie rosnąć,
        // więc sprawdzamy co pięć minut zamiast wysyłać przy zapisie.
        $schedule->command('kadry:powiadom-mailem')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Kierownicy dostają w poniedziałek listę urlopów bez wniosku.
        $schedule->command('kadry:przypomnij-o-wnioskach')->weeklyOn(1, '07:00');

        // Rejestr logowań trzymamy rok — reszta znika sama.
        $schedule->command('logowania:posprzataj')->dailyAt('03:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
