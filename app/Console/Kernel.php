<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\GenerateLibraryQRCode::class, // تأكد من إضافة الـ command هنا
    ];

    protected function schedule(Schedule $schedule)
{
    $schedule->command('library:generate-qr')->everyFiveSeconds();
}

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
