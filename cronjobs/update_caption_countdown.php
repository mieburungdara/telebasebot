<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Connect to DB
$db = getDbConnection();

// Find messages that are in 'ready_review' and have an auto_publish_at date
$stmt = $db->prepare("SELECT * FROM messages WHERE status = 'ready_review' AND auto_publish_at IS NOT NULL");
$stmt->execute();
$result = $stmt->get_result();

while ($message = $result->fetch_assoc()) {
    $user = findUserById($message['user_id']);
    $now = new DateTime();
    $publish_time = new DateTime($message['auto_publish_at']);

    if ($now >= $publish_time) {
        // Skip if it's time to publish, the other cron will handle it
        continue;
    }

    $interval = $now->diff($publish_time);
    $countdown = $interval->format('%i menit %s detik');

    $new_caption = $message['caption'] . "\n\n";
    $new_caption .= "--------------------\n";
    $new_caption .= "👤 Poin Kontributor: " . ($user['points'] ?? 0) . "\n";
    $new_caption .= "⏳ Auto-publish dalam: " . $countdown;

    // The inline keyboard for the editor channel
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '✅ Publish', 'callback_data' => 'admin_publish:' . $message['id']],
                ['text' => '❌ Batal', 'callback_data' => 'admin_cancel:' . $message['id']],
            ],
            [
                ['text' => '⏱+1m', 'callback_data' => 'admin_extend:1:' . $message['id']],
                ['text' => '⏱+2m', 'callback_data' => 'admin_extend:2:' . $message['id']],
                ['text' => '⏱+5m', 'callback_data' => 'admin_extend:5:' . $message['id']],
                ['text' => '⏱+10m', 'callback_data' => 'admin_extend:10:' . $message['id']],
            ]
        ]
    ];

    editMessageCaption(EDITOR_CHANNEL_ID, $message['editor_message_id'], $new_caption, $keyboard);
}

echo "Caption countdown cronjob executed successfully.\n";

?>
