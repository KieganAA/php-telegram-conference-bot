<?php

namespace App\Utils;

/**
 * Class Logger
 *
 * Basic example of how you could handle logging.
 * In production, you might use monolog/monolog or a built-in PSR logger.
 */
class Logger
{
    /**
     * Log a message to a file or somewhere else.
     *
     * @param string $message
     */
    public static function log(string $message): void
    {
        // Example: log to a local file
        $logFile = __DIR__ . '/../../bot.log';
        $dateTime = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$dateTime] $message\n", FILE_APPEND);
    }
}
