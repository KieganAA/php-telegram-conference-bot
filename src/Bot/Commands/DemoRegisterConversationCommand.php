<?php

namespace App\Bot\Commands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use App\Services\GoogleSheetService;

/**
 * Class DemoRegisterConversationCommand
 *
 * A multi-step flow to gather user data for an affiliate marketing demo registration,
 * storing conversation state in MySQL (longman/telegram-bot ~0.83 style).
 */
class DemoRegisterConversationCommand extends UserCommand
{
    protected $name = 'demoregister';
    protected $description = 'Multi-step registration for an affiliate marketing demo.';
    protected $usage = '/demoregister';
    protected $version = '1.0.0';

    /**
     * Execute the command
     * @throws TelegramException
     * @throws \Exception
     */
    public function execute(): ServerResponse
    {
        // Basic info about the incoming message
        $message = $this->getMessage();
        $chatId  = $message->getChat()->getId();
        $userId  = $message->getFrom()->getId();
        $text    = trim($message->getText(true)); // text after the command

        // Initialize or resume the conversation
        // "command" param is optional in older versions, but let's keep it.
        $conversation = new Conversation($userId, $chatId, $this->getName());

        // Get current conversation data (notes)
        $notes = $conversation->notes;
        if (!is_array($notes)) {
            $notes = [];
        }

        // We store the current step in $notes['step'] (default 0)
        $step = isset($notes['step']) ? (int)$notes['step'] : 0;

        // Handle "cancel" at any time
        if (strtolower($text) === 'cancel') {
            $conversation->stop();
            return Request::sendMessage([
                'chat_id' => $chatId,
                'text'    => "Demo registration cancelled. You can restart with /demoregister at any time.",
            ]);
        }

        switch ($step) {
            case 0:
                // ASK FOR NAME
                if (empty($text)) {
                    // We haven't asked yet, or user didn't reply
                    // Update 'step' to 0 so we keep asking
                    $notes['step'] = 0;
                    $conversation->notes = $notes;
                    $conversation->update();

                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "What's your **full name**?\n(Type 'cancel' anytime to quit.)",
                        'parse_mode' => 'markdown',
                    ]);
                }

                // User provided their name
                $notes['name'] = $text;

                // Move to next step
                $notes['step'] = 1;
                $conversation->notes = $notes;
                $conversation->update();

                // We'll let the code flow down to the next step logic
                $text = ''; // Reset text so the next step sees it as empty
            // no break

            case 1:
                // ASK FOR EMAIL
                if (empty($text)) {
                    // Still on step 1, ask for email
                    $notes['step'] = 1;
                    $conversation->notes = $notes;
                    $conversation->update();

                    $name = $notes['name'] ?? '';
                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "Great, *{$name}*. Please share your **best email address**.\n" .
                            "(Type 'cancel' to quit.)",
                        'parse_mode' => 'markdown',
                    ]);
                }

                // Validate email (simple check)
                if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "That doesn't look like a valid email. Please try again or type 'cancel' to quit."
                    ]);
                }

                // Save email
                $notes['email'] = $text;

                // Move to next step
                $notes['step'] = 2;
                $conversation->notes = $notes;
                $conversation->update();

                $text = ''; // Reset
            // no break

            case 2:
                // ASK FOR AFFILIATE VERTICAL
                if (empty($text)) {
                    $notes['step'] = 2;
                    $conversation->notes = $notes;
                    $conversation->update();

                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "Which **affiliate marketing vertical** are you most interested in?\n" .
                            "(e.g. e-commerce, lead gen, finance, crypto, etc.)\n" .
                            "Type 'cancel' to quit.",
                        'parse_mode' => 'markdown',
                    ]);
                }

                $notes['vertical'] = $text;

                // Move to next step
                $notes['step'] = 3;
                $conversation->notes = $notes;
                $conversation->update();

                $text = ''; // Reset
            // no break

            case 3:
                // CONFIRMATION
                // If no user input yet, let's display summary and ask for yes/no
                if (empty($text)) {
                    $notes['step'] = 3;
                    $conversation->notes = $notes;
                    $conversation->update();

                    // Summarize
                    $summary = $this->createSummary($notes);

                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => $summary,
                        'parse_mode' => 'markdown',
                    ]);
                }

                // If user typed something
                if (in_array(strtolower($text), ['yes', 'y'])) {
                    // Save to Google Sheets
                    $ok = $this->saveToSheet($notes, $chatId);

                    // Stop conversation
                    $conversation->stop();

                    $reply = $ok
                        ? "Thank you! Your registration has been recorded. We'll be in touch."
                        : "Oops! Something went wrong saving your data. Please try again later.";

                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => $reply,
                    ]);
                } else {
                    // If they said no or anything else
                    $conversation->stop();
                    return Request::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => "Registration cancelled or not confirmed. Use /demoregister to start again."
                    ]);
                }

            default:
                // If we somehow get here, just exit
                return Request::sendMessage([
                    'chat_id' => $chatId,
                    'text'    => "Something unexpected happened. You can /demoregister again.",
                ]);
        }
    }

    /**
     * Helper method: Summarize user data for final confirmation.
     */
    private function createSummary(array $notes): string
    {
        $name     = $notes['name'] ?? '';
        $email    = $notes['email'] ?? '';
        $vertical = $notes['vertical'] ?? '';

        $summary  = "Please confirm the following details:\n\n"
            . "Name: *{$name}*\n"
            . "Email: *{$email}*\n"
            . "Vertical: *{$vertical}*\n\n"
            . "Is this correct? (yes/no)\n"
            . "(Or type 'cancel' to quit anytime.)";

        return $summary;
    }

    /**
     * Save final data to Google Sheets, or any storage you prefer.
     * @throws \Exception
     */
    private function saveToSheet(array $notes, int $chatId): bool
    {
        $credentialsPath = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'] ?? '';
        $spreadsheetId   = $_ENV['SPREADSHEET_ID'] ?? '';

        $sheetService = new GoogleSheetService($credentialsPath, $spreadsheetId);

        // Let's store [chat_id, name, email, vertical, date]
        $row = [
            $chatId,
            $notes['name']     ?? '',
            $notes['email']    ?? '',
            $notes['vertical'] ?? '',
            date('Y-m-d H:i:s'),
        ];

        return $sheetService->appendRow($row, 'Registrations!A1');
    }
}
