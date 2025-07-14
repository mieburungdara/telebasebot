<?php

// --- DATABASE CONFIGURATION ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'telegram_bot');

// --- BOT CONFIGURATION ---
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');

// --- CHANNEL CONFIGURATION ---
define('EDITOR_CHANNEL_ID', 'YOUR_EDITOR_CHANNEL_ID');
define('PUBLIC_CHANNEL_ID', 'YOUR_PUBLIC_CHANNEL_ID');

// --- OTHER SETTINGS ---
// Auto-delete pending messages after how many minutes
define('PENDING_DELETE_MINUTES', 5);
// Auto-publish after how many minutes without admin action
define('AUTOPUBLISH_MINUTES', 10);

?>
