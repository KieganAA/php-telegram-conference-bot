<?php

namespace App\Bot\Commands;

use Longman\TelegramBot\Commands\UserCommand;
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
     */
    public function execute(): ServerResponse
    {
        $chatId = $this->getMessage()->getChat()->getId();
        $messageText = $this->getMessage()->getProperty('text');



        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $messageText,
        ]);
    }
}