<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
use Exception;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\CallbackQuery;

/**
 * Class CallbackqueryCommand
 *
 * This command handles incoming callback queries from inline buttons.
 */
class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Handle the inline button callbacks';
    protected $version = '1.0.0';

    /**
     * @throws TelegramException
     * @throws Exception
     */
    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $userId = $callbackQuery->getFrom()->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();

        if ($callbackData === 'tracker_invite_code') {
            $existingCodes = DatabaseService::getCodesByUser($userId);

            if (!empty($existingCodes)) {
                $trackerInviteCode = $existingCodes[0]['code'];
                $text = DatabaseService::getMessage('tracker_invite_code_exists')
                    ?: "Your already existing invite code:";
            } else {
                //$trackerInviteCode = DatabaseService::getUnusedInviteCode();
                $trackerInviteCode = '4149';

                if (!$trackerInviteCode) {
                    Request::answerCallbackQuery([
                        'callback_query_id' => $callbackQuery->getId(),
                        'text' => 'All invite codes have been claimed!',
                        'show_alert' => true,
                    ]);
                    return Request::emptyResponse();
                }

//                $success = DatabaseService::markCodeAsUsed(
//                    $trackerInviteCode,
//                    $userId,
//                    $chatId
//                );
                $success = true;

                if (!$success) {
                    Request::answerCallbackQuery([
                        'callback_query_id' => $callbackQuery->getId(),
                        'text' => 'Error claiming code, please try again',
                        'show_alert' => true,
                    ]);
                    return Request::emptyResponse();
                }

                $text = DatabaseService::getMessage('tracker_invite_code_success')
                    ?: "Your exclusive invite code:";
            }

            $keyboard = new InlineKeyboard([
                [
                    'text' => 'Use Invite Code',
                    'url' => 'https://app.aio.tech/auth/register?invite_code=' . $trackerInviteCode
                ]
            ]);

            return Request::editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text . PHP_EOL . "<code>$trackerInviteCode</code>",
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'
            ]);
        }

        return Request::emptyResponse();
    }
}