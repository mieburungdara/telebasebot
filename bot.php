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

    // Check if user exists
    $user = findUserByTelegramId($user_id);
    if (!$user) {
        // If user not found, check if they are banned before creating a new one
        $raw_user = findRawUserByTelegramId($user_id);
        if ($raw_user && $raw_user['is_banned']) {
            // User is banned, so we do nothing.
            return;
        }
        // If not banned and not found, create a new user
        if (!$raw_user) {
            $user = createUser($user_id, $username);
        } else {
            // This case should technically not be reached if findUserByTelegramId is working correctly
            $user = $raw_user;
        }
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
            $responseText = "👋 Hai, selamat datang di bot kiriman media!\nKamu bisa mengirimkan foto, video, atau teks untuk kami moderasi dan publikasikan ke channel publik.\n\n📌 Setelah kirim, kamu akan dapat tombol untuk mengkonfirmasi.\n⏳ Jika tidak dikonfirmasi dalam 5 menit, kiriman akan dihapus otomatis.\n\nKetik /bantuan untuk info lebih lanjut.";
            break;
        case $command === '/bantuan':
            $responseText = "📖 *Panduan Bot*\n\n1. Kirim media (foto/video/teks)\n2. Klik tombol ✅ Upload atau ❌ Hapus\n3. Media kamu akan ditinjau oleh admin\n4. Jika disetujui → akan diterbitkan ke channel\n5. Kamu akan mendapat poin setiap media diterbitkan\n\n📌 Perintah:\n- /menu → Tampilkan menu interaktif\n- /statistik → Lihat kontribusimu\n- /topkontributor → Lihat 10 kontributor terbaik\n- /faq → Pertanyaan yang sering diajukan";
            break;
        case $command === '/topkontributor':
            $top_users = getTopContributors();
            if (empty($top_users)) {
                $responseText = "Belum ada kontributor.";
            } else {
                $responseText = "🏆 Top 10 Kontributor:\n\n";
                foreach ($top_users as $index => $top_user) {
                    $responseText .= ($index + 1) . ". " . ($top_user['username'] ? '@' . $top_user['username'] : '👤 (tanpa username)') . " – " . $top_user['points'] . " poin\n";
                }
            }
            break;
        case $command === '/statistik':
            $stats = getUserStats($user['id']);
            $responseText = "📊 Statistik Kontribusi Kamu\n\n";
            $responseText .= "✨ Total Poin: " . $user['points'] . "  \n";
            $responseText .= "📝 Total Kiriman: " . $stats['total_posts'] . "  \n";
            $responseText .= "✅ Diterbitkan: " . $stats['published_posts'] . "  \n";
            $responseText .= "❌ Dibatalkan: " . $stats['cancelled_posts'] . "  \n"; // Assuming you add this stat
            $responseText .= "⏳ Menunggu Editor: " . $stats['review_posts'] . "\n";
            break;
        case $command === '/poin':
            $responseText = "✨ Poin kamu saat ini: " . $user['points'];
            break;
        case $command === '/histori':
            // Assuming you implement getPostHistory function
            $history = getPostHistory($user['id'], 5);
            if (empty($history)) {
                $responseText = "🗂️ Riwayat Kiriman Kamu:\n\nBelum ada kiriman.";
            } else {
                $responseText = "🗂️ Riwayat Kiriman Kamu:\n\n";
                foreach ($history as $index => $item) {
                    $status_text = '';
                    switch ($item['status']) {
                        case 'forwarded':
                            $status_text = 'Diterbitkan';
                            break;
                        case 'cancelled':
                        case 'deleted':
                            $status_text = 'Dibatalkan';
                            break;
                        case 'ready_review':
                            $status_text = 'Menunggu Editor';
                            break;
                        default:
                            $status_text = ucfirst($item['status']);
                    }
                    $responseText .= ($index + 1) . ". " . ucfirst($item['type']) . " – " . $status_text . "\n";
                }
            }
            break;
        case $command === '/menu':
            $responseText = "📋 Menu Utama\n\nPilih fitur yang ingin kamu akses:";
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '📊 Statistik', 'callback_data' => 'stat:me']],
                    [['text' => '📁 Kiriman Saya', 'callback_data' => 'history']],
                    [['text' => '🏆 Top Kontributor', 'callback_data' => 'top']],
                    [['text' => '📖 Bantuan', 'callback_data' => 'help']]
                ]
            ];
            sendMessage($chat_id, $responseText, $keyboard);
            return; // Important: return to avoid sending another message
        case $command === '/faq' || $command === '/aturan':
            $responseText = "📌 FAQ\n\nQ: Berapa maksimal ukuran video?\nA: Maks 50MB.\n\nQ: Berapa lama konten saya diproses?\nA: Maksimal 10 menit atau akan dipublish otomatis.\n\nQ: Bolehkah saya kirim konten promosi?\nA: Ya, selama sesuai pedoman komunitas.";
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
        $text = "📩 Media kamu telah kami terima!\n\nKlik tombol di bawah ini:\n✅ Upload → Untuk melanjutkan ke admin\n❌ Hapus → Untuk membatalkan\n⏳ Jika tidak dikonfirmasi dalam 5 menit, akan dihapus otomatis.";
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
