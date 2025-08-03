<?php

use Bot\Database;

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

// --- PENGHASILAN FUNCTIONS ---
function handlePenghasilan($user_id)
{
    $total_contents = User::getTotalPaidContentsByUser($user_id);
    $total_earnings = User::getTotalEarningsByUser($user_id);
    $unique_buyers = User::getUniqueBuyersCountByUser($user_id);
    $top_contents = User::getTopSellingContentsByUser($user_id, 2);

    $response = "📈 Penghasilan Kamu\n\n";
    $response .= "🎨 Jumlah Konten Berbayar: " . $total_contents . "\n";
    $response .= "💵 Total Pendapatan: Rp" . number_format($total_earnings, 0, ',', '.') . "\n";
    $response .= "👥 Jumlah Pembeli Unik: " . $unique_buyers . "\n\n";

    if (!empty($top_contents)) {
        $response .= "Konten Terlaris:\n";
        foreach ($top_contents as $content) {
            $response .= "- " . ucfirst($content['type']) . " #" . $content['id'] . " – " . $content['jumlah_beli'] . " pembeli – Rp" . number_format($content['total'], 0, ',', '.') . "\n";
        }
    } else {
        $response .= "Belum ada konten yang terjual.";
    }

    return $response;
}
