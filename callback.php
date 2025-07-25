<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

function processCallbackQuery($callback_query)
{
    $callback_id = $callback_query['id'];
    $user_telegram_id = $callback_query['from']['id'];
    $data = $callback_query['data'];
    $message = $callback_query['message'];
    $chat_id = $message['chat']['id'];
    $message_id = $message['message_id'];

    $parts = explode(':', $data);
    $action = $parts[0];
    $db_message_id = $parts[1] ?? null;

    $user = findUserByTelegramId($user_telegram_id);
    if (!$user) {
        answerCallbackQuery($callback_id, "User tidak ditemukan.", true);
        return;
    }

    // Admin action routing
    if (strpos($action, 'admin_') === 0) {
        if ($user['role'] !== 'admin' && $user['role'] !== 'editor' && $user['role'] !== 'superadmin') {
            answerCallbackQuery($callback_id, "Anda tidak memiliki izin untuk melakukan aksi ini.", true);
            return;
        }
        handleAdminAction($callback_id, $user, $action, $parts, $message_id);
        return;
    }

    // User action routing
    switch ($action) {
        case 'upload':
            handleUpload($callback_id, $user, $db_message_id, $chat_id, $message_id);
            break;
        case 'cancel':
            handleCancel($callback_id, $user, $db_message_id, $chat_id, $message_id);
            break;
        case 'stat':
            // Handle the '/statistik' command logic here
            $stats = getUserStats($user['id']);
            $responseText = "📊 Statistik Kontribusi Kamu\n\n";
            $responseText .= "✨ Total Poin: " . $user['points'] . "  \n";
            $responseText .= "📝 Total Kiriman: " . $stats['total_posts'] . "  \n";
            $responseText .= "✅ Diterbitkan: " . $stats['published_posts'] . "  \n";
            $responseText .= "❌ Dibatalkan: " . $stats['cancelled_posts'] . "  \n";
            $responseText .= "⏳ Menunggu Editor: " . $stats['review_posts'] . "\n";
            editMessageText($chat_id, $message_id, $responseText);
            answerCallbackQuery($callback_id);
            break;
        case 'history':
            // Handle the '/histori' command logic here
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
            editMessageText($chat_id, $message_id, $responseText);
            answerCallbackQuery($callback_id);
            break;
        case 'top':
            // Handle the '/topkontributor' command logic here
            $top_users = getTopContributors();
            if (empty($top_users)) {
                $responseText = "Belum ada kontributor.";
            } else {
                $responseText = "🏆 Top 10 Kontributor:\n\n";
                foreach ($top_users as $index => $top_user) {
                    $responseText .= ($index + 1) . ". " . ($top_user['username'] ? '@' . $top_user['username'] : '👤 (tanpa username)') . " – " . $top_user['points'] . " poin\n";
                }
            }
            editMessageText($chat_id, $message_id, $responseText);
            answerCallbackQuery($callback_id);
            break;
        case 'help':
            $help_type = $parts[1] ?? 'main';
            $responseText = '';
            $keyboard = null;

            switch ($help_type) {
                case 'about':
                    $responseText = "Bot ini adalah platform untuk berbagi dan menjual konten. Anda bisa menjadi kontributor dengan mengirimkan media, atau menjadi kreator dengan menjual konten premium.";
                    break;
                case 'sell':
                    $responseText = "Untuk menjual konten:\n1. Gunakan perintah /buatkonten.\n2. Kirim media yang ingin dijual.\n3. Masukkan harga.\n4. Konten Anda akan ditinjau oleh admin sebelum ditampilkan di /katalog.";
                    break;
                case 'buy':
                    $responseText = "Untuk membeli konten:\n1. Lihat konten yang tersedia di /katalog.\n2. Gunakan perintah /belikonten <ID>.\n3. Pastikan saldo Anda mencukupi.\n4. Konten akan dikirimkan setelah pembayaran berhasil.";
                    break;
                case 'rules':
                    $responseText = "Aturan Komunitas:\n- Dilarang mengirim konten SARA, pornografi, dan kekerasan.\n- Dilarang melakukan spamming.\n- Hormati semua anggota komunitas.\n- Pelanggaran akan menyebabkan pemblokiran.";
                    break;
                case 'report':
                    $responseText = "Untuk melaporkan masalah atau pengguna, silakan hubungi admin dengan menyertakan bukti yang jelas.";
                    $keyboard = ['inline_keyboard' => [[['text' => 'Hubungi Admin', 'url' => 'https://t.me/' . ADMIN_USERNAME]]]];
                    break;
                default:
                    $responseText = "📖 *Panduan Bot*\n\n1. Kirim media (foto/video/teks)\n2. Klik tombol ✅ Upload atau ❌ Hapus\n3. Media kamu akan ditinjau oleh admin\n4. Jika disetujui → akan diterbitkan ke channel\n5. Kamu akan mendapat poin setiap media diterbitkan\n\n📌 Perintah:\n- /menu → Tampilkan menu interaktif\n- /statistik → Lihat kontribusimu\n- /topkontributor → Lihat 10 kontributor terbaik\n- /faq → Pertanyaan yang sering diajukan";
                    break;
            }

            editMessageText($chat_id, $message_id, $responseText, $keyboard);
            answerCallbackQuery($callback_id);
            break;
        case 'rate':
            $rating = (int)$parts[1];
            $content_id = (int)$parts[2];
            if (saveRating($user['id'], $content_id, $rating)) {
                answerCallbackQuery($callback_id, "Terima kasih atas penilaian Anda!");
                editMessageText($chat_id, $message_id, "Anda telah memberikan rating: " . str_repeat('⭐', $rating));
            } else {
                answerCallbackQuery($callback_id, "Terjadi kesalahan saat menyimpan penilaian Anda.", true);
            }
            break;
    }
}

