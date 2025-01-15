<?php

namespace App\Bot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\CallbackQuery;

/**
 * Class CallbackqueryCommand
 *
 * This command handles incoming callback queries from inline buttons.
 */
class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Handle the inline button callbacks';
    protected $version = '1.0.0';

    /**
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();
        $chatId        = $callbackQuery->getMessage()->getChat()->getId();
        $messageId     = $callbackQuery->getMessage()->getMessageId();
        $userId        = $callbackQuery->getFrom()->getId();

        if ($callbackData === 'call_aio_team') {
            $keyboard = new Keyboard(
                [
                    ['text' => 'Yes', 'request_location' => true],
                    ['text' => 'No'],
                ]
            );

            $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true);

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => 'Would you like to share your location?',
                'reply_markup' => $keyboard,
            ]);
        }

        if ($callbackData === 'call_aio_team_location') {
            return $this->handleCallAioTeam($callbackQuery);
        }

        return Request::emptyResponse();
    }

    /**
     * Make a POST request to notify.php to send a notification.
     * @throws TelegramException
     */
    private function handleCallAioTeam(CallbackQuery $callbackQuery): ServerResponse
    {

        $baseUrl = $_ENV['BASE_URL'];
        $url = sprintf('%s/notify.php', $baseUrl);
        $chatId    = $callbackQuery->getMessage()->getChat()->getId();

        $postData = [
            'fullname' => $callbackQuery->getFrom()->getFirstName() . ' ' . $callbackQuery->getFrom()->getLastName(),
            'username'    => $callbackQuery->getFrom()->getUsername(),
            'chatId'    => $chatId,
        ];

        $response = $this->curlPost($url, $postData);

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => "AIO Team has been notified! We'll be right there.",
            'reply_markup' => Keyboard::remove(),
        ]);
    }

    /**
     * Reusable method to do a cURL POST.
     *
     * @param string $url
     * @param array  $fields
     * @return string The response body
     */
    private function curlPost(string $url, array $fields): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // timeouts:
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);  // wait max 5s for connection
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);        // max 10s total

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
