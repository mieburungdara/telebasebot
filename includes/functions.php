<?php

// --- DATABASE FUNCTIONS ---

function getDbConnection()
{
    static $conn;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // In a real app, log this error instead of echoing
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

function findRawUserByTelegramId($telegram_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
    $stmt->bind_param('i', $telegram_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function findUserByTelegramId($telegram_id)
{
    $user = findRawUserByTelegramId($telegram_id);
    if ($user && $user['is_banned']) {
        return null; // Treat banned users as not found
    }
    return $user;
}

function findUserById($id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createUser($telegram_id, $username)
{
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO users (telegram_id, username) VALUES (?, ?)");
    $stmt->bind_param('is', $telegram_id, $username);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    return ['id' => $user_id, 'telegram_id' => $telegram_id, 'username' => $username, 'role' => 'member', 'points' => 0];
}

function saveMessageToDb($user_id, $message_id, $type, $content, $caption)
{
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO messages (user_id, message_id, type, content, caption) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('iisss', $user_id, $message_id, $type, $content, $caption);
    $stmt->execute();
    return $stmt->insert_id;
}

function updateMessageStatus($message_id, $status)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE messages SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $message_id);
    return $stmt->execute();
}

function updateEditorMessageId($message_id, $editor_message_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE messages SET editor_message_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $editor_message_id, $message_id);
    return $stmt->execute();
}


function updatePublicMessageId($message_id, $public_message_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE messages SET public_message_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $public_message_id, $message_id);
    return $stmt->execute();
}

function addPointsToUser($user_id, $points_to_add)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->bind_param('ii', $points_to_add, $user_id);
    return $stmt->execute();
}

function blockUser($user_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    return $stmt->execute();
}

function isUserBanned($user_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result && $result['is_banned'];
}

function getTopContributors($limit = 10)
{
    $db = getDbConnection();
    $result = $db->query("SELECT username, points FROM users ORDER BY points DESC LIMIT $limit");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getMessageById($id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserStats($user_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM messages WHERE user_id = ?) as total_posts,
            (SELECT COUNT(*) FROM messages WHERE user_id = ? AND status = 'forwarded') as published_posts,
            (SELECT COUNT(*) FROM messages WHERE user_id = ? AND status = 'pending') as pending_posts,
            (SELECT COUNT(*) FROM messages WHERE user_id = ? AND status = 'ready_review') as review_posts
    ");
    $stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function logAction($user_id, $action, $message_id = null, $details = null)
{
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO logs (user_id, action, message_id, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isis', $user_id, $action, $message_id, $details);
    return $stmt->execute();
}


// --- TELEGRAM API FUNCTIONS ---

functionapiRequest($method, $parameters = [])
{
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function sendMessage($chat_id, $text, $keyboard = null)
{
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
    ];
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    }
    return apiRequest('sendMessage', $params);
}

function forwardMessage($chat_id, $from_chat_id, $message_id)
{
    return apiRequest('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id,
    ]);
}

function editMessageCaption($chat_id, $message_id, $caption, $keyboard = null)
{
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
    ];
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    }
    return apiRequest('editMessageCaption', $params);
}

function editMessageReplyMarkup($chat_id, $message_id, $keyboard = null)
{
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ];
    if ($keyboard) {
        $params['reply_markup'] = json_encode($keyboard);
    } else {
        $params['reply_markup'] = json_encode((object)[]); // To remove keyboard
    }
    return apiRequest('editMessageReplyMarkup', $params);
}


function answerCallbackQuery($callback_query_id, $text = '', $show_alert = false)
{
    return apiRequest('answerCallbackQuery', [
        'callback_query_id' => $callback_query_id,
        'text' => $text,
        'show_alert' => $show_alert,
    ]);
}


// --- HELPER FUNCTIONS ---

function getMediaType($message)
{
    if (isset($message['photo'])) return 'photo';
    if (isset($message['video'])) return 'video';
    if (isset($message['document'])) return 'document';
    return null;
}

function getFileIdFromMessage($message, $media_type)
{
    switch ($media_type) {
        case 'photo':
            return end($message['photo'])['file_id']; // Get the highest resolution
        case 'video':
            return $message['video']['file_id'];
        case 'document':
            return $message['document']['file_id'];
        default:
            return null;
    }
}

?>