function handleAdminAction($callback_id, $admin_user, $action, $data_parts, $editor_message_id)
{
    $db_message_id = $data_parts[1];
    $message = getMessageById($db_message_id);
    if (!$message) {
        answerCallbackQuery($callback_id, "Pesan tidak ditemukan di database.", true);
        return;
    }

    $original_user = findUserById($message['user_id']);

    switch ($action) {
        case 'admin_publish':
            // 1. Forward to public channel
            $forwarded = forwardMessage(PUBLIC_CHANNEL_ID, EDITOR_CHANNEL_ID, $editor_message_id);
            if ($forwarded && isset($forwarded['result']['message_id'])) {
                $public_message_id = $forwarded['result']['message_id'];
                // 2. Update DB
                updateMessageStatus($db_message_id, 'forwarded');
                updatePublicMessageId($db_message_id, $public_message_id);
                addPointsToUser($original_user['id'], 1);
                // 3. Remove keyboard from editor channel
                editMessageReplyMarkup(EDITOR_CHANNEL_ID, $editor_message_id, null);
                // 4. Notify user
                $updated_user = findUserById($original_user['id']); // Refetch user to get updated balance
                $text = "✅ Kiriman kamu diterbitkan!\n";
                $text .= "🎁 Kamu mendapat Rp500\n";
                $text .= "💰 Saldo kamu sekarang: Rp" . number_format($updated_user['balance'], 2, ',', '.');

                $keyboard = ['inline_keyboard' => [[['text' => 'Lihat Posting', 'url' => 'https://t.me/' . PUBLIC_CHANNEL_USERNAME . '/' . $public_message_id]]]];
                sendMessage($original_user['telegram_id'], $text, $keyboard);
                answerCallbackQuery($callback_id, "Dipublikasikan!");
                logAction($admin_user['id'], 'admin_publish', $db_message_id);
            } else {
                // Notify user about the failure
                sendMessage($original_user['telegram_id'], '⚠️ Gagal menerbitkan media kamu karena kesalahan teknis. Silakan coba lagi nanti.');
                answerCallbackQuery($callback_id, "Gagal mempublikasikan.", true);
            }
            break;

        case 'admin_cancel':
            updateMessageStatus($db_message_id, 'cancelled');
            editMessageText(EDITOR_CHANNEL_ID, $editor_message_id, "Kiriman ini telah dibatalkan oleh @" . $admin_user['username']);
            sendMessage($original_user['telegram_id'], '❌ Mohon maaf, kiriman kamu tidak kami terbitkan kali ini.\n\nTetap semangat dan kirim konten menarik lainnya ya! 😊');
            answerCallbackQuery($callback_id, "Kiriman dibatalkan.");
            logAction($admin_user['id'], 'admin_cancel', $db_message_id);
            break;

        case 'admin_extend':
            $minutes_to_add = $data_parts[2];
            $db = getDbConnection();
            $stmt = $db->prepare("UPDATE messages SET auto_publish_at = auto_publish_at + INTERVAL ? MINUTE WHERE id = ?");
            $stmt->bind_param('ii', $minutes_to_add, $db_message_id);
            $stmt->execute();
            answerCallbackQuery($callback_id, "Waktu auto-publish ditambah $minutes_to_add menit.");
            logAction($admin_user['id'], 'admin_extend', $db_message_id, "Added $minutes_to_add minutes");
            // The caption will be updated by the cronjob automatically
            break;

        case 'admin_block':
            if (blockUser($original_user)) {
                // Also cancel the post
                updateMessageStatus($db_message_id, 'cancelled');
                editMessageText(EDITOR_CHANNEL_ID, $editor_message_id, "Kiriman ini telah dibatalkan dan pengguna @" . ($original_user['username'] ?? $original_user['id']) . " telah diblokir oleh @" . $admin_user['username']);
                answerCallbackQuery($callback_id, "Pengguna telah diblokir dan kiriman dibatalkan.");
                logAction($admin_user['id'], 'admin_block', $db_message_id);
            } else {
                answerCallbackQuery($callback_id, "Gagal memblokir pengguna.", true);
            }
            break;
        case 'admin_approve_content':
            $content_id = $data_parts[1];
            if (updatePaidContentStatus($content_id, 'active')) {
                $content = getPaidContentById($content_id);
                $creator = findUserById($content['user_id']);
                sendMessage($creator['telegram_id'], "Selamat! Konten Anda dengan ID #" . $content_id . " telah disetujui dan sekarang aktif di katalog.");
                answerCallbackQuery($callback_id, "Konten disetujui.");
                editMessageText($chat_id, $message_id, "Konten #" . $content_id . " telah disetujui oleh @" . $admin_user['username']);
            } else {
                answerCallbackQuery($callback_id, "Gagal menyetujui konten.", true);
            }
            break;
        case 'admin_reject_content':
            $content_id = $data_parts[1];
            if (updatePaidContentStatus($content_id, 'rejected')) {
                $content = getPaidContentById($content_id);
                $creator = findUserById($content['user_id']);
                sendMessage($creator['telegram_id'], "Maaf, konten Anda dengan ID #" . $content_id . " ditolak. Silakan periksa kembali pedoman komunitas.");
                answerCallbackQuery($callback_id, "Konten ditolak.");
                editMessageText($chat_id, $message_id, "Konten #" . $content_id . " telah ditolak oleh @" . $admin_user['username']);
            } else {
                answerCallbackQuery($callback_id, "Gagal menolak konten.", true);
            }
            break;
        case 'admin_block_creator':
            $creator_id = $data_parts[1];
            $creator = findUserById($creator_id);
            if (blockUser($creator)) {
                answerCallbackQuery($callback_id, "Kreator telah diblokir.");
            } else {
                answerCallbackQuery($callback_id, "Gagal memblokir kreator.", true);
            }
            break;
    }
}


