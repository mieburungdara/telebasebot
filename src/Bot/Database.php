<?php

namespace Bot;

use mysqli;
use Exception;

class Database
{
    private static ?mysqli $conn = null;

    public static function getDbConnection(): mysqli
    {
        if (self::$conn === null) {
            self::$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$conn->connect_error) {
                error_log("Database Connection Failed: " . self::$conn->connect_error);
                die("Connection failed: " . self::$conn->connect_error);
            }
            self::$conn->set_charset("utf8mb4");
        }
        return self::$conn;
    }

    // --- Message Functions ---
    public static function saveMessageToDb($user_id, $message_id, $type, $content, $caption)
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("INSERT INTO messages (user_id, message_id, type, content, caption) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iisss', $user_id, $message_id, $type, $content, $caption);
        $stmt->execute();
        return $stmt->insert_id;
    }

    public static function updateMessageStatus($message_id, $status): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("UPDATE messages SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $message_id);
        return $stmt->execute();
    }

    public static function getMessageById($id)
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function updateEditorMessageId($message_id, $editor_message_id): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("UPDATE messages SET editor_message_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $editor_message_id, $message_id);
        return $stmt->execute();
    }

    public static function updatePublicMessageId($message_id, $public_message_id): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("UPDATE messages SET public_message_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $public_message_id, $message_id);
        return $stmt->execute();
    }

    // --- Paid Content Functions ---
    public static function createPaidContent($user_id, $data)
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("INSERT INTO paid_contents (user_id, type, file_id, blurred_file_id, caption, price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending_approval')");
        $stmt->bind_param('issssd', $user_id, $data['type'], $data['file_id'], $data['blurred_file_id'], $data['caption'], $data['price']);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    public static function getPaidContentForSale($limit = 10): array
    {
        $db = self::getDbConnection();
        $result = $db->query("SELECT * FROM paid_contents WHERE status = 'active' ORDER BY created_at DESC LIMIT $limit");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getPaidContentById($content_id)
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM paid_contents WHERE id = ?");
        $stmt->bind_param('i', $content_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function hasPurchased($user_id, $content_id): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("SELECT id FROM purchases WHERE user_id = ? AND content_id = ?");
        $stmt->bind_param('ii', $user_id, $content_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() !== null;
    }

    public static function purchaseContent($user_id, $content)
    {
        $db = self::getDbConnection();
        // Note: findUserById is now in the User model.
        // This indicates that purchaseContent might be better off in a PaidContent model.
        // For now, we leave it here to stick to the plan, but this is a candidate for future refactoring.
        $buyer = \Bot\Models\User::findUserById($user_id);

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
            $purchase_id = $stmt3->insert_id;

            self::logAction($user_id, 'purchase', $content['id'], json_encode(['price' => $content['price']]));

            $db->commit();
            return $purchase_id;
        } catch (Exception $e) {
            $db->rollback();
            error_log("Purchase Error: " . $e->getMessage());
            return 'error';
        }
    }

    public static function incrementContentViews($content_id): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("UPDATE paid_contents SET views = views + 1 WHERE id = ?");
        $stmt->bind_param('i', $content_id);
        return $stmt->execute();
    }

    public static function getContentAnalytics($content_id): array
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM purchases WHERE content_id = ?) as total_purchases,
                (SELECT SUM(price) FROM purchases WHERE content_id = ?) as total_revenue,
                (SELECT views FROM paid_contents WHERE id = ?) as total_views
        ");
        $stmt->bind_param('iii', $content_id, $content_id, $content_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function saveRating($user_id, $content_id, $rating): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("INSERT INTO ratings (user_id, content_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->bind_param('iiii', $user_id, $content_id, $rating, $rating);
        return $stmt->execute();
    }

    public static function getPendingContents($limit = 10): array
    {
        $db = self::getDbConnection();
        $result = $db->query("SELECT * FROM paid_contents WHERE status = 'pending_approval' ORDER BY created_at ASC LIMIT $limit");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function updatePaidContentStatus($content_id, $status): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("UPDATE paid_contents SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $content_id);
        return $stmt->execute();
    }

    // --- Generic Functions ---
    public static function logAction($user_id, $action, $message_id = null, $details = null): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("INSERT INTO logs (user_id, action, message_id, details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isis', $user_id, $action, $message_id, $details);
        return $stmt->execute();
    }

    public static function logError($user_id, $purchase_id, $error_message): bool
    {
        $db = self::getDbConnection();
        $stmt = $db->prepare("INSERT INTO error_logs (user_id, purchase_id, error_message) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $user_id, $purchase_id, $error_message);
        return $stmt->execute();
    }
}
