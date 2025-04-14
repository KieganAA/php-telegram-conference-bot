<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
use Exception;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class StartCommand
 *
 * Handles the /start command.
 */
class StartbannererevanCommand extends UserCommand
{
    protected $name = 'startbannererevan';
    protected $description = 'Example command';
    protected $usage = '/startBannerErevan';
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
        // TODO Make separate integrations for en and ru Telegram language
        // TODO Make separate admin dashboard for en/ru translations
        // TODO Make separate dashboard for TG contacts on conference

        $message = $this->getMessage();
        $chat = $message->getChat();
        $chatId  = $message->getChat()->getId();
        $user = $message->getFrom();

        $textDB = DatabaseService::getMessage('welcome_text_erevan');
        $text = <<<TEXT
        Hey there!
        **With the buttons below, you can:**
        
        🔑 *Get Free Tracker Access*
        Receive your AIO invite code for instant registration
        
        📞 *Connect with Us*
        Speak directly with our AIO Sales Manager to:
        • Learn about our unique solutions
        • Schedule a demo call
        • Get answers to all your questions
        
        🌐 *Explore AIO Features*
        Discover our full range of features through our official website
        TEXT;

        $keyboard = new InlineKeyboard(
            [
                ['text' => 'Get Tracker Invite Code', 'callback_data' => 'tracker_invite_code'],
            ],
            [
                ['text' => 'Contact AIO Sales Manager', 'url' => 'https://t.me/aio_presale'],
            ],
            [
                ['text' => 'Our Features', 'url' => 'https://aio.tech/features'],
            ],
        );

        try {
            DatabaseService::saveUser(
                $user->getId(),
                $user->getIsBot(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getUsername(),
                $user->getLanguageCode(),
                'BannerErevan'
            );

            DatabaseService::saveChat(
                $chat->getId(),
                $chat->getUsername(),
                $chat->getFirstName(),
                $chat->getLastName()
            );

            DatabaseService::linkUserChat($user->getId(), $chat->getId());

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $textDB ?? $text,
                'reply_markup' => $keyboard,
                'parse_mode'   => 'Markdown',
            ]);
        } catch (Exception $e) {
            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $textDB ?? $text,
                'reply_markup' => $keyboard,
                'parse_mode'   => 'Markdown',
            ]);
        }
    }
}