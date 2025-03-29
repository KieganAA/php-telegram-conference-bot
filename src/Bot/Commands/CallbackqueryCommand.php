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
use RuntimeException;

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
        $callbackData  = $callbackQuery->getData();
        $chatId        = $callbackQuery->getMessage()->getChat()->getId();
        $messageId     = $callbackQuery->getMessage()->getMessageId();
        $userId        = $callbackQuery->getFrom()->getId();

        if ($callbackData === 'tracker_invite_code') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert' => false,
            ]);

            //$text = DatabaseService::getMessage('tracker_invite_code');

            $trackerInviteCode = 'test';

            $keyboard = new InlineKeyboard(
                [
                    ['text' => 'Use Tracker Invite Code', 'url' => 'https//app.aio.tech?invite_code='],
                ]
            );

            return Request::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Hello, below you can get to know about us more',
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML',
            ]);}

        return Request::emptyResponse();}}
