<?php

/**
 * public/index.php
 *
 * Main entry point for Telegram updates (webhook).
 */

declare(strict_types=1);

use App\Bot\BotHandler;
use Dotenv\Dotenv;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad won't throw exception if file is missing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $botHandler = new BotHandler($botToken);

    try {
        $botHandler->handle();
    } catch (Exception $e) {
        error_log("[Webhook Error]: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo 'An error occurred while processing the webhook.';
    }
    exit;
}

http_response_code(404);
echo 'Page not found.';