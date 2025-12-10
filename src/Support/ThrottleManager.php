<?php

namespace LaravelLogMonitor\Support;

class ThrottleManager
{
    protected bool $enabled;
    protected int $minutes;
    protected array $cache = [];

    public function __construct(bool $enabled = true, int $minutes = 5)
    {
        $this->enabled = $enabled;
        $this->minutes = $minutes;
    }

    /**
     * Check if a log entry should be throttled.
     *
     * @param array $parsedLog
     * @return bool Returns true if should be throttled (skip), false if should send
     */
    public function shouldThrottle(array $parsedLog): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $key = $this->generateKey($parsedLog);
        $now = time();

        // Clean old entries
        $this->cleanCache($now);

        // Check if we've seen this recently
        if (isset($this->cache[$key])) {
            $lastSeen = $this->cache[$key];
            $diff = $now - $lastSeen;

            if ($diff < ($this->minutes * 60)) {
                return true; // Throttle
            }
        }

        // Update cache
        $this->cache[$key] = $now;

        return false; // Don't throttle
    }

    /**
     * Generate a unique key for a log entry.
     *
     * @param array $parsedLog
     * @return string
     */
    protected function generateKey(array $parsedLog): string
    {
        // Use level and message to create a unique key
        // This prevents duplicate alerts for the same error
        return md5($parsedLog['level'] . '|' . $parsedLog['message']);
    }

    /**
     * Clean old cache entries.
     *
     * @param int $now
     */
    protected function cleanCache(int $now): void
    {
        $threshold = $this->minutes * 60;

        foreach ($this->cache as $key => $timestamp) {
            if (($now - $timestamp) > $threshold) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Clear the throttle cache.
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}

