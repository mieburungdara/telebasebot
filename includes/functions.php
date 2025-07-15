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
    $balance_to_add = $points_to_add * 500; // 1 point = Rp500
    $stmt = $db->prepare("UPDATE users SET points = points + ?, balance = balance + ? WHERE id = ?");
    $stmt->bind_param('idi', $points_to_add, $balance_to_add, $user_id);
    return $stmt->execute();
}

function blockUser($user)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    $success = $stmt->execute();

    if ($success) {
        sendMessage($user['telegram_id'], BANNED_MESSAGE);
    }

    return $success;
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
            (SELECT COUNT(*) FROM messages WHERE user_id = ? AND status = 'ready_review') as review_posts,
            (SELECT COUNT(*) FROM messages WHERE user_id = ? AND (status = 'cancelled' OR status = 'deleted')) as cancelled_posts
    ");
    $stmt->bind_param('iiiii', $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getPostHistory($user_id, $limit = 5)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT type, status FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function requestWithdrawal($user_id, $amount)
{
    $db = getDbConnection();
    $user = findUserById($user_id);

    if ($user['balance'] < $amount) {
        return 'insufficient_balance';
    }

    if ($amount < 10000) {
        return 'minimum_amount';
    }

    $db->begin_transaction();
    try {
        $stmt1 = $db->prepare("UPDATE users SET balance = balance - ?, pending_withdrawal = pending_withdrawal + ? WHERE id = ?");
        $stmt1->bind_param('ddi', $amount, $amount, $user_id);
        $stmt1->execute();

        logAction($user_id, 'withdrawal_request', null, json_encode(['amount' => $amount]));

        $db->commit();
        return 'success';
    } catch (Exception $e) {
        $db->rollback();
        return 'error';
    }
}

function getUserCreations($user_id, $limit = 10)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM paid_contents WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTopCreators($limit = 10)
{
    $db = getDbConnection();
    $result = $db->query("
        SELECT u.username, SUM(p.price) as total_earnings
        FROM purchases p
        JOIN paid_contents pc ON p.content_id = pc.id
        JOIN users u ON pc.user_id = u.id
        GROUP BY u.id
        ORDER BY total_earnings DESC
        LIMIT $limit
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
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

function sendPhoto($chat_id, $file_id, $caption = null)
{
    $params = ['chat_id' => $chat_id, 'photo' => $file_id];
    if ($caption) {
        $params['caption'] = $caption;
    }
    return apiRequest('sendPhoto', $params);
}

function sendVideo($chat_id, $file_id, $caption = null)
{
    $params = ['chat_id' => $chat_id, 'video' => $file_id];
    if ($caption) {
        $params['caption'] = $caption;
    }
    return apiRequest('sendVideo', $params);
}

function sendDocument($chat_id, $file_id, $caption = null)
{
    $params = ['chat_id' => $chat_id, 'document' => $file_id];
    if ($caption) {
        $params['caption'] = $caption;
    }
    return apiRequest('sendDocument', $params);
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

function updateUserState($user_id, $state)
{
    $db = getDbConnection();
    $stmt = $db->prepare("UPDATE users SET state = ? WHERE id = ?");
    $stmt->bind_param('si', $state, $user_id);
    return $stmt->execute();
}

function createPaidContent($user_id, $data)
{
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO paid_contents (user_id, type, file_id, caption, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isssd', $user_id, $data['type'], $data['file_id'], $data['caption'], $data['price']);
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

function getPaidContentForSale($limit = 10)
{
    $db = getDbConnection();
    $result = $db->query("SELECT * FROM paid_contents WHERE status = 'active' ORDER BY created_at DESC LIMIT $limit");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPaidContentById($content_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM paid_contents WHERE id = ?");
    $stmt->bind_param('i', $content_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function hasPurchased($user_id, $content_id)
{
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id FROM purchases WHERE user_id = ? AND content_id = ?");
    $stmt->bind_param('ii', $user_id, $content_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() !== null;
}

function purchaseContent($user_id, $content)
{
    $db = getDbConnection();
    $creator = findUserById($content['user_id']);
    $buyer = findUserById($user_id);

    if ($buyer['balance'] < $content['price']) {
        return 'insufficient_balance';
    }

    $db->begin_transaction();
    try {
        // Deduct from buyer
        $stmt1 = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt1->bind_param('di', $content['price'], $user_id);
        $stmt1->execute();

        // Add to creator (90% cut)
        $earnings = $content['price'] * 0.9;
        $stmt2 = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt2->bind_param('di', $earnings, $content['user_id']);
        $stmt2->execute();

        // Record purchase
        $stmt3 = $db->prepare("INSERT INTO purchases (user_id, content_id, price) VALUES (?, ?, ?)");
        $stmt3->bind_param('iid', $user_id, $content['id'], $content['price']);
        $stmt3->execute();

        logAction($user_id, 'purchase', $content['id'], json_encode(['price' => $content['price']]));

        $db->commit();
        return 'success';
    } catch (Exception $e) {
        $db->rollback();
        return 'error';
    }
}

?>
