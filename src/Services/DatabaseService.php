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
    /**
     * Save or update a user in the database.
     */
    public static function saveUser(
        int $id,
        bool $is_bot,
        string $first_name,
        string $last_name = null,
        string $username = null,
        string $language_code = null,
        string $link_label = null,
    ): bool {
        $sql = "INSERT INTO `user` ( 
            id, is_bot, first_name, last_name, username, 
            language_code, created_at, updated_at, link_label
        ) VALUES (
            :id, :is_bot, :first_name, :last_name, :username, 
            :language_code, NOW(), NOW(), :link_label
        )
        ON DUPLICATE KEY UPDATE
            first_name = :first_name_update,
            last_name = :last_name_update,
            username = :username_update,
            language_code = :language_code_update,
            updated_at = NOW()";

        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':is_bot' => (int)$is_bot,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':username' => $username,
            ':language_code' => $language_code,
            ':link_label' => $link_label,

            ':first_name_update' => $first_name,
            ':last_name_update' => $last_name,
            ':username_update' => $username,
            ':language_code_update' => $language_code,
        ]);
    }

    /**
     * Save or update a chat in the database.
     */
    public static function saveChat(
        int $id,
        string $username = null,
        string $first_name = null,
        string $last_name = null,
    ): bool {
        $sql = "INSERT INTO `chat` (
                id, username, first_name, last_name, 
                created_at, updated_at
            ) VALUES (
                :id, :username, :first_name, :last_name, 
                NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                username = :username_update,
                first_name = :first_name_update,
                last_name = :last_name_update,
                updated_at = NOW()";

        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':username' => $username,
            ':first_name' => $first_name,
            ':last_name' => $last_name,

            ':username_update' => $username,
            ':first_name_update' => $first_name,
            ':last_name_update' => $last_name,
        ]);
    }

    /**
     * Link a user to a chat in the user_chat table.
     */
    public static function linkUserChat(int $user_id, int $chat_id): bool
    {
        $sql = "INSERT IGNORE INTO user_chat (user_id, chat_id) VALUES (:user_id, :chat_id)";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':chat_id' => $chat_id,
        ]);
    }

    public static function addInviteCode(string $code): bool
    {
        $sql = "INSERT INTO tracker_invite_codes (code) VALUES (:code)";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':code' => $code]);
    }

    public static function markCodeAsUsed(string $code, int $userId, int $chatId): bool
    {
        $sql = "UPDATE tracker_invite_codes 
                SET used = TRUE, user_id = :user_id, chat_id = :chat_id, used_at = NOW() 
                WHERE code = :code AND used = FALSE";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':code' => $code,
            ':user_id' => $userId,
            ':chat_id' => $chatId
        ]);
    }

    public static function getUnusedInviteCode(): ?string
    {
        $sql = "SELECT code FROM tracker_invite_codes WHERE used = FALSE LIMIT 1";
        $pdo = self::getInstance();
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['code'] ?? null;
    }

    public static function isCodeValid(string $code): bool
    {
        $sql = "SELECT COUNT(*) FROM tracker_invite_codes 
                WHERE code = :code AND used = FALSE";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':code' => $code]);
        return (bool)$stmt->fetchColumn();
    }
    public static function getAllInviteCodesWithUsernames(): array
    {
        $sql = "SELECT tic.*, u.username AS user_username, c.username AS chat_username
            FROM tracker_invite_codes tic
            LEFT JOIN `user` u ON tic.user_id = u.id
            LEFT JOIN chat c ON tic.chat_id = c.id
            ORDER BY tic.created_at DESC";
        $pdo = self::getInstance();
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getCodesByUser(int $userId): array
    {
        $sql = "SELECT * FROM tracker_invite_codes 
                WHERE user_id = :user_id";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getCodesByChat(int $chatId): array
    {
        $sql = "SELECT * FROM tracker_invite_codes 
                WHERE chat_id = :chat_id";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':chat_id' => $chatId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getAvailableCodeCount(): int
    {
        $sql = "SELECT COUNT(*) FROM tracker_invite_codes WHERE used = FALSE";
        $pdo = self::getInstance();
        return (int)$pdo->query($sql)->fetchColumn();
    }

    public static function revokeCode(string $code): bool
    {
        $sql = "UPDATE tracker_invite_codes 
                SET used = FALSE, user_id = NULL, chat_id = NULL 
                WHERE code = :code";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':code' => $code]);
    }
    public static function getAllUsers(): array
    {
        $sql = "SELECT * FROM `user` ORDER BY created_at DESC";
        $pdo = self::getInstance();
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllChats(): array
    {
        $sql = "SELECT * FROM `chat` ORDER BY created_at DESC";
        $pdo = self::getInstance();
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUserChatRelationships(): array
    {
        $sql = "SELECT uc.*, u.username AS user_username, c.title AS chat_title
            FROM user_chat uc
            LEFT JOIN `user` u ON uc.user_id = u.id
            LEFT JOIN chat c ON uc.chat_id = c.id";
        $pdo = self::getInstance();
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function markCodeAsUsedSecond(
        string $code,
        ?int $userId = null,
        ?int $chatId = null
    ): bool {
        $sql = "UPDATE tracker_invite_codes 
            SET used = TRUE, 
                user_id = :user_id, 
                chat_id = :chat_id, 
                used_at = NOW() 
            WHERE code = :code AND used = FALSE";

        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':code' => $code,
            ':user_id' => $userId,
            ':chat_id' => $chatId
        ]);
    }
    public static function deleteInviteCode(string $code): bool
    {
        $sql = "DELETE FROM tracker_invite_codes WHERE code = :code";
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':code' => $code]);
    }
}
