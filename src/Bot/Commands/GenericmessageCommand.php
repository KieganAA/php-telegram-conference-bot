<?php

namespace App\Bot\Commands;

use Exception;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use App\Utils\Helpers;
use App\Services\GoogleSheetService;

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

        $credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
        $spreadsheetId   = $_ENV['SPREADSHEET_ID'];
        $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);

        if ($message->getLocation()) {
            $latitude = $message->getLocation()->getLatitude();
            $longitude = $message->getLocation()->getLongitude();

            $sheetService->appendOrUpdateRow([
                $chatId, '', '', '', '','','','', $latitude, $longitude, $timestamp
            ], 'Main');

            Request::sendMessage([
                'chat_id' => $chatId,
                'text' => "Thank you!\n\nNotifying the team...",
                'reply_markup' => Keyboard::remove(),
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
                'text' => "Thank you!\n\nNotifying the team...",
                'reply_markup' => Keyboard::remove(),
            ]);
            $telegram = $this->telegram;
            Helpers::fakeCallback($chatId, $message, $telegram, 'call_aio_team_location');
            return Request::emptyResponse();
        }
        return Request::emptyResponse();
    }
}
