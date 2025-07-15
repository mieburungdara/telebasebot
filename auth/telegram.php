<?php
session_start();

// Gantilah dengan token bot Telegram Anda
$botToken = 'YOUR_TELEGRAM_BOT_TOKEN';

// Fungsi untuk mengirim pesan ke Telegram
function sendMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
    ];
    // Gunakan cURL atau metode lain untuk mengirim permintaan HTTP
    // Contoh sederhana menggunakan file_get_contents (tidak disarankan untuk produksi)
    file_get_contents($url . '?' . http_build_query($params));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];

    // Hasilkan token unik
    $token = bin2hex(random_bytes(16));

    // Simpan token dan username di sesi
    $_SESSION['login_token'] = $token;
    $_SESSION['login_username'] = $username;

    // Di dunia nyata, Anda perlu mendapatkan chat_id pengguna dari database
    // berdasarkan username Telegram mereka. Untuk saat ini, kita akan mengasumsikan
    // kita memiliki chat_id.
    $chatId = 'USER_CHAT_ID'; // Ganti dengan chat_id pengguna

    // Buat link login
    $loginLink = "http://{$_SERVER['HTTP_HOST']}/auth/callback.php?token={$token}";

    // Kirim link ke pengguna
    sendMessage($chatId, "Klik link ini untuk login: {$loginLink}", $botToken);

    echo "Link login telah dikirim ke akun Telegram Anda.";
}
?>
