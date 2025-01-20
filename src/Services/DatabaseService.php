<?php

namespace App\Services;

use PDO;
use PDOException;

class DatabaseService
{
    private static ?PDO $instance = null;

    /**
     * Get a shared PDO instance.
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASS']
                );
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$instance;
    }

    /**
     * Get a message by identifier.
     *
     * @param string $identifier
     * @return string|null
     */
    public static function getMessage(string $identifier): ?string
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("SELECT text FROM bot_messages WHERE identifier = :identifier");
        $stmt->execute([':identifier' => $identifier]);
        return $stmt->fetchColumn() ?: null;
    }


    /**
     * Insert a new message into the database.
     *
     * @param string $identifier
     * @param string $text
     * @return bool
     */
    public static function insertMessage(string $identifier, string $text): bool
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("INSERT INTO bot_messages (identifier, text) VALUES (:identifier, :text)");
        return $stmt->execute([
            ':identifier' => $identifier,
            ':text'       => $text,
        ]);
    }

    /**
     * Update an existing message in the database.
     *
     * @param string $identifier
     * @param string $text
     * @return bool
     */
    public static function updateMessage(string $identifier, string $text): bool
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("UPDATE bot_messages SET text = :text WHERE identifier = :identifier");
        return $stmt->execute([
            ':text'       => $text,
            ':identifier' => $identifier,
        ]);
    }

    /**
     * Delete a message by identifier.
     *
     * @param string $identifier
     * @return bool
     */
    public static function deleteMessage(string $identifier): bool
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("DELETE FROM bot_messages WHERE identifier = :identifier");
        return $stmt->execute([':identifier' => $identifier]);
    }

    /**
     * Get all messages.
     *
     * @return array
     */
    public static function getAllMessages(): array
    {
        $pdo = self::getInstance();
        $stmt = $pdo->query("SELECT id, identifier, text, last_updated FROM bot_messages");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
