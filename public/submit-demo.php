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
if (!$email || !$vertical) {
    exit("Missing required fields. Please go back and fill out all data.");
}

try {
    $sheetService = GoogleSheetService::getInstance();
} catch (Exception $e) {
    throw new RuntimeException('SheetServiceException: ' . $e->getMessage());
}

$timestamp = date('Y-m-d H:i:s');

$row = [$chatId, '', '', '', $email, $vertical, $message, '','', '', $timestamp];
$success = $sheetService->appendOrUpdateRow($row, 'Main');

// 4. Respond to user
if ($success) {
    echo sprintf("Thanks! Your registration was recorded successfully. chatId = %s", $chatId);
} else {
    echo "Oops! Something went wrong saving your data. Please try again later.";
}