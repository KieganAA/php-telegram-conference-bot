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

$fullname = $_POST['fullname'] ?? '(unknown)';
$username = $_POST['username'] ?? '(unknown)';
$chatId   = $_POST['chatId'] ?? '(unknown)';

// Get location from Google Sheets
$locationRowIndex = $sheetService->getRowIndexByChatId($chatId, 'Locations!A2');
$locationRowValues = $sheetService->getRowValuesByIndex($locationRowIndex + 1, 'Locations');

// Properly convert longitude and latitude to floats
$latitude = floatval(str_replace(',', '.', trim($locationRowValues[1])));
$longitude = floatval(str_replace(',', '.', trim($locationRowValues[2])));


// Notify staff with location
try {
    // Send location pin as floats
    $successLocation = $notificationService->sendLocation($staffChatId, $latitude, $longitude);

    if (!$successLocation) {
        throw new RuntimeException('Failed to send location.');
    }

    // Send additional message
    $msg = "$fullname wants to have a talk with us\n"
        . "His TG: @$username\n"
        . "Location values: Longitude: $longitude, Latitude: $latitude\n";

    $successMessage = $notificationService->notifyUser($staffChatId, $msg);

} catch (TelegramException $e) {
    throw new RuntimeException('TelegramException: ' . $e->getMessage());
}

echo ($successLocation && $successMessage) ? "Staff notified" : "Failed to notify staff";