function handleUpload($callback_id, $user, $db_message_id, $chat_id, $message_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM messages WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param('ii', $db_message_id, $user['id']);
    $stmt->execute();
    $message_data = $stmt->get_result()->fetch_assoc();

    if (!$message_data) {
        answerCallbackQuery($callback_id, "Media tidak ditemukan atau sudah diproses.", true);
        return;
    }

    // 1. Send to editor channel
    $caption = $message_data['caption'] . "\n\n";
    $caption .= "--------------------\n";
    $caption .= "👤 Poin Kontributor: " . ($user['points'] ?? 0) . "\n";
    $caption .= "⏳ Auto-publish dalam: " . AUTOPUBLISH_MINUTES . " menit";

    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '✅ Publish', 'callback_data' => 'admin_publish:' . $db_message_id],
                ['text' => '❌ Batal', 'callback_data' => 'admin_cancel:' . $db_message_id],
            ],
            [
                ['text' => '⏱+1m', 'callback_data' => 'admin_extend:1:' . $db_message_id],
                ['text' => '⏱+2m', 'callback_data' => 'admin_extend:2:' . $db_message_id],
                ['text' => '⏱+5m', 'callback_data' => 'admin_extend:5:' . $db_message_id],
                ['text' => '⏱+10m', 'callback_data' => 'admin_extend:10:' . $db_message_id],
            ],
            [
                ['text' => '🚫 Block User', 'callback_data' => 'admin_block:' . $db_message_id],
            ]
        ]
    ];

    // We need to resend the media to the editor channel
    $sent_message = sendMediaToEditor($message_data, $caption, $keyboard);

    if ($sent_message && isset($sent_message['result']['message_id'])) {
        $editor_message_id = $sent_message['result']['message_id'];

        // 2. Update DB
        $auto_publish_at = date('Y-m-d H:i:s', strtotime('+' . AUTOPUBLISH_MINUTES . ' minutes'));
        $db->query("UPDATE messages SET status = 'ready_review', editor_message_id = $editor_message_id, auto_publish_at = '$auto_publish_at' WHERE id = $db_message_id");

        // 3. Update user's message
        editMessageText($chat_id, $message_id, "✅ Berhasil! Media Anda telah dikirim ke tim editor untuk direview.");

        answerCallbackQuery($callback_id, "Media dikirim untuk review.");
        logAction($user['id'], 'upload_confirm', $db_message_id);

    } else {
        // Handle error
        editMessageText($chat_id, $message_id, "❌ Gagal mengirim media ke editor. Silakan coba lagi.");
        answerCallbackQuery($callback_id, "Gagal mengirim.", true);
        logAction($user['id'], 'upload_error', $db_message_id, json_encode($sent_message));
    }
}

