<?php

namespace LaravelLogMonitor\Commands;

use Illuminate\Console\Command;
use LaravelLogMonitor\Services\LogWatcher;

class LogMonitorWatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-monitor:watch 
                            {--stop-on-error : Stop watching on first error}
                            {--once : Run once and exit (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start monitoring Laravel log files and send alerts';

    /**
     * Execute the console command.
     */
    public function handle(LogWatcher $watcher): int
    {
        $this->info('Starting log monitor...');
        $this->info('Press Ctrl+C to stop.');

        // Handle graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use ($watcher) {
                $this->info("\nStopping log monitor...");
                $watcher->stop();
                exit(0);
            });
            pcntl_signal(SIGTERM, function () use ($watcher) {
                $this->info("\nStopping log monitor...");
                $watcher->stop();
                exit(0);
            });
        }

        try {
            if ($this->option('once')) {
                // For testing: read existing logs once
                $this->info('Running in test mode (once)...');
                $watcher->watch();
                return Command::SUCCESS;
            }

            // Continuous monitoring
            $watcher->watch();
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());

            if ($this->option('stop-on-error')) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}

