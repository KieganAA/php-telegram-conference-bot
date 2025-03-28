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

        if ($message->getText(true) == 'No') {
            Request::sendMessage([
                'chat_id' => $chatId,
                'text' => 'Yes',
                'reply_markup' => Keyboard::remove(),
                'parse_mode' => 'Markdown',
            ]);
        }
        return Request::emptyResponse();
    }
}
