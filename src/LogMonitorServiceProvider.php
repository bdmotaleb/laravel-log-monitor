<?php

namespace LaravelLogMonitor;

use Illuminate\Support\ServiceProvider;
use LaravelLogMonitor\Services\LogWatcher;
use LaravelLogMonitor\Services\LogParser;
use LaravelLogMonitor\Notifications\TelegramNotifier;
use LaravelLogMonitor\Support\ThrottleManager;

class LogMonitorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/log-monitor.php',
            'log-monitor'
        );

        $this->app->singleton(LogParser::class, function ($app) {
            return new LogParser();
        });

        $this->app->singleton(ThrottleManager::class, function ($app) {
            $config = $app['config']->get('log-monitor.throttle', []);
            return new ThrottleManager(
                $config['enabled'] ?? true,
                $config['minutes'] ?? 5
            );
        });

        $this->app->singleton(TelegramNotifier::class, function ($app) {
            $config = $app['config']->get('log-monitor.telegram', []);
            return new TelegramNotifier(
                $config['bot_token'] ?? '',
                $config['chat_id'] ?? ''
            );
        });

        $this->app->singleton(LogWatcher::class, function ($app) {
            return new LogWatcher(
                $app->make(LogParser::class),
                $app->make(ThrottleManager::class),
                $app->make(TelegramNotifier::class),
                $app['config']->get('log-monitor', [])
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/log-monitor.php' => config_path('log-monitor.php'),
            ], 'log-monitor-config');

            $this->commands([
                Commands\LogMonitorWatchCommand::class,
            ]);
        }
    }
}

