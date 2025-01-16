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
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();

        $username  = $message->getFrom()->getUsername();
        $fullname = $message->getFrom()->getFirstName() . ' ' . $message->getFrom()->getLastName();
        $timestamp = date('Y-m-d H:i:s');
        $languageCode  = $message->getFrom()->getLanguageCode();

        $sheetService->appendOrUpdateRow([
            $chatId, $fullname, $username, $languageCode, '','','','','','', $timestamp
        ], 'Main');

        $baseUrl = $_ENV['BASE_URL'];
        $webAppUrl = sprintf('%s/demo-register-form.html?chatId=%s', $baseUrl, $chatId);
        $AioUrl = 'https://aio.tech';

        $keyboard = new InlineKeyboard([
            ['text' => 'Demo', 'web_app' => ['url' => $webAppUrl]],
            ['text' => 'Wanna Talk?', 'callback_data' => 'want_to_talk'],
        ], [
            ['text' => 'Official Site', 'url' => $AioUrl],
        ]
        )
        ;

        $text = "Welcome to Dubai!\n"
            . "Our booth is at:\n"
            . "xxx_yyy_somewhere\n\n"
            . "blablablablablablablablabla:\n"
            . "Visit our official site\n"
            . "Book a Demo\n"
            . "Talk with us\n\n"
            . "blablablablablablablablabla";

        return Request::sendMessage([
            'chat_id'      => $chatId,
            'text'         => $text,
            'reply_markup' => $keyboard,
        ]);
    }
}