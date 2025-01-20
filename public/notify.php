<?php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\GoogleSheetService;
use App\Services\NotificationService;
use Longman\TelegramBot\Exception\TelegramException;

try {
    $notificationService = new NotificationService($_ENV['TELEGRAM_BOT_TOKEN'], $_ENV['TELEGRAM_BOT_USERNAME']);
} catch (TelegramException $e) {
    throw new RuntimeException('TelegramException: ' . $e->getMessage());
}

$credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
$spreadsheetId   = $_ENV['SPREADSHEET_ID'];
$sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);

$staffChatId = $_ENV['STAFF_CHAT_TELEGRAM_ID'];

// get info from POST Form
$fullname = $_POST['fullname'] ?? '(unknown)';
$username = $_POST['username'] ?? '(unknown)';
$chatId   = $_POST['chatId'] ?? '(unknown)';

// get values from Sheets
$locationRowIndex = $sheetService->getRowIndexByChatId($chatId, 'Main');
$locationRowValues = $sheetService->getRowValuesByIndex($locationRowIndex, 'Main');

// get info from Sheets
$rawLatitude = $locationRowValues[8];

$latitude = floatval(str_replace(',', '.', trim($locationRowValues[8])));
$longitude = floatval(str_replace(',', '.', trim($locationRowValues[9])));
$staffMember = $locationRowValues[7];


try {
    $isValidLocation = is_numeric($rawLatitude);

    if ($isValidLocation) {
        $successLocation = $notificationService->sendLocation($staffChatId, $latitude, $longitude);
    } else {
        $successLocation = false;
    }

    $message = "Client $fullname wants to have a talk with $staffMember\n"
        . "Client TG: @$username\n";

    $successMessage = $notificationService->notifyUser($staffChatId, $message);

} catch (TelegramException $e) {
    throw new RuntimeException('TelegramException: ' . $e->getMessage());
}

echo ($successLocation || $successMessage) ? "Staff notified" : "Failed to notify staff";

