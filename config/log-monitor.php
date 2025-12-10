<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Path
    |--------------------------------------------------------------------------
    |
    | The path to your Laravel log files. By default, this will use
    | storage_path('logs'), but you can override it here.
    |
    */
    'log_path' => env('LOG_MONITOR_PATH', storage_path('logs')),

    /*
    |--------------------------------------------------------------------------
    | Log Levels to Monitor
    |--------------------------------------------------------------------------
    |
    | Configure which log levels should trigger notifications and through
    | which channels. Available channels: 'telegram'
    |
    | Example:
    | 'levels' => [
    |     'critical' => ['telegram'],
    |     'error' => ['telegram'],
    |     'warning' => [],
    |     'notice' => ['telegram'],
    | ],
    |
    */
    'levels' => [
        'critical' => ['telegram'],
        'error' => ['telegram'],
        'warning' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Telegram bot for sending alerts.
    |
    | To get a bot token:
    | 1. Talk to @BotFather on Telegram
    | 2. Create a new bot with /newbot
    | 3. Copy the bot token
    |
    | To get your chat ID:
    | 1. Send a message to your bot
    | 2. Visit: https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates
    | 3. Look for "chat":{"id":123456789} in the response
    |
    */
    'telegram' => [
        'enabled' => env('LOG_MONITOR_TELEGRAM_ENABLED', true),
        'bot_token' => env('LOG_MONITOR_TELEGRAM_TOKEN'),
        'chat_id' => env('LOG_MONITOR_TELEGRAM_CHAT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling Configuration
    |--------------------------------------------------------------------------
    |
    | Prevent sending duplicate alerts for the same log entry within
    | a specified time period.
    |
    */
    'throttle' => [
        'enabled' => env('LOG_MONITOR_THROTTLE_ENABLED', true),
        'minutes' => env('LOG_MONITOR_THROTTLE_MINUTES', 5),
    ],
];

