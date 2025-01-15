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
     * @param string|int $chatId The user or group chat ID
     * @param string $message The message to send
     * @return bool
     * @throws TelegramException
     */
    public function notifyUser($chatId, string $message): bool
    {
        $data = [
            'chat_id' => $chatId,
            'text'    => $message,
        ];

        $result = Request::sendMessage($data);

        return $result->isOk();
    }
}
