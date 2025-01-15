<?php
// submit-demo.php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Services\GoogleSheetService;

// 1. Retrieve form data
$fullname = $_POST['fullname'] ?? '';
$email    = $_POST['email'] ?? '';
$vertical = $_POST['vertical'] ?? '';

// 2. Basic validation
if (!$fullname || !$email || !$vertical) {
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

$row = [
    $fullname,
    $email,
    $vertical,
    date('Y-m-d H:i:s'),
];
$success = $sheetService->appendRow($row, 'Registrations!A2');

// 4. Respond to user
if ($success) {
    echo "Thanks! Your registration was recorded successfully.";
} else {
    echo "Oops! Something went wrong saving your data. Please try again later.";
}