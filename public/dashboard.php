<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';

use App\Services\DatabaseService;

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}


$formatDate = function($date) {
    return $date ? date('Y-m-d H:i', strtotime($date)) : '-';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add_code':
                    if (!empty($_POST['new_code']) && !empty($_POST['link_label'])) {
                        DatabaseService::addInviteCode(
                            $_POST['new_code'],
                            $_POST['link_label']
                        );
                    }
                    break;

                case 'delete_code':
                    if (!empty($_POST['code'])) {
                        DatabaseService::deleteInviteCode($_POST['code']);
                    }
                    break;

            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    header("Location: " . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
    exit;
}

$users = DatabaseService::getAllUsers();
$chats = DatabaseService::getAllChats();
$inviteCodes = DatabaseService::getAllInviteCodesWithLabels();
$userChats = DatabaseService::getUserChatRelationships()
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        :root {
            --primary-color: #4A90E2;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --background-color: #f8f9fa;
            --text-color: #212529;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            color: var(--text-color);
            background-color: var(--background-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1, h2, h3 {
            color: var(--text-color);
            margin: 2rem 0 1rem;
            font-weight: 600;
        }

        h1 {
            font-size: 2.5rem;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f3f5;
        }

        .code-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }

        .form-group {
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: center;
        }

        .code-input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .code-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }

        button[type="submit"]:hover {
            background-color: #357abd;
        }

        .danger-button {
            background-color: var(--danger-color);
            color: white;
        }

        .danger-button:hover {
            background-color: #bb2d3b;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .nav-header {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }

        .nav-spacer {
            height: 70px;
        }

        .text-right {
            text-align: right;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .form-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="nav-header">
    <ul class="nav-list">
        <li>
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="admin.php" class="nav-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">
                Admin Panel
            </a>
        </li>
    </ul>
</nav>
<div class="nav-spacer"></div>
<h1>Database Center</h1>

<!-- Users Table -->
<h2>Total User & Information (<?= count($users) ?>)</h2>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Link Label</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Username</th>
        <th>Language</th>
        <th>Created</th>
        <th>Updated</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= ($user['id']) ?></td>
            <td><?= $user['link_label'] ?? '-'  ?></td>
            <td><?= htmlspecialchars($user['first_name']) ?></td>
            <td><?= ($user['last_name']) ?? '-' ?></td>
            <td><?= ($user['username']) ?? '-' ?></td>
            <td><?= ($user['language_code']) ?? '-' ?></td>
            <td><?= $formatDate($user['created_at']) ?></td>
            <td><?= $formatDate($user['updated_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="code-controls">
    <h3>Add New Invite Code</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add_code">
        <input class="code-input" type="text" name="new_code"
               placeholder="Enter code" required>
        <input class="code-input" type="text" name="link_label"
               placeholder="Link Label" required>
        <button type="submit">Add Code</button>
    </form>
</div>

<!-- Invite Codes Table -->
<h2>Invite Codes (Total: <?= count($inviteCodes) ?>)</h2>
<table>
    <thead>
    <tr>
        <th>Code</th>
        <th>Link Label</th>
        <th>Created At</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($inviteCodes as $code): ?>
        <tr>
            <td><?= htmlspecialchars($code['code']) ?></td>
            <td><?= htmlspecialchars($code['link_label']) ?></td>
            <td><?= $formatDate($code['created_at']) ?></td>
            <td>
                <form method="POST"
                      onsubmit="return confirm('Delete <?= htmlspecialchars($code['code']) ?>?')">
                    <input type="hidden" name="action" value="delete_code">
                    <input type="hidden" name="code" value="<?= $code['code'] ?>">
                    <button type="submit" class="danger-button">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>

<script>
    function confirmDelete(code) {
        return confirm(`Are you sure you want to delete code ${code}? This action cannot be undone!`);
    }
</script>

<?php if (!empty($error)): ?>
    <div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">
        Error: <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

</html>