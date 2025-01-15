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

// 1. Load environment variables from .env (if present)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad won't throw exception if file is missing

// 2. Load MySQL



// 3. Instantiate and handle the Telegram Bot
$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$botHandler = new BotHandler($botToken);
$botHandler->handle();
