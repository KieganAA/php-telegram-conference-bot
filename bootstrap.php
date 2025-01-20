<?php

use App\Services\NotificationService;

// 1. Load Composer autoload if not already loaded:
require __DIR__ . '/vendor/autoload.php';

// 2. Load environment variables if not done globally:
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

try {
    // Initialize PDO
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

// 3. Initialize the NotificationService for error alerts
$botToken    = $_ENV['TELEGRAM_BOT_TOKEN']       ?? '';
$botUsername = $_ENV['TELEGRAM_BOT_USERNAME']    ?? '';
$errorChatId = $_ENV['ERROR_NOTIFICATION_TELEGRAM_ID'] ?? '';

$notificationService = new NotificationService($botToken, $botUsername);

/**
 * 4. Exception Handler
 */
set_exception_handler(function (\Throwable $throwable) use ($notificationService, $errorChatId) {
    // Build a detailed message
    $message = "[EXCEPTION] " . $throwable->getMessage() . "\n"
        . "File: " . $throwable->getFile() . "\n"
        . "Line: " . $throwable->getLine() . "\n";

    // Send to Telegram
    $notificationService->notifyUser($errorChatId, $message);

    // Log to error_log or a custom logger as well
    error_log($message);

    // Optionally re-throw or exit
    // throw $throwable; // or exit;
});

/**
 * 5. Error Handler
 *
 * If you also want to treat PHP warnings, notices as “exceptions”.
 */
set_error_handler(function ($severity, $message, $file, $line) use ($notificationService, $errorChatId) {
    // Convert error to exception-like message
    $errorType = match ($severity) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR   => 'Fatal Error',
        E_WARNING, E_USER_WARNING                             => 'Warning',
        E_NOTICE, E_USER_NOTICE                               => 'Notice',
        E_DEPRECATED, E_USER_DEPRECATED                       => 'Deprecated',
        E_STRICT                                              => 'Strict',
        default                                               => 'Unknown Error',
    };

    $errorMessage = "[PHP $errorType] $message\nFile: $file\nLine: $line\n";

    // Send to Telegram
    $notificationService->notifyUser($errorChatId, $errorMessage);

    // Log to error_log
    error_log($errorMessage);

    // By default, let PHP know if we want to keep normal error handling going
    // Returning false continues normal error handling; returning true halts it
    // Usually we return false, so errors also appear in logs:
    return false;
});

/**
 * 6. Shutdown Function (for fatal errors)
 *
 * Register a shutdown function to catch fatal errors that kill the script.
 * Note: We cannot always recover from fatal errors, but we can at least send a report.
 */
register_shutdown_function(function () use ($notificationService, $errorChatId) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $message = "[FATAL ERROR] {$error['message']}\nFile: {$error['file']}\nLine: {$error['line']}\n";
        $notificationService->notifyUser($errorChatId, $message);
        error_log($message);
    }
});
