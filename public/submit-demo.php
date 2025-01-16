<?php
// submit-demo.php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\GoogleSheetService;

// 1. Retrieve form data
$message = $_POST['message'] ?? '';
$email    = $_POST['email'] ?? '';
$vertical = $_POST['vertical'] ?? '';
$chatId = $_POST['chatId'] ?? '';

// 2. Basic validation
if (!$message || !$email || !$vertical) {
    exit("Missing required fields. Please go back and fill out all data.");
}

// 3. Store in Google Sheets
$credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
$spreadsheetId   = $_ENV['SPREADSHEET_ID'];

try {
    $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);
} catch (Exception $e) {
    throw new RuntimeException($e->getMessage());
}
$timestamp = date('Y-m-d H:i:s');

$row = [$chatId, '', '', '', $email, $vertical, $message, '','', '', $timestamp];
$success = $sheetService->appendOrUpdateRow($row, 'Main');

// 4. Respond to user
if ($success) {
    echo "Thanks! Your registration was recorded successfully.";
} else {
    echo "Oops! Something went wrong saving your data. Please try again later.";
}