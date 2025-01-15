<?php
namespace App\Bot\Commands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Conversation;

/**
 * This command catches all callback queries for the entire bot.
 */
class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';

    public function execute(): \Longman\TelegramBot\Entities\ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();
        $chatId        = $callbackQuery->getMessage()->getChat()->getId();
        $userId        = $callbackQuery->getFrom()->getId();

        // If the callback is for our "demoregister" confirmation
        if (in_array($callbackData, ['confirm_yes', 'confirm_no'], true)) {
            $conversation = new Conversation($chatId, $userId, 'demoregister');
            $notes = $conversation->notes;

            if ($callbackData === 'confirm_yes') {
                // Save to sheet
                // ...
                $conversation->stop();
                return Request::editMessageText([
                    'chat_id'      => $chatId,
                    'message_id'   => $callbackQuery->getMessage()->getMessageId(),
                    'text'         => "Perfect! Your demo registration has been recorded.",
                ]);
            }

            if ($callbackData === 'confirm_no') {
                $conversation->stop();
                return Request::editMessageText([
                    'chat_id'    => $chatId,
                    'message_id' => $callbackQuery->getMessage()->getMessageId(),
                    'text'       => "Registration cancelled. Use /demoregister to restart.",
                ]);
            }
        }

        // Otherwise handle other callback data...
        return Request::emptyResponse();
    }
}
