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

        // TODO Make separate integrations for en and ru Telegram language
        // TODO Make separate admin dashboard for en/ru translations
        // TODO Make separate dashboard for TG contacts on conference

        $message = $this->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();

        DatabaseService::saveUser(
            $user->getId(),
            $user->getIsBot(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getUsername(),
            $user->getLanguageCode(),
            $user->getIsPremium(),
            $user->getAddedToAttachmentMenu()
        );

        DatabaseService::saveChat(
            $chat->getId(),
            $chat->getType(),
            $chat->getTitle(),
            $chat->getUsername(),
            $chat->getFirstName(),
            $chat->getLastName(),
            $chat->getIsForum(),
            $chat->allMembersAreAdministrators(),
            $chat->getOldId() // If available in your Chat object
        );

        DatabaseService::linkUserChat($user->getId(), $chat->getId());

        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();

        $keyboard = new InlineKeyboard(
            [
                ['text' => 'Get Tracker Invite Code', 'callback_data' => 'tracker_invite_code'],
            ],
            [
                ['text' => 'Contact AIO Sales Manager', 'url' => 'https://t.me/aio_presale'],
            ],
            [
                ['text' => 'Our Features', 'url' => 'https://aio.tech'],
            ],
        )
        ;

        $text = DatabaseService::getMessage('welcome_text');
        return Request::sendMessage([
            'chat_id'      => $chatId,
            'text'         => 'Hello, below you can get to know about us more',
            'reply_markup' => $keyboard,
            'parse_mode'   => 'HTML',
        ]);
    }
}