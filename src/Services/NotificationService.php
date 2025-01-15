<?php

namespace App\Services;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class NotificationService
 *
 * Sends Telegram notifications to a specified user or chat.
 */
class NotificationService
{
    private Telegram $telegram;

    /**
     * NotificationService constructor.
     *
     * @param string $botToken Telegram bot token
     * @param string $botUsername Telegram bot username
     * @throws TelegramException
     */
    public function __construct(string $botToken, string $botUsername)
    {
        $this->telegram = new Telegram($botToken, $botUsername);
    }

    /**
     * Send a message to a specific chat ID.
     *
     * @param int|string $chatId The user or group chat ID
     * @param string $message The message to send
     * @return bool
     * @throws TelegramException
     */
    public function notifyUser(int|string $chatId, string $message): bool
    {
        $data = [
            'chat_id' => $chatId,
            'text'    => $message,
        ];

        $result = Request::sendMessage($data);

        if (! $result->isOk()) {
            throw new \RuntimeException('SendMessage error code: ' . $result->getErrorCode() . ' SendMessage description: ' . $result->getDescription());
        }
        return $result->isOk();
    }
    public function sendLocation(int $chatId, float $latitude, float $longitude): bool
    {
        try {
            $data = [
                'chat_id' => $chatId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $response = Request::sendLocation($data);

            return $response->isOk();
        } catch (TelegramException $e) {
            throw new TelegramException('Failed to send location: ' . $e->getMessage());
        }
    }
}
