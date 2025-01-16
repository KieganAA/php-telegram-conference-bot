<?php

namespace App\Bot\Commands\SystemCommands;

use App\Services\GoogleSheetService;
use Firebase\JWT\Key;
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


        if ($callbackData === 'want_to_talk') {

            $text = "Who would you like to talk to?\n"
                . "Anyone will do\n"
                . "Someone - tech\n"
                . "Someone - business\n"
                . "Someone - demo\n"
                . "Someone - fun\n"
                . "Someone - drink beer\n"
                . "Someone - clown\n";

            $keyboard = new InlineKeyboard(
                [
                    ['text' => 'Anyone', 'callback_data' => 'staff_member_0'],
                ],
                [
                    ['text' => 'someone1', 'callback_data' => 'staff_member_1'],
                    ['text' => 'someone2', 'callback_data' => 'staff_member_2'],
                ],
                [
                    ['text' => 'someone3', 'callback_data' => 'staff_member_3'],
                    ['text' => 'someone4', 'callback_data' => 'staff_member_4'],
                ],
                [
                    ['text' => 'someone5', 'callback_data' => 'staff_member_5'],
                    ['text' => 'someone6', 'callback_data' => 'staff_member_6'],
                ]
            )
            ;
            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $keyboard,
            ]);

        }

        $staffMembers = [
            'staff_member_0' => 'Anyone',
            'staff_member_1' => '@sometgtag1',
            'staff_member_2' => '@sometgtag2',
            'staff_member_3' => '@sometgtag3',
            'staff_member_4' => '@sometgtag4',
            'staff_member_5' => '@sometgtag5',
            'staff_member_6' => '@sometgtag6',
        ];

        if (array_key_exists($callbackData, $staffMembers)) {
            $staffMember = $staffMembers[$callbackData];

            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);

            Request::editMessageText([
                'chat_id'      => $chatId,
                'message_id'   => $callbackQuery->getMessage()->getMessageId(),
                'text' => "You'll talk with $staffMember, we'll send a notification",
            ]);

            Request::editMessageReplyMarkup([
                'chat_id'      => $chatId,
                'message_id'   => $callbackQuery->getMessage()->getMessageId(),
                'reply_markup' => null,
            ]);

            $keyboard = new Keyboard(
                [
                    ['text' => 'Yes', 'request_location' => true],
                    ['text' => 'No'],
                ]
            );

            $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true);

            $credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
            $spreadsheetId   = $_ENV['SPREADSHEET_ID'];
            $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);
            $timestamp = date('Y-m-d H:i:s');

            // Add the staff member's tag to the spreadsheet
            $sheetService->appendOrUpdateRow([
                $chatId, '', '', '', '','','', $staffMember, '','', $timestamp
            ], 'Main');

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => 'Would you like to share your location so that we could find you?',
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
