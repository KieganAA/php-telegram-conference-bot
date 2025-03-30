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
                    if (!empty($_POST['new_code'])) {
                        DatabaseService::addInviteCode($_POST['new_code']);
                    }
                    break;

                case 'mark_used':
                    if (!empty($_POST['code'])) {
                        DatabaseService::markCodeAsUsedSecond(
                            $_POST['code'],
                            $_POST['user_id'] ?? null,
                            $_POST['chat_id'] ?? null
                        );
                    }
                    break;

                case 'delete_code':
                    if (!empty($_POST['code'])) {
                        DatabaseService::deleteInviteCode($_POST['code']);
                    }
                    break;

                case 'mark_unused':
                    if (!empty($_POST['code'])) {
                        DatabaseService::revokeCode($_POST['code']);
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
$inviteCodes = DatabaseService::getAllInviteCodesWithUsernames();
$userChats = DatabaseService::getUserChatRelationships()
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #666;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .action-form { display: inline-block; margin: 2px; }
        .action-button { padding: 4px 8px; cursor: pointer; }
        .code-controls { margin: 20px 0; }
        .code-input { padding: 8px; width: 200px; }
        .action-button.mark-used { background-color: #ccffcc; }
        .action-button.mark-unused { background-color: #ffcccc; }
        .nav-header {
            background-color: #333;
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #4CAF50;
        }

        .nav-spacer {
            height: 70px;
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
        <th>Bot</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Username</th>
        <th>Language</th>
        <th>Premium</th>
        <th>Added to Menu</th>
        <th>Created</th>
        <th>Updated</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= $user['is_bot'] ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($user['first_name']) ?></td>
            <td><?= htmlspecialchars($user['last_name']) ?? '-' ?></td>
            <td><?= htmlspecialchars($user['username']) ?? '-' ?></td>
            <td><?= htmlspecialchars($user['language_code']) ?? '-' ?></td>
            <td><?= $user['is_premium'] ? 'Yes' : 'No' ?></td>
            <td><?= $user['added_to_attachment_menu'] ? 'Yes' : 'No' ?></td>
            <td><?= $formatDate($user['created_at']) ?></td>
            <td><?= $formatDate($user['updated_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Chats Table -->

<!--<h2>Chats (--><?php //= count($chats) ?><!--)</h2>-->
<!--<table>-->
<!--    <thead>-->
<!--    <tr>-->
<!--        <th>ID</th>-->
<!--        <th>Type</th>-->
<!--        <th>Username</th>-->
<!--        <th>First Name</th>-->
<!--        <th>Last Name</th>-->
<!--        <th>Is Forum</th>-->
<!--        <th>All Admins</th>-->
<!--        <th>Created</th>-->
<!--        <th>Updated</th>-->
<!--    </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--    --><?php //foreach ($chats as $chat): ?>
<!--        <tr>-->
<!--            <td>--><?php //= htmlspecialchars($chat['id']) ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($chat['type']) ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($chat['username']) ?? '-' ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($chat['first_name']) ?? '-' ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($chat['last_name']) ?? '-' ?><!--</td>-->
<!--            <td>--><?php //= $chat['is_forum'] ? 'Yes' : 'No' ?><!--</td>-->
<!--            <td>--><?php //= $chat['all_members_are_administrators'] ? 'Yes' : 'No' ?><!--</td>-->
<!--            <td>--><?php //= $formatDate($chat['created_at']) ?><!--</td>-->
<!--            <td>--><?php //= $formatDate($chat['updated_at']) ?><!--</td>-->
<!--        </tr>-->
<!--    --><?php //endforeach; ?>
<!--    </tbody>-->
<!--</table>-->

<div class="code-controls">
    <h3>Add New Invite Code</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add_code">
        <input class="code-input" type="text" name="new_code"
               placeholder="Enter new code" required>
        <button type="submit">Generate Code</button>
    </form>

<!-- Invite Codes Table -->
<h2>Total Invite Codes & Information (Total: <?= count($inviteCodes) ?>, Available: <?= DatabaseService::getAvailableCodeCount() ?>)</h2>
    <table>
        <thead>
        <tr>
            <th>Code</th>
            <th>Is Used?</th>
            <th>Used By:</th>
            <th>Used At</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($inviteCodes)): ?>
            <tr><td colspan="6">No invite codes found</td></tr>
        <?php endif; ?>
        <?php foreach ($inviteCodes as $code): ?>
            <tr>
                <td><?= htmlspecialchars($code['code']) ?></td>
                <td><?= $code['used'] ? 'Yes' : 'No' ?></td>
                <td>
                    <?= $code['user_username']
                        ? htmlspecialchars($code['user_username']) . " ({$code['user_id']})"
                        : '-' ?>
                </td>
                <td><?= $code['used_at'] ?? '-' ?></td>
                <td><?= $formatDate($code['created_at']) ?></td>
                <td>
                    <?php if ($code['used']): ?>
                        <form class="action-form" method="POST">
                            <input type="hidden" name="action" value="mark_unused">
                            <input type="hidden" name="code" value="<?= $code['code'] ?>">
                            <button type="submit" class="action-button" style="background-color: #ffcccc;">
                                Mark Unused
                            </button>
                        </form>
                    <?php else: ?>
                        <form class="action-form" method="POST">
                            <input type="hidden" name="action" value="mark_used">
                            <input type="hidden" name="code" value="<?= $code['code'] ?>">
                            <select name="user_id" title="Select User" required>
                                <option value="">No User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username'] ?: $user['id']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="chat_id" title="Select Chat" required>
                                <option value="">No Chat</option>
                                <?php foreach ($chats as $chat): ?>
                                    <option value="<?= $chat['id'] ?>">
                                        <?= htmlspecialchars($chat['id']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="action-button" style="background-color: #ccffcc;">
                                Mark Used
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Delete button for all codes -->
                    <form class="action-form" method="POST"
                          onsubmit="return confirm('Permanently delete <?= htmlspecialchars($code['code']) ?>?')">
                        <input type="hidden" name="action" value="delete_code">
                        <input type="hidden" name="code" value="<?= $code['code'] ?>">
                        <button type="submit" class="action-button"
                                style="background-color: #ff4444; color: white; margin-top: 5px;">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<!-- User-Chat Relationships Table -->
<!--<h2>User-Chat Relationships (--><?php //= count($userChats) ?><!--)</h2>-->
<!--<table>-->
<!--    <thead>-->
<!--    <tr>-->
<!--        <th>User ID</th>-->
<!--        <th>Chat ID</th>-->
<!--        <th>User Name</th>-->
<!--    </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--    --><?php //foreach ($userChats as $relation): ?>
<!--        <tr>-->
<!--            <td>--><?php //= htmlspecialchars($relation['user_id']) ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($relation['chat_id']) ?><!--</td>-->
<!--            <td>--><?php //= htmlspecialchars($relation['user_username']) ?? 'N/A' ?><!--</td>-->
<!--        </tr>-->
<!--    --><?php //endforeach; ?>
<!--    </tbody>-->
<!--</table>-->

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