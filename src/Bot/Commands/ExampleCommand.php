<?php

namespace App\Bot\Commands;

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
    protected $name = 'startStyle';
    protected $description = 'Example command';
    protected $usage = '/startStyle';
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
        $timestamp = date('Y-m-d H:i:s');
        $chatId  = $message->getChat()->getId();
        $messageId     = $message->getMessageId();
        $userId        = $message->getFrom()->getId();
        $username  = $message->getFrom()->getUsername();
        $language = $this->getMessage()->getFrom()->getLanguageCode();
        $text    = trim($message->getText(true));

        $textResponse = "Example command run successfully!\n ";

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $textResponse,
        ]);
    }
}