<?php

namespace LaravelLogMonitor\Services;

use LaravelLogMonitor\Notifications\TelegramNotifier;
use LaravelLogMonitor\Support\ThrottleManager;
use Illuminate\Support\Facades\Log;

class LogWatcher
{
    protected LogParser $parser;
    protected ThrottleManager $throttleManager;
    protected TelegramNotifier $telegramNotifier;
    protected array $config;
    protected $fileHandle = null;
    protected string $currentLogFile = '';
    protected bool $shouldStop = false;

    public function __construct(
        LogParser $parser,
        ThrottleManager $throttleManager,
        TelegramNotifier $telegramNotifier,
        array $config
    ) {
        $this->parser = $parser;
        $this->throttleManager = $throttleManager;
        $this->telegramNotifier = $telegramNotifier;
        $this->config = $config;
    }

    /**
     * Start watching log files.
     */
    public function watch(): void
    {
        $this->shouldStop = false;
        $logPath = $this->getLogPath();

        while (!$this->shouldStop) {
            $logFile = $this->getCurrentLogFile($logPath);

            if ($logFile !== $this->currentLogFile) {
                $this->switchLogFile($logFile);
            }

            if ($this->fileHandle) {
                $this->readNewLines();
            }

            usleep(500000); // Sleep 0.5 seconds
        }

        $this->closeFile();
    }

    /**
     * Stop watching.
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Get the watcher instance (for facade compatibility).
     *
     * @return self
     */
    public function getWatcher(): self
    {
        return $this;
    }

    /**
     * Get the log directory path.
     */
    protected function getLogPath(): string
    {
        $customPath = $this->config['log_path'] ?? null;
        
        if ($customPath) {
            return $customPath;
        }

        return storage_path('logs');
    }

    /**
     * Get the current log file (most recent daily log).
     */
    protected function getCurrentLogFile(string $logPath): string
    {
        $today = date('Y-m-d');
        $logFile = $logPath . DIRECTORY_SEPARATOR . "laravel-{$today}.log";

        // If today's file doesn't exist, try to find the most recent one
        if (!file_exists($logFile)) {
            $files = glob($logPath . DIRECTORY_SEPARATOR . 'laravel-*.log');
            if (!empty($files)) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $logFile = $files[0];
            }
        }

        // Return the expected log file path (may not exist yet)
        return $logFile;
    }

    /**
     * Switch to a new log file.
     */
    protected function switchLogFile(string $logFile): void
    {
        $this->closeFile();

        if (file_exists($logFile)) {
            $this->currentLogFile = $logFile;
            $this->fileHandle = fopen($logFile, 'r');

            if ($this->fileHandle) {
                // Move to end of file
                fseek($this->fileHandle, 0, SEEK_END);
            }
        }
    }

    /**
     * Read new lines from the current log file.
     */
    protected function readNewLines(): void
    {
        if (!$this->fileHandle) {
            return;
        }

        while (($line = fgets($this->fileHandle)) !== false) {
            $this->processLine($line);
        }
    }

    /**
     * Process a log line.
     */
    protected function processLine(string $line): void
    {
        $parsed = $this->parser->parse($line);

        if (!$parsed) {
            return;
        }

        $level = $parsed['level'];
        $configuredLevels = array_keys($this->config['levels'] ?? []);

        if (!$this->parser->matchesLevel($level, $configuredLevels)) {
            return;
        }

        // Check throttling
        if ($this->throttleManager->shouldThrottle($parsed)) {
            return;
        }

        // Get channels for this level
        $channels = $this->config['levels'][$level] ?? [];

        // Send notifications
        $this->sendNotifications($parsed, $channels);
    }

    /**
     * Send notifications through configured channels.
     */
    protected function sendNotifications(array $parsed, array $channels): void
    {
        foreach ($channels as $channel) {
            try {
                if ($channel === 'telegram' && $this->config['telegram']['enabled'] ?? false) {
                    $this->telegramNotifier->send($parsed);
                }
            } catch (\Exception $e) {
                Log::error('LogMonitor: Failed to send notification', [
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Close the current file handle.
     */
    protected function closeFile(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Cleanup on destruction.
     */
    public function __destruct()
    {
        $this->closeFile();
    }
}

