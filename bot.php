<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/callback.php';

// Get the update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    exit('Invalid request');
}

// Log the update (optional, for debugging)
// file_put_contents('update.log', print_r($update, true), FILE_APPEND);

if (isset($update['message'])) {
    processMessage($update['message']);
} elseif (isset($update['callback_query'])) {
    processCallbackQuery($update['callback_query']);
}

function processMessage($message)
{
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $username = $message['from']['username'] ?? '';
    $text = $message['text'] ?? '';

    // Check if user exists, if not, create new user
    $user = findUserByTelegramId($user_id);
    if (!$user) {
        $user = createUser($user_id, $username);
    }

    // Handle commands
    if (strpos($text, '/') === 0) {
        handleCommand($chat_id, $text, $user);
        return;
    }

    // Handle media
    $media_type = getMediaType($message);
    if ($media_type) {
        handleMedia($message, $user, $media_type);
        return;
    }

    // Default response for other messages
    sendMessage($chat_id, 'Saya hanya menerima kiriman media (foto, video, atau dokumen) dan perintah (command).');
}

function handleCommand($chat_id, $command, $user)
{
    // Simple command routing
    switch (true) {
        case $command === '/start':
            $responseText = "Selamat datang! Kirimkan saya media (foto, video, atau dokumen) untuk diproses.";
            break;
        case $command === '/topkontributor':
            $top_users = getTopContributors();
            if (empty($top_users)) {
                $responseText = "Belum ada kontributor.";
            } else {
                $responseText = "🏆 *Top 10 Kontributor Teratas:*\n\n";
                foreach ($top_users as $index => $top_user) {
                    $responseText .= ($index + 1) . ". " . ($top_user['username'] ? '@' . $top_user['username'] : 'User') . " - " . $top_user['points'] . " poin\n";
                }
            }
            break;
        case $command === '/statistik':
            $stats = getUserStats($user['id']);
            $responseText = "📊 *Statistik Anda:*\n\n";
            $responseText .= "🏅 Poin: *" . $user['points'] . "*\n";
            $responseText .= "📤 Total Kiriman: *" . $stats['total_posts'] . "*\n";
            $responseText .= "✅ Diterbitkan: *" . $stats['published_posts'] . "*\n";
            $responseText .= "⏳ Menunggu Review: *" . $stats['review_posts'] . "*\n";
            $responseText .= "📝 Menunggu Konfirmasi: *" . $stats['pending_posts'] . "*\n";
            break;
        default:
            $responseText = "Perintah tidak dikenali.";
            break;
    }
    sendMessage($chat_id, $responseText);
}

function handleMedia($message, $user, $media_type)
{
    $message_id = $message['message_id'];
    $caption = $message['caption'] ?? '';
    $file_id = getFileIdFromMessage($message, $media_type);

    // Save message to database with 'pending' status
    $db_message_id = saveMessageToDb($user['id'], $message_id, $media_type, $file_id, $caption);

    if ($db_message_id) {
        // Send confirmation to user
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Upload', 'callback_data' => 'upload:' . $db_message_id],
                    ['text' => '❌ Hapus', 'callback_data' => 'cancel:' . $db_message_id],
                ]
            ]
        ];
        $text = "Media Anda telah diterima dan sedang menunggu konfirmasi. Silakan klik 'Upload' untuk melanjutkan atau 'Hapus' untuk membatalkan.\n\n*Pesan ini akan otomatis dihapus dalam " . PENDING_DELETE_MINUTES . " menit jika tidak ada tindakan.*";
        sendMessage($user['telegram_id'], $text, $keyboard);
    } else {
        sendMessage($user['telegram_id'], 'Terjadi kesalahan saat menyimpan media Anda. Silakan coba lagi.');
    }
}

// Set webhook (run this once)
// $webhook_url = 'https://your.domain/path/to/bot.php';
// $response = file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/setWebhook?url=' . $webhook_url);
// echo $response;

?>
