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
        bool $is_premium = false,
        bool $added_to_attachment_menu = false
    ): bool {
        $sql = "INSERT INTO user (
                id, is_bot, first_name, last_name, username, 
                language_code, is_premium, added_to_attachment_menu, created_at, updated_at
            ) VALUES (
                :id, :is_bot, :first_name, :last_name, :username, 
                :language_code, :is_premium, :added_to_attachment_menu, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                first_name = :first_name_update,
                last_name = :last_name_update,
                username = :username_update,
                language_code = :language_code_update,
                is_premium = :is_premium_update,
                added_to_attachment_menu = :added_to_attachment_menu_update,
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
            ':is_premium' => (int)$is_premium,
            ':added_to_attachment_menu' => (int)$added_to_attachment_menu,

            // Update parameters
            ':first_name_update' => $first_name,
            ':last_name_update' => $last_name,
            ':username_update' => $username,
            ':language_code_update' => $language_code,
            ':is_premium_update' => (int)$is_premium,
            ':added_to_attachment_menu_update' => (int)$added_to_attachment_menu,
        ]);
    }

    /**
     * Save or update a chat in the database.
     */
    public static function saveChat(
        int $id,
        string $type,
        string $title = '',
        string $username = null,
        string $first_name = null,
        string $last_name = null,
        bool $is_forum = false,
        bool $all_members_are_administrators = false,
        int $old_id = null
    ): bool {
        $sql = "INSERT INTO chat (
                id, type, title, username, first_name, last_name, 
                is_forum, all_members_are_administrators, created_at, updated_at, old_id
            ) VALUES (
                :id, :type, :title, :username, :first_name, :last_name, 
                :is_forum, :all_members_are_administrators, NOW(), NOW(), :old_id
            )
            ON DUPLICATE KEY UPDATE
                type = :type_update,
                title = :title_update,
                username = :username_update,
                first_name = :first_name_update,
                last_name = :last_name_update,
                is_forum = :is_forum_update,
                all_members_are_administrators = :all_members_are_administrators_update,
                old_id = :old_id_update,
                updated_at = NOW()";

        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':type' => $type,
            ':title' => $title,
            ':username' => $username,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':is_forum' => (int)$is_forum,
            ':all_members_are_administrators' => (int)$all_members_are_administrators,
            ':old_id' => $old_id,

            // Update parameters
            ':type_update' => $type,
            ':title_update' => $title,
            ':username_update' => $username,
            ':first_name_update' => $first_name,
            ':last_name_update' => $last_name,
            ':is_forum_update' => (int)$is_forum,
            ':all_members_are_administrators_update' => (int)$all_members_are_administrators,
            ':old_id_update' => $old_id,
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
}
