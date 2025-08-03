<?php

namespace Bot\Commands;

use Bot\TelegramAPI;

class StartCommand
{
    public function execute($chat_id, $user)
    {
        $responseText = "👋 Hai, selamat datang di bot kiriman media!\nKamu bisa mengirimkan foto, video, atau teks untuk kami moderasi dan publikasikan ke channel publik.\n\n📌 Setelah kirim, kamu akan dapat tombol untuk mengkonfirmasi.\n⏳ Jika tidak dikonfirmasi dalam 5 menit, kiriman akan dihapus otomatis.\n\nKetik /bantuan untuk info lebih lanjut.";
        TelegramAPI::sendMessage($chat_id, $responseText);
    }
}
