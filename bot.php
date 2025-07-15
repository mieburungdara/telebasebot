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

    // Handle state
    if ($user['state']) {
        handleState($user, $message);
        return;
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
        case $command === '/saldo':
            $responseText = "💰 Saldo kamu: Rp" . number_format($user['balance'], 2, ',', '.') . "\n";
            $responseText .= "🕓 Dalam proses penarikan: Rp" . number_format($user['pending_withdrawal'], 2, ',', '.') . "\n";
            $responseText .= "🎯 Minimal tarik: Rp10.000\n";
            $responseText .= "Gunakan perintah: /tarik <jumlah>";
            break;
        case strpos($command, '/tarik') === 0:
            $parts = explode(' ', $command);
            if (count($parts) < 2 || !is_numeric($parts[1])) {
                $responseText = "Format perintah salah. Gunakan: /tarik <jumlah>";
            } else {
                $amount = (float)$parts[1];
                $result = requestWithdrawal($user['id'], $amount);
                if ($result === 'success') {
                    $responseText = "📤 Permintaan penarikan Rp" . number_format($amount, 2, ',', '.') . " telah diterima.\nSilakan kirim info penarikan:\n- Nama Bank / eWallet\n- Nomor Rekening\n- Nama Pemilik\n\nKirim ke admin melalui tombol di bawah:";
                    $keyboard = [
                        'inline_keyboard' => [
                            [['text' => '🔘 Hubungi Admin', 'url' => 'https://t.me/' . ADMIN_USERNAME]]
                        ]
                    ];
                    sendMessage($chat_id, $responseText, $keyboard);
                    return;
                } elseif ($result === 'insufficient_balance') {
                    $responseText = "Saldo tidak mencukupi untuk melakukan penarikan.";
                } elseif ($result === 'minimum_amount') {
                    $responseText = "Jumlah penarikan minimum adalah Rp10.000.";
                } else {
                    $responseText = "Terjadi kesalahan saat memproses permintaan Anda.";
                }
            }
            break;
        case $command === '/faq' || $command === '/aturan':
            $responseText = "📌 FAQ\n\nQ: Berapa maksimal ukuran video?\nA: Maks 50MB.\n\nQ: Berapa lama konten saya diproses?\nA: Maksimal 10 menit atau akan dipublish otomatis.\n\nQ: Bolehkah saya kirim konten promosi?\nA: Ya, selama sesuai pedoman komunitas.";
            break;
        case '/buatkonten':
            updateUserState($user['id'], 'create_content_media');
            $responseText = "Silakan kirim media yang ingin Anda jual.";
            break;
        case '/katalog':
            $contents = getPaidContentForSale();
            if (empty($contents)) {
                $responseText = "Saat ini belum ada konten yang dijual.";
            } else {
                sendMessage($chat_id, "Katalog Konten Berbayar:");
                foreach ($contents as $content) {
                    $caption = "ID: " . $content['id'] . "\n";
                    $caption .= "Harga: Rp" . number_format($content['price'], 2, ',', '.') . "\n";
                    $caption .= "Deskripsi: " . $content['caption'] . "\n";
                    $caption .= "Untuk membeli, gunakan /belikonten " . $content['id'];

                    if ($content['type'] === 'photo' && $content['blurred_file_id']) {
                        sendPhoto($chat_id, $content['blurred_file_id'], $caption);
                    } else {
                        sendMessage($chat_id, $caption);
                    }
                }
                return;
            }
            break;
        case strpos($command, '/belikonten') === 0:
            $parts = explode(' ', $command);
            if (count($parts) < 2 || !is_numeric($parts[1])) {
                $responseText = "Format perintah salah. Gunakan: /belikonten <id_konten>";
            } else {
                $content_id = (int)$parts[1];
                $content = getPaidContentById($content_id);

                if (!$content) {
                    $responseText = "Konten tidak ditemukan.";
                } else {
                    incrementContentViews($content_id); // Increment views
                    if (hasPurchased($user['id'], $content_id)) {
                        $responseText = "Anda sudah memiliki konten ini.";
                        sendPaidContent($user['telegram_id'], $content);
                    } else {
                        $buyer = findUserById($user['id']);
                        if ($buyer['balance'] < $content['price']) {
                            $responseText = "Saldo tidak mencukupi untuk membeli konten ini.";
                        } else {
                        $purchase_id = purchaseContent($user['id'], $content);
                        if (is_numeric($purchase_id)) {
                            $responseText = "Pembelian berhasil! Konten sedang dikirim...";
                            sendPaidContent($user['telegram_id'], $content, $purchase_id);

                            // Send rating request
                            $rating_keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => '⭐', 'callback_data' => 'rate:1:' . $content['id']],
                                        ['text' => '⭐⭐', 'callback_data' => 'rate:2:' . $content['id']],
                                        ['text' => '⭐⭐⭐', 'callback_data' => 'rate:3:' . $content['id']],
                                        ['text' => '⭐⭐⭐⭐', 'callback_data' => 'rate:4:' . $content['id']],
                                        ['text' => '⭐⭐⭐⭐⭐', 'callback_data' => 'rate:5:' . $content['id']],
                                    ]
                                ]
                            ];
                            sendMessage($user['telegram_id'], "Bagaimana penilaian Anda terhadap konten ini?", $rating_keyboard);
                        } else {
                            $responseText = "Terjadi kesalahan saat memproses pembelian Anda.";
                        }
                        }
                    }
                }
            }
            break;
        case '/kontenku':
            $creations = getUserCreations($user['id']);
            if (empty($creations)) {
                $responseText = "Anda belum membuat konten berbayar.";
            } else {
                $responseText = "Konten yang Anda buat:\n\n";
                foreach ($creations as $content) {
                    $responseText .= "ID: " . $content['id'] . "\n";
                    $responseText .= "Tipe: " . $content['type'] . "\n";
                    $responseText .= "Harga: Rp" . number_format($content['price'], 2, ',', '.') . "\n";
                    $responseText .= "Status: " . $content['status'] . "\n\n";
                }
            }
            break;
        case '/penghasilan':
            $responseText = handlePenghasilan($user['id']);
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔘 Lihat Riwayat Transaksi', 'callback_data' => 'penghasilan:history']],
                    [['text' => '🔘 Tarik Saldo', 'callback_data' => 'penghasilan:withdraw']]
                ]
            ];
            sendMessage($chat_id, $responseText, $keyboard);
            return;
        case '/topkreator':
            $top_creators = getTopCreators();
            if (empty($top_creators)) {
                $responseText = "Belum ada kreator.";
            } else {
                $responseText = "🏆 Top 10 Kreator:\n\n";
                foreach ($top_creators as $index => $creator) {
                    $responseText .= ($index + 1) . ". " . ($creator['username'] ? '@' . $creator['username'] : '👤 (tanpa username)') . " – Rp" . number_format($creator['total_earnings'], 2, ',', '.') . "\n";
                }
            }
            break;
        case '/riwayatbeli':
            $purchases = getPurchaseHistory($user['id']);
            if (empty($purchases)) {
                $responseText = "Anda belum pernah melakukan pembelian.";
            } else {
                $responseText = "🧾 Riwayat Pembelian Anda:\n\n";
                foreach ($purchases as $purchase) {
                    $responseText .= "Judul: " . ($purchase['caption'] ?: 'Tanpa Judul') . "\n";
                    $responseText .= "Harga: Rp" . number_format($purchase['price'], 2, ',', '.') . "\n";
                    $responseText .= "Waktu: " . $purchase['created_at'] . "\n\n";
                }
            }
            break;
        case strpos($command, '/analitik') === 0:
            $parts = explode(' ', $command);
            if (count($parts) < 2 || !is_numeric($parts[1])) {
                $responseText = "Format perintah salah. Gunakan: /analitik <id_konten>";
            } else {
                $content_id = (int)$parts[1];
                $content = getPaidContentById($content_id);

                if (!$content || $content['user_id'] !== $user['id']) {
                    $responseText = "Konten tidak ditemukan atau Anda bukan pemiliknya.";
                } else {
                    $analytics = getContentAnalytics($content_id);
                    $conversion_rate = $analytics['total_views'] > 0 ? ($analytics['total_purchases'] / $analytics['total_views']) * 100 : 0;

                    $responseText = "📊 Analitik untuk Konten ID: " . $content_id . "\n\n";
                    $responseText .= "👀 Dilihat: " . $analytics['total_views'] . " kali\n";
                    $responseText .= "🛒 Dibeli: " . $analytics['total_purchases'] . " kali\n";
                    $responseText .= "📈 Tingkat Konversi: " . number_format($conversion_rate, 2) . "%\n";
                    $responseText .= "💰 Total Pendapatan: Rp" . number_format($analytics['total_revenue'], 2, ',', '.') . "\n";
                }
            }
            break;
        case '/moderasi':
            if ($user['role'] !== 'admin' && $user['role'] !== 'editor' && $user['role'] !== 'superadmin') {
                $responseText = "Anda tidak memiliki izin untuk mengakses perintah ini.";
            } else {
                $pending_contents = getPendingContents();
                if (empty($pending_contents)) {
                    $responseText = "Tidak ada konten yang menunggu persetujuan.";
                } else {
                    foreach ($pending_contents as $content) {
                        $keyboard = [
                            'inline_keyboard' => [
                                [
                                    ['text' => '✅ Setujui', 'callback_data' => 'admin_approve_content:' . $content['id']],
                                    ['text' => '❌ Tolak', 'callback_data' => 'admin_reject_content:' . $content['id']],
                                ],
                                [
                                    ['text' => '🚫 Blokir Kreator', 'callback_data' => 'admin_block_creator:' . $content['user_id']],
                                ]
                            ]
                        ];
                        $caption = "Konten menunggu persetujuan:\n\n";
                        $caption .= "ID: " . $content['id'] . "\n";
                        $caption .= "Tipe: " . $content['type'] . "\n";
                        $caption .= "Harga: Rp" . number_format($content['price'], 2, ',', '.') . "\n";
                        $caption .= "Deskripsi: " . $content['caption'] . "\n";
                        sendMediaToEditor($content, $caption, $keyboard);
                    }
                    return;
                }
            }
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

