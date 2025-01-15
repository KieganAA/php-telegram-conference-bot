<?php

namespace App\Utils;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

/**
 * Class Helpers
 *
 * A place for small, generic helper functions you might need.
 */
class Helpers
{
    /**
     * Example helper function to sanitize user input or format strings.
     *
     * @param string $input
     * @return string
     */

    public static function sanitizeInput(string $input): string
    {
        // trivial example
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function fakeCallback($chat_id, $message, $telegram, $callbackData): bool
    {
        $fake_update_data = [
            'update_id' => time(),
            'callback_query' => [
                'id' => uniqid(),
                'from' => [
                    'id' => $chat_id,
                    'is_bot' => false,
                    'first_name' => $message->getFrom()->getFirstName(),
                    'last_name' => $message->getFrom()->getLastName(),
                    'username' => $message->getFrom()->getUsername(),
                ],
                'message' => $message->getRawData(),
                'chat_instance' => uniqid(),
                'data' => $callbackData,
            ],
        ];

        $fake_update = new Update($fake_update_data);

        $callback_command = $telegram->getCommandObject('callbackquery');
        if ($callback_command) {
            $callback_command->setUpdate($fake_update);
            $callback_command->preExecute();
        }

        if ($callback_command) {
            return true;
        } else {
            return false;
        }

    }

}
