<?php

namespace Bot\Models;

use Bot\Database;
use Exception;

class User
{
    public static function findRawUserByTelegramId($telegram_id)
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->bind_param('i', $telegram_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private static function isUsernameExists($username): bool
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE generated_username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() !== null;
    }

    private static function generateUniqueUsername(): string
    {
        $length = 8;
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $char_length = strlen($characters);

        do {
            $username = '';
            $username .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'[rand(0, 51)];
            for ($i = 1; $i < $length; $i++) {
                $username .= $characters[rand(0, $char_length - 1)];
            }
        } while (self::isUsernameExists($username));

        return $username;
    }

    public static function findUserByLoginToken($token)
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE login_token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function createLoginToken($user_id)
    {
        $token = bin2hex(random_bytes(32));
        $db = Database::getDbConnection();
        $stmt = $db->prepare("UPDATE users SET login_token = ? WHERE id = ?");
        $stmt->bind_param('si', $token, $user_id);
        if ($stmt->execute()) {
            return $token;
        }
        return null;
    }

    public static function clearLoginToken($user_id): bool
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("UPDATE users SET login_token = NULL WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }

    public static function findUserByTelegramId($telegram_id)
    {
        $user = self::findRawUserByTelegramId($telegram_id);
        if ($user && $user['is_banned']) {
            return null; // Treat banned users as not found
        }
        return $user;
    }

    public static function findUserById($id)
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function createUser($telegram_id, $real_username): array
    {
        $db = Database::getDbConnection();
        $generated_username = self::generateUniqueUsername();
        $stmt = $db->prepare("INSERT INTO users (telegram_id, real_username, generated_username) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $telegram_id, $real_username, $generated_username);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        // Return a full user array, consistent with what findUserById would return
        return [
            'id' => $user_id,
            'telegram_id' => $telegram_id,
            'real_username' => $real_username,
            'generated_username' => $generated_username,
            'role' => 'member',
            'points' => 0,
            'balance' => 0,
            'pending_withdrawal' => 0,
            'is_banned' => 0,
            'state' => null,
            'login_token' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    public static function addPointsToUser($user_id, $points_to_add): bool
    {
        $db = Database::getDbConnection();
        $balance_to_add = $points_to_add * 500; // 1 point = Rp500
        $stmt = $db->prepare("UPDATE users SET points = points + ?, balance = balance + ? WHERE id = ?");
        $stmt->bind_param('idi', $points_to_add, $balance_to_add, $user_id);
        return $stmt->execute();
    }

    public static function blockUser($user_id): bool
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }

    public static function isUserBanned($user_id): bool
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result && $result['is_banned'];
    }

    public static function getTopContributors($limit = 10): array
    {
        $db = Database::getDbConnection();
        $result = $db->query("SELECT generated_username, points FROM users ORDER BY points DESC LIMIT $limit");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getUserStats($user_id): array
    {
        $db = Database::getDbConnection();
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

    public static function getPostHistory($user_id, $limit = 5): array
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT type, status FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function requestWithdrawal($user_id, $amount): string
    {
        $db = Database::getDbConnection();
        $user = self::findUserById($user_id);

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

            Database::logAction($user_id, 'withdrawal_request', null, json_encode(['amount' => $amount]));

            $db->commit();
            return 'success';
        } catch (Exception $e) {
            $db->rollback();
            error_log("Withdrawal Error: " . $e->getMessage());
            return 'error';
        }
    }

    public static function getUserCreations($user_id, $limit = 10): array
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT * FROM paid_contents WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getTopCreators($limit = 10): array
    {
        $db = Database::getDbConnection();
        $result = $db->query("
            SELECT u.generated_username, SUM(p.price) as total_earnings
            FROM purchases p
            JOIN paid_contents pc ON p.content_id = pc.id
            JOIN users u ON pc.user_id = u.id
            GROUP BY u.id
            ORDER BY total_earnings DESC
            LIMIT $limit
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateUserState($user_id, $state): bool
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("UPDATE users SET state = ? WHERE id = ?");
        $stmt->bind_param('si', $state, $user_id);
        return $stmt->execute();
    }

    public static function getTotalPaidContentsByUser($user_id): int
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM paid_contents WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public static function getTotalEarningsByUser($user_id): float
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("
            SELECT SUM(p.price) as total
            FROM purchases p
            JOIN paid_contents c ON p.content_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public static function getUniqueBuyersCountByUser($user_id): int
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT p.user_id) as total
            FROM purchases p
            JOIN paid_contents c ON p.content_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public static function getTopSellingContentsByUser($user_id, $limit = 2): array
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("
            SELECT c.id, c.type, COUNT(p.id) AS jumlah_beli, SUM(p.price) AS total
            FROM purchases p
            JOIN paid_contents c ON p.content_id = c.id
            WHERE c.user_id = ?
            GROUP BY c.id, c.type
            ORDER BY jumlah_beli DESC
            LIMIT ?
        ");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getPurchaseHistory($user_id, $limit = 10): array
    {
        $db = Database::getDbConnection();
        $stmt = $db->prepare("
            SELECT pc.caption, p.price, p.created_at
            FROM purchases p
            JOIN paid_contents pc ON p.content_id = pc.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