$temp_content_data = [];

function handleState($user, $message)
{
    global $temp_content_data;
    $chat_id = $message['chat']['id'];
    $user_id = $user['id'];
    $state_parts = explode(':', $user['state']);
    $state = $state_parts[0];

    switch ($state) {
        case 'create_content_media':
            $media_type = getMediaType($message);
            if ($media_type) {
                $file_id = getFileIdFromMessage($message, $media_type);
                $caption = $message['caption'] ?? '';
                $blurred_file_id = null;

                if ($media_type === 'photo') {
                    sendMessage($chat_id, "Memproses gambar blur...");
                    $blurred_file_id = blurAndReuploadImage($file_id);
                }

                // Store temporary data
                $temp_content_data[$user_id] = [
                    'type' => $media_type,
                    'file_id' => $file_id,
                    'blurred_file_id' => $blurred_file_id,
                    'caption' => $caption
                ];

                updateUserState($user_id, 'create_content_price');
                sendMessage($chat_id, "Media diterima. Sekarang, silakan masukkan harga untuk konten ini (misalnya: 5000).");
            } else {
                sendMessage($chat_id, "Tolong kirimkan media (foto, video, atau dokumen).");
            }
            break;

        case 'create_content_price':
            $price = $message['text'];
            if (is_numeric($price) && $price > 0) {
                $content_data = $temp_content_data[$user_id] ?? null;
                if ($content_data) {
                    $content_data['price'] = $price;

                    // Save to database
                    $content_id = createPaidContent($user_id, $content_data);

                    if ($content_id) {
                        sendMessage($chat_id, "Konten berbayar Anda telah berhasil dibuat dengan ID: $content_id");
                        unset($temp_content_data[$user_id]);
                        updateUserState($user_id, null); // Clear state
                    } else {
                        sendMessage($chat_id, "Terjadi kesalahan saat menyimpan konten Anda. Silakan coba lagi.");
                        // Optionally clear state or let them try again
                    }

                } else {
                    sendMessage($chat_id, "Terjadi kesalahan. Silakan mulai lagi dengan /buatkonten.");
                    updateUserState($user_id, null);
                }
            } else {
                sendMessage($chat_id, "Harga tidak valid. Harap masukkan angka positif.");
            }
            break;
    }
}


function sendPaidContent($chat_id, $content, $purchase_id)
{
    $result = null;
    switch ($content['type']) {
        case 'photo':
            $result = sendPhoto($chat_id, $content['file_id'], $content['caption']);
            break;
        case 'video':
            $result = sendVideo($chat_id, $content['file_id'], $content['caption']);
            break;
        case 'document':
            $result = sendDocument($chat_id, $content['file_id'], $content['caption']);
            break;
    }

    if (!$result || !$result['ok']) {
        // Log the error
        $error_message = $result['description'] ?? 'Unknown error';
        logError($content['user_id'], $purchase_id, $error_message);
    }
}

// Set webhook (run this once)
// $webhook_url = 'https://your.domain/path/to/bot.php';
// $response = file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/setWebhook?url=' . $webhook_url);
// echo $response;

?>
