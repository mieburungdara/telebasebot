<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Connect to DB
$db = getDbConnection();

// Find messages ready for auto-publishing
$now = date('Y-m-d H:i:s');
$stmt = $db->prepare("SELECT * FROM messages WHERE status = 'ready_review' AND auto_publish_at <= ?");
$stmt->bind_param('s', $now);
$stmt->execute();
$result = $stmt->get_result();

while ($message = $result->fetch_assoc()) {
    $user = findUserById($message['user_id']);

    // 1. Forward the message to the public channel
    $forwarded_message = forwardMessage(PUBLIC_CHANNEL_ID, EDITOR_CHANNEL_ID, $message['editor_message_id']);

    if ($forwarded_message && isset($forwarded_message['result']['message_id'])) {
        $public_message_id = $forwarded_message['result']['message_id'];

        // 2. Update DB
        updateMessageStatus($message['id'], 'forwarded');
        updatePublicMessageId($message['id'], $public_message_id);
        addPointsToUser($user['id'], 1); // Add 1 point for successful publication

        // 3. Remove inline keyboard from the editor channel message
        editMessageReplyMarkup(EDITOR_CHANNEL_ID, $message['editor_message_id'], null);

        // 4. Notify the user
        if ($user) {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        // Note: You need the public channel's username (e.g., @yourchannel)
                        ['text' => 'Lihat Postingan', 'url' => 'https://t.me/' . 'your_public_channel_username' . '/' . $public_message_id]
                    ]
                ]
            ];
            sendMessage($user['telegram_id'], '🎉 Media Anda telah berhasil dipublikasikan!', $keyboard);
        }

        logAction($user['id'], 'auto_publish', $message['id'], "Published to public channel. Points awarded.");

    } else {
        // Handle error
        updateMessageStatus($message['id'], 'error');
        // Optionally, notify an admin about the failure
        logAction($user['id'], 'auto_publish_error', $message['id'], json_encode($forwarded_message));
    }
}

echo "Auto-publish cronjob executed successfully.\n";

?>
