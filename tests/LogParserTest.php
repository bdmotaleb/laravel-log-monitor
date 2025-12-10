<?php

namespace LaravelLogMonitor\Tests;

use LaravelLogMonitor\Services\LogParser;
use PHPUnit\Framework\TestCase;

class LogParserTest extends TestCase
{
    protected LogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LogParser();
    }

    public function test_parses_valid_log_line()
    {
        $line = '[2025-12-09 16:05:50] production.WARNING: Something went wrong';
        $result = $this->parser->parse($line);

        $this->assertNotNull($result);
        $this->assertEquals('2025-12-09 16:05:50', $result['timestamp']);
        $this->assertEquals('production', $result['environment']);
        $this->assertEquals('warning', $result['level']);
        $this->assertEquals('Something went wrong', $result['message']);
    }

    public function test_parses_error_level()
    {
        $line = '[2025-12-09 16:06:14] production.ERROR: Database connection failed';
        $result = $this->parser->parse($line);

        $this->assertNotNull($result);
        $this->assertEquals('error', $result['level']);
        $this->assertEquals('Database connection failed', $result['message']);
    }

    public function test_parses_critical_level()
    {
        $line = '[2025-12-09 16:06:55] production.CRITICAL: Payment API down';
        $result = $this->parser->parse($line);

        $this->assertNotNull($result);
        $this->assertEquals('critical', $result['level']);
        $this->assertEquals('Payment API down', $result['message']);
    }

    public function test_returns_null_for_invalid_format()
    {
        $line = 'This is not a valid log line';
        $result = $this->parser->parse($line);

        $this->assertNull($result);
    }

    public function test_handles_whitespace()
    {
        $line = '   [2025-12-09 16:05:50] production.WARNING: Something went wrong   ';
        $result = $this->parser->parse($line);

        $this->assertNotNull($result);
        $this->assertEquals('warning', $result['level']);
    }

    public function test_matches_level_case_insensitive()
    {
        $this->assertTrue($this->parser->matchesLevel('ERROR', ['error', 'critical']));
        $this->assertTrue($this->parser->matchesLevel('error', ['ERROR', 'CRITICAL']));
        $this->assertFalse($this->parser->matchesLevel('info', ['error', 'critical']));
    }
}

