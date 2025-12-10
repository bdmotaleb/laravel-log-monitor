<?php

namespace LaravelLogMonitor\Notifications;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    protected Client $client;
    protected string $botToken;
    protected string $chatId;
    protected string $apiUrl = 'https://api.telegram.org/bot';

    public function __construct(string $botToken, string $chatId)
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->client = new Client([
            'timeout' => 10,
        ]);
    }

    /**
     * Send a notification to Telegram.
     *
     * @param array $parsedLog
     * @return bool
     */
    public function send(array $parsedLog): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('LogMonitor: Telegram credentials not configured');
            return false;
        }

        $message = $this->formatMessage($parsedLog);

        try {
            $response = $this->client->post("{$this->apiUrl}{$this->botToken}/sendMessage", [
                'json' => [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error('LogMonitor: Telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format the log entry into a Telegram message.
     *
     * @param array $parsedLog
     * @return string
     */
    protected function formatMessage(array $parsedLog): string
    {
        $level = strtoupper($parsedLog['level']);
        $emoji = $this->getEmojiForLevel($parsedLog['level']);

        $message = "{$emoji} <b>{$level}</b> detected in <b>{$parsedLog['environment']}</b>\n\n";
        $message .= "⏰ <b>Time:</b> {$parsedLog['timestamp']}\n";
        $message .= "📝 <b>Message:</b>\n<code>{$parsedLog['message']}</code>";

        return $message;
    }

    /**
     * Get emoji for log level.
     *
     * @param string $level
     * @return string
     */
    protected function getEmojiForLevel(string $level): string
    {
        $level = strtolower($level);
        
        switch ($level) {
            case 'critical':
                return '🚨';
            case 'error':
                return '❌';
            case 'warning':
                return '⚠️';
            case 'alert':
                return '🔔';
            case 'emergency':
                return '🆘';
            default:
                return '📌';
        }
    }
}

