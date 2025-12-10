<?php

namespace LaravelLogMonitor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void watch()
 * @method static \LaravelLogMonitor\Services\LogWatcher getWatcher()
 *
 * @see \LaravelLogMonitor\Services\LogWatcher
 */
class LogMonitor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \LaravelLogMonitor\Services\LogWatcher::class;
    }
}

