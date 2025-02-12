<?php

use App\Services\DatabaseService;

require_once 'src/Services/DatabaseService.php';

$updatesDir = '/var/www/bot/database/updates';
$db = DatabaseService::getInstance();

// Ensure `schema_versions` table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS schema_versions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(50) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$files = scandir($updatesDir);
$sqlFiles = array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'sql');

foreach ($sqlFiles as $file) {
    $version = pathinfo($file, PATHINFO_FILENAME);

    // Check if the update has already been applied
    $stmt = $db->prepare("SELECT 1 FROM schema_versions WHERE version = :version");
    $stmt->execute([':version' => $version]);
    if ($stmt->fetchColumn()) {
        echo "Update {$version} already applied.\n";
        continue;
    }

    // Apply the update
    $sqlFilePath = "{$updatesDir}/{$file}";
    $command = sprintf(
        'mysql -u %s -p%s %s < %s',
        escapeshellarg($_ENV['DB_USER']),
        escapeshellarg($_ENV['DB_PASS']),
        escapeshellarg($_ENV['DB_NAME']),
        escapeshellarg($sqlFilePath)
    );

    $output = [];
    $status = null;
    exec($command, $output, $status);

    if ($status === 0) {
        echo "Update {$version} applied successfully.\n";

        // Record the applied update
        $stmt = $db->prepare("INSERT INTO schema_versions (version) VALUES (:version)");
        $stmt->execute([':version' => $version]);
    } else {
        error_log("Failed to apply update {$version}: " . implode("\n", $output));
        echo "Error applying update {$version}.\n";
    }
}
