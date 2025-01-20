<?php

namespace App\Bot\Commands\SystemCommands;

use App\Services\GoogleSheetService;
use Exception;
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
     * @throws Exception
     */
    public function execute(): ServerResponse
    {
        $callbackQuery = $this->getCallbackQuery();
        $callbackData  = $callbackQuery->getData();
        $chatId        = $callbackQuery->getMessage()->getChat()->getId();
        $messageId     = $callbackQuery->getMessage()->getMessageId();
        $userId        = $callbackQuery->getFrom()->getId();

        $staffMembers = [
            [
                'name' => 'Anyone',
                'tag' => 'anyone_talk',
                'role' => null,
            ],
            [
                'name' => '@sometgtag1',
                'tag' => 'staff_member_1',
                'role' => '👨‍💻 tech',
            ],
            [
                'name' => '@sometgtag2',
                'tag' => 'staff_member_2',
                'role' => '💼 business',
            ],
            [
                'name' => '@sometgtag3',
                'tag' => 'staff_member_3',
                'role' => '📽️ demo',
            ],
            [
                'name' => '@sometgtag4',
                'tag' => 'staff_member_4',
                'role' => '🎉 fun',
            ],
            [
                'name' => '@sometgtag5',
                'tag' => 'staff_member_5',
                'role' => '🍺 drink beer',
            ],
            [
                'name' => '@sometgtag6',
                'tag' => 'staff_member_6',
                'role' => '🤡 clown',
            ],
        ];


        if ($callbackData === 'attending_people') {
            $text = "Here's a short list of AIO employees who are currently in Dubai:\n\n"
                . "If you want to talk to somebody - just press a button below, and this bot will notify them.\n"
                . "If you don't want to talk - press the corresponding button, and the menu will disappear.\n\n";

            foreach ($staffMembers as $member) {
                if ($member['role']) {
                    $text .= "*{$member['name']}* - {$member['role']}\n";
                } else {
                    $text .= "*{$member['name']}*\n";
                }
            }

            $keyboardRows = [
                [['text' => 'I don\'t want to talk', 'callback_data' => 'no_talk']],
            ];

            foreach ($staffMembers as $member) {
                if ($member['tag'] === 'anyone_talk') {
                    $keyboardRows[] = [['text' => $member['name'], 'callback_data' => $member['tag']]];
                }
            }

            $currentRow = [];
            foreach ($staffMembers as $member) {
                if ($member['tag'] !== 'anyone_talk') {
                    $currentRow[] = ['text' => $member['name'], 'callback_data' => $member['tag']];
                }

                if (count($currentRow) === 2) {
                    $keyboardRows[] = $currentRow;
                    $currentRow = [];
                }
            }

            if (!empty($currentRow)) {
                $keyboardRows[] = $currentRow;
            }

            $keyboard = new InlineKeyboard(...$keyboardRows);

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'Markdown',
            ]);
        }

        if ($callbackData === 'no_talk') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);

            Request::editMessageReplyMarkup([
                'chat_id'      => $chatId,
                'message_id'   => $callbackQuery->getMessage()->getMessageId(),
                'reply_markup' => null,
            ]);

            return Request::emptyResponse();
        }

        if ($callbackData === 'anyone_talk' || str_contains($callbackData, 'staff_member_')) {
            $staffMember = null;
            foreach ($staffMembers as $member) {
                if ($member['tag'] === $callbackData) {
                    $staffMember = $member;
                    break;
                }
            }

            if ($staffMember) {
                Request::answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->getId(),
                    'show_alert'        => false,
                ]);

                $responseText = $staffMember['tag'] === 'anyone_talk'
                    ? "You'll talk with anyone available, we'll send a notification."
                    : "You'll talk with {$staffMember['name']}, we'll send them a notification.";

                Request::editMessageText([
                    'chat_id'      => $chatId,
                    'message_id'   => $callbackQuery->getMessage()->getMessageId(),
                    'text'         => $responseText,
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

                $staffMember['tag'] === 'anyone_talk'
                    ? $sheetService->appendOrUpdateRow([
                        $chatId, '', '', '', '', '', '', $staffMember['name'], '', '', $timestamp
                    ], 'Main')
                    : $sheetService->appendOrUpdateRow([
                        $chatId, '', '', '', '', '', '', 'anyone available', '', '', $timestamp
                    ], 'Main');

                return Request::sendMessage([
                    'chat_id'      => $chatId,
                    'text'         => 'Would you like to share your location so that we could find you?',
                    'reply_markup' => $keyboard,
                ]);
            }
        }


        if ($callbackData === 'call_aio_team_location') {
            return $this->handleCallAioTeam($callbackQuery);
        }

        if ($callbackData === 'aio_booth_info') {
            $text = "Sample message for AIO Booth location (idk)\n"
            ;

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
            ]);
        }

        if ($callbackData === 'aio_contacts') {
            $text = "Below are our official Business Contacts:\n\n"
                . "💬 [AIO Presale](https://t.me/YourSalesBot) - our official Sales Telegram\n"
                . "🌐 [AIO Website](https://www.aio.tech) - our official website\n"
                . "📢 [AIO Channel](https://t.me/AIOChannel) - our official news channel\n"
                . "📧 AIO Email - our official Sales email address: sales@aio.tech\n";

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode' => 'Markdown',
            ]);
        }


        if ($callbackData === 'additional_info') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);

            $baseUrl = $_ENV['BASE_URL'];
            $webAppUrl = sprintf('%s/demo-register-form.html?chatId=%s', $baseUrl, $chatId);

            $text = "Below you can: \n"
                . "Get our booth number and location\n"
                . "Get information about attending AIO employees and ping them if you want to talk\n"
                . "Book a Demo Call via Form\n"
                . "Get our business contacts\n"
                ;

            $keyboard = new InlineKeyboard(
                [
                    ['text' => 'AIO Booth Info', 'callback_data' => 'aio_booth_info'],
                ],
                [
                    ['text' => 'Attending People', 'callback_data' => 'attending_people'],
                ],
                [
                    ['text' => 'Book a Demo Call', 'web_app' => ['url' => $webAppUrl]],
                ],
                [
                    ['text' => 'Business Contacts', 'callback_data' => 'aio_contacts'],
                ]
            )
            ;

            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'reply_markup' => $keyboard,
            ]);
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
