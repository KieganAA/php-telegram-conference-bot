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
class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '2.0.0';

    /**
     * Execute the command.
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        try {
            $credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
            $spreadsheetId   = $_ENV['SPREADSHEET_ID'];
            $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);
            $sheetValues = $sheetService->getSpreadsheetValues('Registrations');
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }


        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();
        $text = $message->getText(true);

        $username  = $message->getFrom()->getUsername();
        $firstName  = $message->getFrom()->getFirstName();
        $lastName  = $message->getFrom()->getLastName();
        $languageCode  = $message->getFrom()->getLanguageCode();




        // TODO func to save to Sheet. Check uniques by chatId

        $baseUrl = $_ENV['BASE_URL'];
        $webAppUrl = sprintf('%s/demo-register-form.html', $baseUrl);
        $AioUrl = 'https://aio.tech';

        $keyboard = new InlineKeyboard([
            ['text' => 'Demo Registration', 'web_app' => ['url' => $webAppUrl]],
            ['text' => 'Wanna Talk?', 'callback_data' => 'call_aio_team'],
        ], [
            ['text' => 'Official Site', 'url' => $AioUrl],
        ]
        )
        ;

        $text = "Welcome to Dubai!\n"
            . "Our booth is at:\n"
            . "xxx_yyy_somewhere\n\n"
            . "Using this bot you can:\n"
            . "Visit our official site\n"
            . "Book a demo call with AIO team\n"
            . "Get a hold of one of team members to have a talk :)\n\n"
            . "If you need any at the booth, ask any member of AIO team.";

        return Request::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'reply_markup' => $keyboard,
        ]);
    }
}