<?php

/**
 * public/notify.php
 *
 * Endpoint to notify staff user(s) via Telegram when a visitor clicks a virtual "Help" button.
 */

declare(strict_types=1);

use App\Services\NotificationService;
use Dotenv\Dotenv;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

// 1. Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// 2. Check if this is a POST request (simple security check)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo "Invalid request method.";
    exit;
}

// 3. Instantiate the NotificationService
$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? '';
$staffChatId = $_ENV['STAFF_USER_TELEGRAM_ID'] ?? '';

try {
    $notificationService = new NotificationService($botToken, $botUsername);
} catch (\Longman\TelegramBot\Exception\TelegramException $e) {
    throw new RuntimeException($e->getMessage());
}

// 4. Prepare the message
$message = "Someone is at the conference booth requesting assistance!";

// 5. Send notification
try {
    $success = $notificationService->notifyUser($staffChatId, $message);
} catch (\Longman\TelegramBot\Exception\TelegramException $e) {
    throw new RuntimeException($e->getMessage());
}

if ($success) {
    echo "Notification sent successfully.";
} else {
    echo "Failed to send notification.";
}