<?php

namespace App\Services;

use Exception;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use RuntimeException;

/**
 * Class NotificationService
 *
 * Sends Telegram notifications to a specified user or chat.
 */
class NotificationService
{
    private Telegram $telegram;

    /**
     * NotificationService constructor.
     *
     * @param string $botToken Telegram bot token
     * @param string $botUsername Telegram bot username
     * @throws TelegramException
     */
    public function __construct(string $botToken, string $botUsername)
    {
        $this->telegram = new Telegram($botToken, $botUsername);
    }

    /**
     * Send a message to a specific chat ID.
     *
     * @param int|string $chatId The user or group chat ID
     * @param string $message The message to send
     * @return bool
     * @throws TelegramException
     */
    public function notifyUser(int|string $chatId, string $message): bool
    {
        $data = [
            'chat_id' => $chatId,
            'text'    => $message,
        ];

        $result = Request::sendMessage($data);

        if (! $result->isOk()) {
            throw new \RuntimeException('SendMessage error code: ' . $result->getErrorCode() . ' SendMessage description: ' . $result->getDescription());
        }
        return $result->isOk();
    }
    public function sendLocation(int $chatId, float $latitude, float $longitude): bool
    {
        try {
            $data = [
                'chat_id' => $chatId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

            $response = Request::sendLocation($data);

            return $response->isOk();
        } catch (TelegramException $e) {
            throw new TelegramException('Failed to send location: ' . $e->getMessage());
        }
    }

    public static function notifyStaff(string $fullname, string $username, string $chatId): string
    {
        try {
            $notificationService = new NotificationService($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_BOT_USERNAME']);
        } catch (TelegramException $e) {
            throw new RuntimeException('TelegramException: ' . $e->getMessage());
        }

        try {
            $sheetService = GoogleSheetService::getInstance();
        } catch (Exception $e) {
            throw new RuntimeException('SheetServiceException: ' . $e->getMessage());
        }

        $staffChatId = $_ENV['STAFF_CHAT_TELEGRAM_ID'];

        // Get values from Sheets
        $locationRowIndex = $sheetService->getRowIndexByChatId($chatId, 'Main');
        $locationRowValues = $sheetService->getRowValuesByIndex($locationRowIndex, 'Main');

        // Extract location details
        $rawLatitude = $locationRowValues[8] ?? 'No Location';
        $latitude = floatval(str_replace(',', '.', trim($locationRowValues[8] ?? '')));
        $longitude = floatval(str_replace(',', '.', trim($locationRowValues[9] ?? '')));
        $staffMember = $locationRowValues[7] ?? '(unknown)';

        try {
            $isNoLocation = $rawLatitude === 'No Location';
            $successLocation = false;

            if (!$isNoLocation) {
                $successLocation = $notificationService->sendLocation($staffChatId, $latitude, $longitude);
            }

            $message = "Client $fullname wants to have a talk with $staffMember\n"
                . "Client TG: @$username\n";

            $successMessage = $notificationService->notifyUser($staffChatId, $message);

            return ($successLocation || $successMessage) ? "AIO Team has been notified! We'll be right there" : "Failed to notify staff, please try again";
        } catch (TelegramException $e) {
            throw new RuntimeException('TelegramException: ' . $e->getMessage());
        }
    }
}