function handleCancel($callback_id, $user, $db_message_id, $chat_id, $message_id)
{
    // 1. Update DB status to 'cancelled'
    updateMessageStatus($db_message_id, 'cancelled');

    // 2. Update user's message
    editMessageText($chat_id, $message_id, "❌ Media Anda telah dibatalkan.");

    answerCallbackQuery($callback_id, "Media dibatalkan.");
    logAction($user['id'], 'upload_cancel', $db_message_id);
}


// This function is a bit complex as it needs to re-send media
function sendMediaToEditor($message_data, $caption, $keyboard)
{
    $params = [
        'chat_id' => EDITOR_CHANNEL_ID,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode($keyboard)
    ];

    $method = '';
    switch ($message_data['type']) {
        case 'photo':
            $method = 'sendPhoto';
            $params['photo'] = $message_data['content'];
            break;
        case 'video':
            $method = 'sendVideo';
            $params['video'] = $message_data['content'];
            break;
        case 'document':
            $method = 'sendDocument';
            $params['document'] = $message_data['content'];
            break;
    }

    if ($method) {
        return apiRequest($method, $params);
    }
    return null;
}

// We need a function to edit text, not just caption
function editMessageText($chat_id, $message_id, $text, $keyboard = null)
{
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
    ];
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    }
    return apiRequest('editMessageText', $params);
}
?>
