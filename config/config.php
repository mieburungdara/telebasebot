<?php

// --- DATABASE CONFIGURATION ---
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// --- BOT CONFIGURATION ---
define('BOT_TOKEN', getenv('BOT_TOKEN'));
define('BOT_USERNAME', getenv('BOT_USERNAME'));

// --- CHANNEL CONFIGURATION ---
define('EDITOR_CHANNEL_ID', getenv('EDITOR_CHANNEL_ID'));
define('PUBLIC_CHANNEL_ID', getenv('PUBLIC_CHANNEL_ID'));
define('PUBLIC_CHANNEL_USERNAME', getenv('PUBLIC_CHANNEL_USERNAME'));

// --- OTHER SETTINGS ---
// Auto-delete pending messages after how many minutes
define('PENDING_DELETE_MINUTES', getenv('PENDING_DELETE_MINUTES'));
// Auto-publish after how many minutes without admin action
define('AUTOPUBLISH_MINUTES', getenv('AUTOPUBLISH_MINUTES'));

// --- MESSAGES ---
define('BANNED_MESSAGE', getenv('BANNED_MESSAGE'));

?>
