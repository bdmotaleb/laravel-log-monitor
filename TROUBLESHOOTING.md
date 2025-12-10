# Troubleshooting Guide

## No Alerts Being Received

If you're not receiving alerts, follow these steps:

### 1. Check Configuration

First, make sure you've published the config file:

```bash
php artisan vendor:publish --tag=log-monitor-config
```

### 2. Verify Telegram Credentials

Add these to your `.env` file:

```env
LOG_MONITOR_TELEGRAM_ENABLED=true
LOG_MONITOR_TELEGRAM_TOKEN=your_bot_token_here
LOG_MONITOR_TELEGRAM_CHAT_ID=your_chat_id_here
```

**To get your bot token:**
1. Talk to [@BotFather](https://t.me/BotFather) on Telegram
2. Send `/newbot` command
3. Follow instructions and copy the token

**To get your chat ID:**
1. Send a message to your bot
2. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Look for `"chat":{"id":123456789}` in the response

### 3. Check Log Levels Configuration

Verify in `config/log-monitor.php` that your log levels are configured:

```php
'levels' => [
    'critical' => ['telegram'],  // ✅ Will send alerts
    'error' => ['telegram'],     // ✅ Will send alerts
    'warning' => [],             // ❌ Won't send alerts (empty array)
],
```

### 4. Run the Watch Command

**IMPORTANT:** The watch command must be running continuously!

```bash
php artisan log-monitor:watch
```

This command will:
- Show configuration status
- Validate Telegram credentials
- Start monitoring for new log entries

### 5. Process Existing Logs

By default, the watcher only processes **new** log entries added after it starts. To process existing logs:

```bash
php artisan log-monitor:watch --process-existing
```

This will:
1. Process all existing log entries in the current log file
2. Then continue monitoring for new entries

### 6. Test with New Log Entry

After starting the watcher, create a test log entry:

```php
// In your Laravel app
\Log::error('Test error message');
\Log::critical('Test critical message');
```

### 7. Check for Errors

The command will show warnings if:
- Telegram token is missing
- Chat ID is missing
- No log levels are configured

Look for messages like:
- `⚠️ Telegram bot token is not configured!`
- `❌ Telegram notifications will not work without proper configuration!`

### 8. Verify Log File Path

The package looks for logs in `storage/logs/laravel-YYYY-MM-DD.log`. 

If your logs are in a different location, set:

```env
LOG_MONITOR_PATH=/path/to/your/logs
```

### 9. Check Throttling

By default, duplicate alerts are throttled for 5 minutes. If you're testing with the same message, wait 5 minutes or disable throttling:

```env
LOG_MONITOR_THROTTLE_ENABLED=false
```

### 10. Run in Test Mode

For debugging, run once to see what happens:

```bash
php artisan log-monitor:watch --once --process-existing
```

## Common Issues

### Issue: "Telegram credentials not configured"

**Solution:** Make sure your `.env` file has:
```env
LOG_MONITOR_TELEGRAM_TOKEN=your_token
LOG_MONITOR_TELEGRAM_CHAT_ID=your_chat_id
```

Then clear config cache:
```bash
php artisan config:clear
```

### Issue: "No alerts for existing logs"

**Solution:** The watcher only reads new entries by default. Use:
```bash
php artisan log-monitor:watch --process-existing
```

### Issue: "Command stops immediately"

**Solution:** The command should run continuously. If it exits, check for errors in the output. Make sure you're not running it with `--once` flag unless testing.

### Issue: "Alerts not sent for certain levels"

**Solution:** Check `config/log-monitor.php` - the level must have at least one channel configured:
```php
'error' => ['telegram'],  // ✅ Will send
'warning' => [],          // ❌ Won't send
```

## Running as a Daemon

For production, run the watcher as a background service using Supervisor or systemd (see README.md for details).

## Still Not Working?

1. Check Laravel logs: `storage/logs/laravel.log`
2. Look for errors starting with "LogMonitor:"
3. Verify the log file exists and is readable
4. Test Telegram API directly:
   ```bash
   curl -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" \
     -d "chat_id=<CHAT_ID>&text=Test"
   ```

