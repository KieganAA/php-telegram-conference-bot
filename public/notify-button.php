<?php
// notify.php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\NotificationService;
use Longman\TelegramBot\Exception\TelegramException;

try {
    $notificationService = new NotificationService($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_BOT_USERNAME']);
} catch (TelegramException $e) {
    throw new RuntimeException('TelegramException: ' . $e->getMessage());
}

$staffChatId = $_ENV['STAFF_CHAT_TELEGRAM_ID'];

$msg = "Someone pressed a button 'Call AIO Team'!\n"
    . "Please head to the booth!";

try {
    $success = $notificationService->notifyUser($staffChatId, $msg);
} catch (TelegramException $e) {
    throw new RuntimeException('TelegramException: ' . $e->getMessage());
}

echo $success ? "Staff notified" : "Failed to notify staff";