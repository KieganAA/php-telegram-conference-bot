<?php

$updatesDir = __DIR__ . '/updates';
if (!is_dir($updatesDir)) {
    mkdir($updatesDir, 0777, true);
}

$version = date('Ymd_His') . '_update';
$sqlFilePath = "{$updatesDir}/{$version}.sql";

$sqlCommands = <<<SQL
-- SQL Update: {$version}
USE conference_bot_db;

CREATE TABLE IF NOT EXISTS staff_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tag VARCHAR(50) UNIQUE NOT NULL,
    role VARCHAR(255) DEFAULT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO staff_members (name, tag, role) VALUES
('Anyone Available', 'anyone_talk', NULL),
('@sometgtag1', 'staff_member_1', '👨‍💻 tech'),
('@sometgtag2', 'staff_member_2', '💼 business'),
('@sometgtag3', 'staff_member_3', '📽️ demo'),
('@sometgtag4', 'staff_member_4', '🎉 fun'),
('@sometgtag5', 'staff_member_5', '🍺 drink beer'),
('@sometgtag6', 'staff_member_6', '🤡 clown');
SQL;

if (file_put_contents($sqlFilePath, $sqlCommands)) {
    echo "SQL update file created: {$sqlFilePath}\n";
} else {
    echo "Failed to create SQL update file.\n";
}
