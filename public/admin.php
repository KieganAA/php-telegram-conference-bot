<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';

use App\Services\DatabaseService;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$converter = new MarkdownConverter($environment);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'create') {
        $identifier = $_POST['identifier'] ?? null;
        $text = $_POST['text'] ?? null;

        if ($identifier && $text) {
            if (DatabaseService::insertMessage($identifier, $text)) {
                $message = "Message created successfully!";
            } else {
                $error = "Failed to create message. Identifier might already exist.";
            }
        } else {
            $error = "All fields are required.";
        }
    }

    if ($action === 'update') {
        $id = $_POST['id'] ?? null;
        $identifier = $_POST['identifier'] ?? null;
        $text = $_POST['text'] ?? null;

        if ($id && $identifier && $text) {
            if (DatabaseService::updateMessage($identifier, $text)) {
                $message = "Message updated successfully!";
            } else {
                $error = "Failed to update message.";
            }
        } else {
            $error = "All fields are required.";
        }
    }

    if ($action === 'delete') {
        $identifier = $_POST['identifier'] ?? null;

        if ($identifier) {
            if (DatabaseService::deleteMessage($identifier)) {
                $message = "Message deleted successfully!";
            } else {
                $error = "Failed to delete message.";
            }
        } else {
            $error = "Identifier is required for deletion.";
        }
    }
}

$messages = DatabaseService::getAllMessages();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
        }
        h1 {
            color: #343a40;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #343a40;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        textarea {
            width: 100%;
            height: 150px;
            resize: vertical;
            font-family: monospace;
            font-size: 14px;
        }
        .button {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .danger {
            background-color: #dc3545;
        }
        .danger:hover {
            background-color: #c82333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: #e9eff5;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 40%;
            position: relative;
            font-family: 'Helvetica', sans-serif;
        }
        .telegram-header {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .telegram-bubble {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 10px 15px;
            color: #000;
            font-size: 14px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .telegram-footer {
            font-size: 12px;
            color: #aaa;
            margin-top: 8px;
            text-align: right;
        }
        .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
    </style>
    <script>
        function confirmDelete(identifier) {
            if (confirm("Are you sure you want to delete the message with identifier: " + identifier + "?")) {
                const form = document.getElementById("delete-form-" + identifier);
                form.submit();
            }
        }

        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('preview-button')) {
                const htmlContent = event.target.getAttribute('data-content');
                showModal(htmlContent);
            }
        });

        function showModal(htmlContent) {
            const modal = document.getElementById('markdownModal');
            const modalContent = document.getElementById('modalContent');

            let formattedContent = htmlContent.replace(/\n\n/g, '<p></p>');
            formattedContent = formattedContent.replace(/\n/g, '<br>');

            modalContent.querySelector('.telegram-bubble').innerHTML = formattedContent;
            modal.style.display = 'block';
        }

        function closeModal() {
            const modal = document.getElementById('markdownModal');
            modal.style.display = 'none';
        }
    </script>
</head>
<body>
<h1>Admin Panel</h1>
<p><a href="login.php?logout=1" class="button">Logout</a></p>

<?php if (!empty($message)) echo "<p class='success'>$message</p>"; ?>
<?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

<!-- Form to create a new message -->
<form method="POST">
    <h2>Create New Message</h2>
    <label for="identifier">Identifier:</label>
    <input type="text" name="identifier" id="identifier" required>
    <br><br>
    <label for="text">Message Text:</label>
    <textarea name="text" id="text" rows="6" required></textarea>
    <br><br>
    <button type="submit" name="action" value="create" class="button">Create Message</button>
</form>

<!-- Table to display and manage existing messages -->
<h2>Manage Messages</h2>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Identifier</th>
        <th>Message Text</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($messages as $msg): ?>
        <?php
        try {
            $parsedHtml = $converter->convert($msg['text'])->__toString();
        } catch (CommonMarkException $e) {
            throw new RuntimeException($e->getMessage());
        }
        ?>
        <tr>
            <form method="POST">
                <td><?= htmlspecialchars($msg['id']) ?></td>
                <td>
                    <label>
                        <input type="text" name="identifier" value="<?= htmlspecialchars($msg['identifier']) ?>" required>
                    </label>
                </td>
                <td>
                    <label>
                        <textarea name="text" rows="6" required><?= htmlspecialchars($msg['text']) ?></textarea>
                    </label>
                </td>
                <td>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($msg['id']) ?>">
                    <button type="submit" name="action" value="update" class="button">Update</button>
                    <button type="button" class="button danger" onclick="confirmDelete('<?= htmlspecialchars($msg['identifier']) ?>')">Delete</button>
                    <!-- Store the rendered HTML in a data attribute -->
                    <button type="button" class="button preview-button" data-content="<?= htmlspecialchars($parsedHtml, ENT_QUOTES | ENT_HTML5) ?>">Preview</button>
                </td>
            </form>
            <form id="delete-form-<?= htmlspecialchars($msg['identifier']) ?>" method="POST" style="display:none;">
                <input type="hidden" name="identifier" value="<?= htmlspecialchars($msg['identifier']) ?>">
                <input type="hidden" name="action" value="delete">
            </form>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal for Telegram-style preview -->
<div id="markdownModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalContent">
            <div class="telegram-header">Conference Bot · 14:35</div>
            <div class="telegram-bubble"></div>
            <div class="telegram-footer">Sent via Bot</div>
        </div>
    </div>
</div>
</body>
</html>

