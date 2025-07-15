<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get all pending messages that were created more than 3 minutes ago and haven't had a reminder sent
$db = getDbConnection();
$three_minutes_ago = date('Y-m-d H:i:s', strtotime('-3 minutes'));
$result = $db->query("
    SELECT m.*, u.telegram_id
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.status = 'pending'
      AND m.reminder_sent = 0
      AND m.created_at < '$three_minutes_ago'
");

$messages = $result->fetch_all(MYSQLI_ASSOC);

foreach ($messages as $message) {
    // Send reminder message
    $text = "⏳ Hai, kiriman kamu belum dikonfirmasi.\nKlik ✅ untuk melanjutkan, atau ❌ untuk membatalkan.\nAkan dihapus otomatis dalam 2 menit.";
    sendMessage($message['telegram_id'], $text);

    // Mark reminder as sent
    $db->query("UPDATE messages SET reminder_sent = 1 WHERE id = " . $message['id']);

    echo "Reminder sent for message ID: " . $message['id'] . "\n";
}

echo "Cron job finished. " . count($messages) . " reminders sent.\n";

?>
