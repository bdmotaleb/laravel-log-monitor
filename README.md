# Laravel Log Monitor

A Laravel package that monitors log files in real-time and sends alerts through Telegram when specific log levels appear.

## Features

- 🔍 **Real-time log monitoring** - Continuously watches Laravel log files
- 📱 **Telegram alerts** - Get instant notifications on your phone
- 🎯 **Level-based filtering** - Configure which log levels trigger alerts
- ⚡ **Efficient tailing** - Uses efficient file streaming (similar to `tail -f`)
- 🔄 **Auto rotation detection** - Automatically switches to new daily log files
- 🚫 **Throttling** - Prevents duplicate alerts for the same error
- 🧪 **Tested** - Includes unit tests for core functionality

## Installation

### Step 1: Install via Composer

```bash
composer require laravel-log-monitor/log-monitor
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=log-monitor-config
```

### Step 3: Configure Environment Variables

Add these to your `.env` file:

```env
# Telegram Configuration
LOG_MONITOR_TELEGRAM_ENABLED=true
LOG_MONITOR_TELEGRAM_TOKEN=your_bot_token_here
LOG_MONITOR_TELEGRAM_CHAT_ID=your_chat_id_here

# Optional: Custom log path
LOG_MONITOR_PATH=/path/to/logs

# Optional: Throttling (default: enabled, 5 minutes)
LOG_MONITOR_THROTTLE_ENABLED=true
LOG_MONITOR_THROTTLE_MINUTES=5
```

## Getting Telegram Credentials

### Bot Token

1. Open Telegram and search for [@BotFather](https://t.me/BotFather)
2. Send `/newbot` command
3. Follow the instructions to create your bot
4. Copy the bot token (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Chat ID

1. Send a message to your bot
2. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Look for `"chat":{"id":123456789}` in the JSON response
4. Copy the chat ID number

## Configuration

Edit `config/log-monitor.php` to customize monitoring:

```php
'levels' => [
    'critical' => ['telegram'],  // Send critical logs to Telegram
    'error' => ['telegram'],     // Send errors to Telegram
    'warning' => [],             // Don't send warnings
    'notice' => ['telegram'],    // Custom level example
],
```

## Usage

### Start Monitoring

Run the artisan command to start monitoring:

```bash
php artisan log-monitor:watch
```

This will continuously monitor your log files and send alerts when configured log levels are detected.

**Note:** By default, the watcher only processes **new** log entries added after it starts. To process existing log entries on startup, use:

```bash
php artisan log-monitor:watch --process-existing
```

This is useful when you first start the monitor or want to catch up on recent logs.

### Running as a Daemon

For production, you should run this as a daemon or use a process manager like Supervisor.

#### Using Supervisor

Create `/etc/supervisor/conf.d/laravel-log-monitor.conf`:

```ini
[program:laravel-log-monitor]
process_name=%(program_name)s
command=php /path/to/your/project/artisan log-monitor:watch
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/log-monitor.log
```

**Note:** For production, don't use `--process-existing` in Supervisor as it will process all logs every time the service restarts. Only use it manually when you need to catch up on existing logs.

Then reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-log-monitor
```

#### Using systemd

Create `/etc/systemd/system/laravel-log-monitor.service`:

```ini
[Unit]
Description=Laravel Log Monitor
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php artisan log-monitor:watch
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable laravel-log-monitor
sudo systemctl start laravel-log-monitor
```

## Command Options

```bash
# Process existing logs on startup (then continue monitoring)
php artisan log-monitor:watch --process-existing

# Run once and exit (useful for testing)
php artisan log-monitor:watch --once

# Stop on first error
php artisan log-monitor:watch --stop-on-error
```

## Log Format

The package expects Laravel's standard log format:

```
[2025-12-09 16:05:50] production.WARNING: Something went wrong
[2025-12-09 16:06:14] production.ERROR: Database connection failed
[2025-12-09 16:06:55] production.CRITICAL: Payment API down
```

## Message Format

Telegram messages are formatted as:

```
🚨 CRITICAL detected in production

⏰ Time: 2025-12-09 16:06:55
📝 Message:
Payment API down
```

## Throttling

The package includes throttling to prevent spam. By default, the same log message won't trigger an alert more than once every 5 minutes.

You can configure this in `config/log-monitor.php`:

```php
'throttle' => [
    'enabled' => true,
    'minutes' => 5,  // Change this value
],
```

## Testing

Run the test suite:

```bash
composer test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

## Architecture

### Services

- **LogParser**: Parses log lines and extracts timestamp, environment, level, and message
- **LogWatcher**: Monitors log files, handles rotation, and processes new entries
- **TelegramNotifier**: Sends formatted messages to Telegram
- **ThrottleManager**: Prevents duplicate alerts

### Facade

You can use the facade in your code:

```php
use LaravelLogMonitor\Facades\LogMonitor;

// Get the watcher instance
$watcher = LogMonitor::getWatcher();
```

## Troubleshooting

### No alerts are being sent

1. Check that Telegram credentials are correct in `.env`
2. Verify the log levels are configured in `config/log-monitor.php`
3. Check that the log file exists and is readable
4. Review Laravel logs for any errors

### Bot not responding

1. Make sure you've sent at least one message to your bot
2. Verify the chat ID is correct
3. Check that the bot token is valid

### File permission issues

Make sure the web server user can read the log files:

```bash
chmod 644 storage/logs/laravel-*.log
```

## Requirements

- PHP >= 8.1
- Laravel >= 10.0
- Guzzle HTTP Client (for Telegram API)

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please open an issue on GitHub.

