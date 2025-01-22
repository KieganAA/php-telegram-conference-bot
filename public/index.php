<?php

/**
 * public/index.php
 *
 * Main entry point for Telegram updates (webhook).
 */

declare(strict_types=1);

use App\Bot\BotHandler;

// Load Bootstrap and Composer autoloader
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../vendor/autoload.php';

/**
 * Telegram sends updates via POST. We also allow GET for health checks.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab bot token from environment
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $botHandler = new BotHandler($botToken);

    try {
        // Handle the incoming Telegram update
        $botHandler->handle();

        // If no exception is thrown, respond with 200 OK to let Telegram know it succeeded
        http_response_code(200);
        echo 'OK';
    } catch (Exception $e) {
        // Log the error (check /var/log/apache2/error.log or /var/log/php_error.log, etc.)
        error_log("[Webhook Error]: " . $e->getMessage());

        // Respond with a 500 so you know something went wrong
        // But if this happens too often, Telegram might unset your webhook
        http_response_code(500);
        echo 'An error occurred while processing the webhook.';
    }
    exit;
}

/**
 * Respond with 200 OK to GET requests
 * (so that random GET/HEAD checks don’t produce 404 and break your webhook).
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo 'OK';
    exit;
}

/**
 * For other methods, you can return 405 (Method Not Allowed) or just 200.
 */
http_response_code(405);
echo 'Method Not Allowed';
