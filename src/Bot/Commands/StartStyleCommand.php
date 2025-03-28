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
class StartStyleCommand extends UserCommand
{
    protected $name = 'startstyle';
    protected $description = 'Start style configuration';
    protected $usage = '/startStyle';
    protected $version = '1.0.0';

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
        return Request::sendMessage([
            'chat_id'      => $chatId,
            'text'         => 'Hey Style',
            'parse_mode'   => 'HTML',
        ]);
    }
}