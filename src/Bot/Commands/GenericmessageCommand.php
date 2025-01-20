<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
use Exception;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Utils\Helpers;
use App\Services\GoogleSheetService;
use RuntimeException;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handle all incoming messages';
    protected $version = '1.0.0';

    /**
     * @throws TelegramException
     * @throws Exception
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();
        $timestamp = date('Y-m-d H:i:s');

        try {
            $sheetService = GoogleSheetService::getInstance();
        } catch (Exception $e) {
            throw new RuntimeException('SheetServiceException: ' . $e->getMessage());
        }

        $text = DatabaseService::getMessage('thank_you_notification');

        if ($message->getLocation()) {
            $latitude = $message->getLocation()->getLatitude();
            $longitude = $message->getLocation()->getLongitude();

            $sheetService->appendOrUpdateRow([
                $chatId, '', '', '', '','','','', $latitude, $longitude, $timestamp
            ], 'Main');

            Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => Keyboard::remove(),
                'parse_mode' => 'Markdown',
            ]);

            $telegram = $this->telegram;
            Helpers::fakeCallback($chatId, $message, $telegram, 'call_aio_team_location');

            return Request::emptyResponse();
        }

        if ($message->getText(true) == 'No') {
            $sheetService->appendOrUpdateRow([
                $chatId, '', '', '', '','','','', 'No Location', 'No Location', $timestamp
            ], 'Main');

            Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => Keyboard::remove(),
                'parse_mode' => 'Markdown',
            ]);
            $telegram = $this->telegram;
            Helpers::fakeCallback($chatId, $message, $telegram, 'call_aio_team_location');
            return Request::emptyResponse();
        }
        return Request::emptyResponse();
    }
}
