<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDbConnection();

// Get all pending errors
$errors = $db->query("SELECT * FROM error_logs WHERE refund_status = 'pending'")->fetch_all(MYSQLI_ASSOC);

foreach ($errors as $error) {
    $purchase = $db->query("SELECT * FROM purchases WHERE id = " . $error['purchase_id'])->fetch_assoc();
    if ($purchase) {
        $user_id = $purchase['user_id'];
        $price = $purchase['price'];

        // Refund the user
        $db->begin_transaction();
        try {
            $stmt1 = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt1->bind_param('di', $price, $user_id);
            $stmt1->execute();

            $stmt2 = $db->prepare("UPDATE error_logs SET refund_status = 'refunded' WHERE id = ?");
            $stmt2->bind_param('i', $error['id']);
            $stmt2->execute();

            $db->commit();

            // Notify user
            $user = findUserById($user_id);
            if ($user) {
                sendMessage($user['telegram_id'], "Maaf, terjadi kesalahan saat mengirimkan konten yang Anda beli. Saldo Anda telah dikembalikan sebesar Rp" . number_format($price, 2, ',', '.'));
            }
        } catch (Exception $e) {
            $db->rollback();
            // Log this error to a file or another table for manual inspection
        }
    }
}

echo "Refund process completed.";

?>
