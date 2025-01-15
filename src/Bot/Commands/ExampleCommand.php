<?php

namespace App\Bot\Commands;

use App\Services\GoogleSheetService;
use Exception;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use RuntimeException;

/**
 * Class StartCommand
 *
 * Handles the /start command.
 */
class ExampleCommand extends UserCommand
{
    protected $name = 'example';
    protected $description = 'Example command';
    protected $usage = '/example';
    protected $version = '1.0.0';

    /**
     * Execute the command.
     *
     * @return ServerResponse
     * @throws TelegramException
     * @throws Exception
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();
        $username  = $message->getFrom()->getUsername();
        $text    = trim($message->getText(true));

        $credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
        $spreadsheetId   = $_ENV['SPREADSHEET_ID'];

        try {
            $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);
            $sheetValues = $sheetService->getSpreadsheetValues('Registrations');
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $row = [
            $chatId,
            $username,
            $text,
            date('Y-m-d H:i:s'),
        ];

        try {
            $sheetService->appendRow($row, 'Registrations!A2');
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $textResponse = "Example command run successfully!\n";

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $textResponse,
        ]);
    }
}