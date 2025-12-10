<?php

namespace LaravelLogMonitor\Services;

class LogParser
{
    /**
     * Parse a log line and extract information.
     *
     * @param string $line
     * @return array|null Returns null if line doesn't match expected format
     */
    public function parse(string $line): ?array
    {
        // Pattern: [2025-12-09 16:05:50] production.WARNING: Something went wrong
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s+(.+)$/';

        if (!preg_match($pattern, trim($line), $matches)) {
            return null;
        }

        return [
            'timestamp' => $matches[1],
            'environment' => $matches[2],
            'level' => strtolower($matches[3]),
            'message' => $matches[4],
            'raw' => $line,
        ];
    }

    /**
     * Check if a log level matches the configured levels.
     *
     * @param string $level
     * @param array $configuredLevels
     * @return bool
     */
    public function matchesLevel(string $level, array $configuredLevels): bool
    {
        return in_array(strtolower($level), array_map('strtolower', $configuredLevels));
    }
}

