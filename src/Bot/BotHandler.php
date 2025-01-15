<?php

namespace App\Bot;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use RuntimeException;

/**
 * Class BotHandler
 *
 * Central class that initializes and handles incoming updates from Telegram.
 */
class BotHandler
{
    /**
     * @var Telegram
     */
    protected Telegram $telegram;

    /**
     * BotHandler constructor.
     *
     * @param string $botToken The Telegram bot token.
     */
    public function __construct(string $botToken)
    {
        $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? '';

        try {
            // Initialize the Telegram object
            $this->telegram = new Telegram($botToken, $botUsername);

            // Register command paths
            $this->telegram->addCommandsPath(__DIR__ . '/Commands');

            $mysql_credentials = [
                'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'user'     => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'database' => $_ENV['DB_NAME'] ?? 'telegram_bot_db',
                'port'     => (int)($_ENV['DB_PORT'] ?? 3306),
            ];

            try {
                $this->telegram->enableMySql($mysql_credentials);
            } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
                throw new RuntimeException($e->getMessage());
            }

            // If you have custom callback handlers or specialized logic,
            // you could load them here or handle them in your commands.

        } catch (TelegramException $e) {
            error_log("TelegramException in BotHandler::__construct: " . $e->getMessage());
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Handle incoming update (webhook).
     */
    public function handle(): void
    {
        try {
            $this->telegram->handle();
        } catch (TelegramException $e) {
            error_log("TelegramException in BotHandler::handle: " . $e->getMessage());
            throw new RuntimeException($e->getMessage());
        }
    }
}
