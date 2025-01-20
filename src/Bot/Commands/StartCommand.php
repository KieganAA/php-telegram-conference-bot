<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
use App\Services\GoogleSheetService;
use Dflydev\DotAccessData\Data;
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
            $sheetService = GoogleSheetService::getInstance();
        } catch (Exception $e) {
            error_log('SheetServiceException: ' . $e->getMessage());
            throw new RuntimeException('SheetServiceException: ' . $e->getMessage());
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

        $keyboard = new InlineKeyboard(
            [
                ['text' => 'Contact AIO Sales Manager', 'url' => 'https://t.me/aio_presale'],
            ],
            [
                ['text' => 'Check AIO Official Website', 'url' => 'https://aio.tech'],
            ],
            [
                ['text' => 'View Additional Info', 'callback_data' => 'additional_info'],
            ],
        )
        ;

        $text = DatabaseService::getMessage('welcome_text');
        return Request::sendMessage([
            'chat_id'      => $chatId,
            'text'         => 'example',
            'reply_markup' => $keyboard,
        ]);
    }
}