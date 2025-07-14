<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Connect to DB
$db = getDbConnection();

// Find pending messages older than PENDING_DELETE_MINUTES
$delete_time = date('Y-m-d H:i:s', strtotime('-' . PENDING_DELETE_MINUTES . ' minutes'));
$stmt = $db->prepare("SELECT * FROM messages WHERE status = 'pending' AND created_at < ?");
$stmt->bind_param('s', $delete_time);
$stmt->execute();
$result = $stmt->get_result();

while ($message = $result->fetch_assoc()) {
    // 1. Update status to 'deleted' in DB
    updateMessageStatus($message['id'], 'deleted');

    // 2. Get user's telegram_id
    $user = findUserById($message['user_id']);
    if ($user) {
        // 3. Notify user
        $text = "Kiriman Anda telah dibatalkan karena tidak ada konfirmasi setelah " . PENDING_DELETE_MINUTES . " menit.";
        sendMessage($user['telegram_id'], $text);

        // 4. (Optional) Delete the confirmation message from the bot
        // This requires storing the bot's message_id sent to the user
    }

    logAction(null, 'auto_delete_pending', $message['id']);
}

echo "Pending delete cronjob executed successfully.\n";

?>
