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
class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
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

        $text = "Welcome to the Conference Bot!\n"
            . "Use /demoregister to sign up for a demo.\n"
            . "If you need help at the booth, ask a staff member or scan the QR code.";

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }
}