<?php

// --- DATABASE CONFIGURATION ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'telegram_bot');

// --- BOT CONFIGURATION ---
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('BOT_USERNAME', 'YOUR_BOT_USERNAME');

// --- CHANNEL CONFIGURATION ---
define('EDITOR_CHANNEL_ID', 'YOUR_EDITOR_CHANNEL_ID');
define('PUBLIC_CHANNEL_ID', 'YOUR_PUBLIC_CHANNEL_ID');
define('PUBLIC_CHANNEL_USERNAME', 'your_public_channel_username'); // Add this line

// --- OTHER SETTINGS ---
// Auto-delete pending messages after how many minutes
define('PENDING_DELETE_MINUTES', 5);
// Auto-publish after how many minutes without admin action
define('AUTOPUBLISH_MINUTES', 10);

// --- MESSAGES ---
define('BANNED_MESSAGE', "Anda telah diblokir karena melanggar peraturan.\n\nSaran Peraturan:\n1. Dilarang mengirim konten SARA.\n2. Dilarang mengirim konten pornografi.\n3. Dilarang mengirim konten yang mengandung kekerasan.\n4. Dilarang mengirim spam.");

?>
