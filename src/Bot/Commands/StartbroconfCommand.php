<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
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
class StartbroconfCommand extends UserCommand
{
    protected $name = 'startbroconf';
    protected $description = 'Start command';
    protected $usage = '/startBroConf';
    protected $version = '2.0.0';

    /**
     * Execute the command.
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {

        // TODO Make separate integrations for en and ru Telegram language
        // TODO Make separate admin dashboard for en/ru translations
        // TODO Make separate dashboard for TG contacts on conference

        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();
        $username  = $message->getFrom()->getUsername();
        $fullname = $message->getFrom()->getFirstName() . ' ' . $message->getFrom()->getLastName();
        $timestamp = date('Y-m-d H:i:s');
        $languageCode  = $message->getFrom()->getLanguageCode();

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
            'text'         => 'Hey BroConf',
            'reply_markup' => $keyboard,
            'parse_mode'   => 'HTML',
        ]);
    }
}