<?php

namespace App\Bot\Callbacks;

use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * Class CallbackQueryHandler
 *
 * Example of how to handle callback queries (e.g., from inline keyboards).
 * You can integrate this with the main BotHandler, or load via custom logic.
 */
class CallbackQueryHandler
{
    /**
     * Process an incoming callback query.
     *
     * @param CallbackQuery $callbackQuery
     * @throws TelegramException
     */
    public function processCallback(CallbackQuery $callbackQuery): void
    {
        $callbackData = $callbackQuery->getData();
        $chatId       = $callbackQuery->getMessage()->getChat()->getId();

        // Example: If $callbackData is 'confirm_demo'
        if ($callbackData === 'confirm_demo') {
            $this->sendConfirmation($chatId);
        } else {
            $this->sendUnknownCallback($chatId);
        }
    }

    /**
     * Send a confirmation response.
     *
     * @param int $chatId
     * @throws TelegramException
     */
    private function sendConfirmation(int $chatId): void
    {
        Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => "Thank you for confirming your demo!",
        ]);
    }

    /**
     * Handle unrecognized callback data.
     *
     * @param int $chatId
     * @throws TelegramException
     */
    private function sendUnknownCallback(int $chatId): void
    {
        Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => "Sorry, I didn't understand that action.",
        ]);
    }
}
