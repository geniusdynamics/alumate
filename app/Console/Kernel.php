<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Console Kernel - Defines scheduled commands for the application
 *
 * This kernel includes backup scheduling for automated disaster recovery
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<class-string>
     */
    protected $commands = [
        Commands\BackupSchedulerCommand::class,
        Commands\BackupRestoreCommand::class,
        Commands\BackupVerifyCommand::class,
        Commands\BackupCleanupCommand::class,
        Commands\CleanUpLogs::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Database backup - Daily at 1:00 AM
        $schedule->command('backup:schedule --type=database')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'))
            ->onSuccess(function () {
                // Log successful backup
            })
            ->onFailure(function () {
                // Log failed backup - already handled by notification
            });

        // Files backup - Daily at 2:00 AM
        $schedule->command('backup:schedule --type=files')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Configuration backup - Weekly on Monday at 3:00 AM
        $schedule->command('backup:schedule --type=config')
            ->weeklyOn(Schedule::MONDAY, '03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Backup cleanup - Daily at 5:00 AM
        $schedule->command('backup:cleanup')
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Database backup verification - Weekly on Sunday at 6:00 AM
        $schedule->command('backup:verify')
            ->weeklyOn(Schedule::SUNDAY, '06:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Log cleanup - Daily at 3:00 AM
        $schedule->command('logs:cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
