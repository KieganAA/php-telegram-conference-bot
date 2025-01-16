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

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$botHandler = new BotHandler($botToken);
$botHandler->handle();