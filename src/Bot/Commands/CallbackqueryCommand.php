<?php

namespace App\Bot\Commands;

use App\Services\DatabaseService;
use App\Services\GoogleSheetService;
use App\Services\NotificationService;
use Exception;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\CallbackQuery;
use RuntimeException;

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
                'name' => 'Anyone Available',
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


        if ($callbackData === 'attending_employees') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);

            $text = DatabaseService::getMessage('attending_employees');

            foreach ($staffMembers as $member) {
                if ($member['role']) {
                    $text .= "*{$member['name']}* - {$member['role']}\n";
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

                try {
                    $sheetService = GoogleSheetService::getInstance();
                } catch (Exception $e) {
                    throw new RuntimeException('SheetServiceException: ' . $e->getMessage());
                }

                $timestamp = date('Y-m-d H:i:s');

                $staffMember['tag'] === 'anyone_talk'
                    ? $sheetService->appendOrUpdateRow([
                        $chatId, '', '', '', '', '', '', $staffMember['name'], '', '', $timestamp
                    ], 'Main')
                    : $sheetService->appendOrUpdateRow([
                        $chatId, '', '', '', '', '', '', 'anyone available', '', '', $timestamp
                    ], 'Main');

                $text = DatabaseService::getMessage('location_question');
                return Request::sendMessage([
                    'chat_id'      => $chatId,
                    'text'         => $text,
                    'reply_markup' => $keyboard,
                    'parse_mode' => 'Markdown',
                ]);
            }
        }


        if ($callbackData === 'call_aio_team_location') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);
            return $this->handleCallAioTeam($callbackQuery);
        }

        if ($callbackData === 'aio_booth_info') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);
            $text = DatabaseService::getMessage('aio_booth_location');
            return Request::sendMessage([
                'chat_id'      => $chatId,
                'text'         => $text,
                'parse_mode' => 'Markdown',
            ]);
        }

        if ($callbackData === 'aio_contacts') {
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'show_alert'        => false,
            ]);
            $text = DatabaseService::getMessage('business_contacts');
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
            $text = DatabaseService::getMessage('additional_info');

            $keyboard = new InlineKeyboard(
                [
                    ['text' => 'AIO Booth Info', 'callback_data' => 'aio_booth_info'],
                ],
                [
                    ['text' => 'Attending Employees', 'callback_data' => 'attending_employees'],
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
                'parse_mode' => 'Markdown',
            ]);
        }

        return Request::emptyResponse();
        }

    /**
     * Notify the AIO Team when the callback is received.
     *
     * @throws TelegramException
     */
    private function handleCallAioTeam(CallbackQuery $callbackQuery): ServerResponse
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $fullname = $callbackQuery->getFrom()->getFirstName() . ' ' . $callbackQuery->getFrom()->getLastName();
        $username = $callbackQuery->getFrom()->getUsername();

        try {
            $result = NotificationService::notifyStaff($fullname, $username, $chatId);
        } catch (Exception $e) {
            $result = "Error: " . $e->getMessage();
        }

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text'    => $result,
            'reply_markup' => Keyboard::remove(),
        ]);
    }
}
