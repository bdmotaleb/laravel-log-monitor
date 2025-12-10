<?php

namespace LaravelLogMonitor\Tests;

use LaravelLogMonitor\Support\ThrottleManager;
use PHPUnit\Framework\TestCase;

class ThrottleManagerTest extends TestCase
{
    public function test_throttles_duplicate_messages()
    {
        $manager = new ThrottleManager(true, 1); // 1 minute throttle

        $logEntry = [
            'level' => 'error',
            'message' => 'Database connection failed',
            'timestamp' => '2025-12-09 16:05:50',
        ];

        // First call should not throttle
        $this->assertFalse($manager->shouldThrottle($logEntry));

        // Second call with same message should throttle
        $this->assertTrue($manager->shouldThrottle($logEntry));
    }

    public function test_allows_different_messages()
    {
        $manager = new ThrottleManager(true, 1);

        $logEntry1 = [
            'level' => 'error',
            'message' => 'Database connection failed',
            'timestamp' => '2025-12-09 16:05:50',
        ];

        $logEntry2 = [
            'level' => 'error',
            'message' => 'Different error message',
            'timestamp' => '2025-12-09 16:05:51',
        ];

        $this->assertFalse($manager->shouldThrottle($logEntry1));
        $this->assertFalse($manager->shouldThrottle($logEntry2));
    }

    public function test_respects_disabled_throttling()
    {
        $manager = new ThrottleManager(false, 1);

        $logEntry = [
            'level' => 'error',
            'message' => 'Database connection failed',
            'timestamp' => '2025-12-09 16:05:50',
        ];

        // Should never throttle when disabled
        $this->assertFalse($manager->shouldThrottle($logEntry));
        $this->assertFalse($manager->shouldThrottle($logEntry));
        $this->assertFalse($manager->shouldThrottle($logEntry));
    }

    public function test_clears_cache()
    {
        $manager = new ThrottleManager(true, 1);

        $logEntry = [
            'level' => 'error',
            'message' => 'Database connection failed',
            'timestamp' => '2025-12-09 16:05:50',
        ];

        $manager->shouldThrottle($logEntry);
        $this->assertTrue($manager->shouldThrottle($logEntry));

        $manager->clear();
        $this->assertFalse($manager->shouldThrottle($logEntry));
    }
}

