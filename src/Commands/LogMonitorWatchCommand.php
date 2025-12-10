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
                            {--once : Run once and exit (for testing)}
                            {--process-existing : Process existing log entries on startup}';

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
        // Validate configuration
        $this->validateConfiguration();

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
                if ($this->option('process-existing')) {
                    $watcher->processExistingLogs();
                }
                $watcher->watch();
                return Command::SUCCESS;
            }

            // Process existing logs if requested
            if ($this->option('process-existing')) {
                $this->info('Processing existing log entries...');
                $watcher->processExistingLogs();
                $this->info('Now monitoring for new entries...');
            } else {
                $this->info('Monitoring for new log entries only...');
                $this->info('(Use --process-existing to process existing logs)');
            }

            // Continuous monitoring
            $this->info('Watching log files...');
            $watcher->watch();
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());

            if ($this->option('stop-on-error')) {
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfiguration(): void
    {
        $config = config('log-monitor');

        // Check if Telegram is enabled and configured
        if ($config['telegram']['enabled'] ?? false) {
            if (empty($config['telegram']['bot_token'])) {
                $this->warn('⚠️  Telegram bot token is not configured!');
                $this->warn('   Set LOG_MONITOR_TELEGRAM_TOKEN in your .env file');
            }

            if (empty($config['telegram']['chat_id'])) {
                $this->warn('⚠️  Telegram chat ID is not configured!');
                $this->warn('   Set LOG_MONITOR_TELEGRAM_CHAT_ID in your .env file');
            }

            if (empty($config['telegram']['bot_token']) || empty($config['telegram']['chat_id'])) {
                $this->error('❌ Telegram notifications will not work without proper configuration!');
            } else {
                $this->info('✅ Telegram configuration looks good');
            }
        }

        // Check configured levels
        $levels = $config['levels'] ?? [];
        $activeLevels = array_filter($levels, function ($channels) {
            return !empty($channels);
        });

        if (empty($activeLevels)) {
            $this->warn('⚠️  No log levels are configured to send notifications!');
            $this->warn('   Configure levels in config/log-monitor.php');
        } else {
            $this->info('📊 Monitoring levels: ' . implode(', ', array_keys($activeLevels)));
        }
    }
}

