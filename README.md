# Telegram Moderation Bot (PHP)

Bot Telegram ini berfungsi sebagai perantara untuk memoderasi konten (gambar, video, dokumen) yang dikirim oleh pengguna sebelum dipublikasikan ke channel publik. Bot ini dibuat menggunakan PHP dan MySQL/MariaDB.

## Alur Kerja Bot

1.  **Pengguna Mengirim Media**: Pengguna mengirimkan foto, video, atau dokumen ke bot.
2.  **Konfirmasi Pengguna**: Bot akan membalas dengan pesan yang meminta konfirmasi. Pesan ini berisi tombol "✅ Upload" dan "❌ Hapus".
3.  **Batas Waktu Konfirmasi**: Jika pengguna tidak merespons dalam 5 menit, kiriman akan otomatis dibatalkan.
4.  **Proses Moderasi**: Setelah pengguna menekan "✅ Upload", media akan dikirim ke *Channel Editor*. Di channel ini, admin/editor dapat melihat kiriman tersebut.
5.  **Tindakan Editor**: Di Channel Editor, terdapat tombol untuk:
    *   `✅ Publish`: Langsung mempublikasikan kiriman ke *Channel Publik*.
    *   `❌ Batal`: Membatalkan kiriman.
    *   `⏱ Perpanjang`: Menambah waktu auto-publish.
6.  **Auto-Publish**: Jika tidak ada tindakan dari editor dalam 10 menit, bot akan secara otomatis mempublikasikan kiriman ke *Channel Publik*.
7.  **Notifikasi & Poin**: Setelah kiriman dipublikasikan (baik manual oleh editor atau otomatis), pengguna akan menerima notifikasi dan mendapatkan poin.

## Fitur

-   **Penerimaan Media**: Menerima foto, video, dan dokumen.
-   **Moderasi Konten**: Alur moderasi melalui channel editor.
-   **Auto-Publish**: Publikasi otomatis jika tidak ada tindakan dari editor.
-   **Cronjobs**:
    -   Menghapus kiriman yang tertunda secara otomatis.
    -   Memperbarui countdown waktu auto-publish di channel editor.
    -   Mengeksekusi auto-publish.
-   **Sistem Poin**: Pengguna mendapatkan poin untuk setiap kiriman yang berhasil dipublikasikan.
-   **Peran Pengguna**: Sistem membedakan antara `member`, `editor`, dan `admin`.
-   **Perintah Pengguna**:
    -   `/start`: Memulai interaksi dengan bot.
    -   `/statistik`: Melihat statistik pribadi (jumlah poin, total kiriman, status kiriman).
    -   `/topkontributor`: Melihat 10 pengguna dengan poin tertinggi.

## Struktur File

```
.
├── README.md
├── bot.php                 # File utama (webhook handler)
├── callback.php            # Menangani semua callback dari inline keyboard
├── config
│   └── config.php          # File konfigurasi (token, DB, channel ID)
├── cronjobs
│   ├── auto_publish.php
│   ├── check_pending_delete.php
│   └── update_caption_countdown.php
├── database.sql            # Skema database
└── includes
    └── functions.php       # Kumpulan fungsi helper
```

## Instalasi

1.  **Database**:
    *   Buat database baru di MySQL/MariaDB.
    *   Impor `database.sql` untuk membuat tabel yang diperlukan (`users`, `messages`, `logs`).

2.  **Konfigurasi**:
    *   Salin `config/config.php.example` menjadi `config/config.php`.
    *   Isi semua konstanta yang ada di `config/config.php`:
        *   `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`: Sesuaikan dengan konfigurasi database Anda.
        *   `BOT_TOKEN`: Token bot Anda yang didapat dari @BotFather.
        *   `EDITOR_CHANNEL_ID`: ID Channel Editor (channel privat).
        *   `PUBLIC_CHANNEL_ID`: ID Channel Publik.
        *   `'your_public_channel_username'`: ganti dengan username channel publik Anda di `auto_publish.php` dan `callback.php` untuk URL "Lihat Postingan".

3.  **Webhook**:
    *   Upload semua file ke server web Anda.
    *   Atur webhook Telegram agar menunjuk ke file `bot.php` Anda. Anda bisa melakukannya dengan mengakses URL berikut di browser Anda (cukup sekali):
        ```
        https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://yourdomain.com/path/to/bot.php
        ```
    *   Pastikan Anda mengganti `<BOT_TOKEN>` dan URL sesuai dengan milik Anda.

4.  **Cronjobs**:
    *   Atur cronjob di server Anda untuk mengeksekusi skrip di direktori `cronjobs` setiap satu menit.
        ```
        * * * * * php /path/to/your/project/cronjobs/check_pending_delete.php
        * * * * * php /path/to/your/project/cronjobs/update_caption_countdown.php
        * * * * * php /path/to/your/project/cronjobs/auto_publish.php
        ```

## Cara Kerja Admin/Editor

1.  **Menambahkan Admin**: Untuk menjadikan pengguna sebagai admin/editor, ubah kolom `role` di tabel `users` secara manual dari `member` menjadi `admin` atau `editor`.
2.  **Moderasi**: Semua kiriman yang dikonfirmasi oleh pengguna akan masuk ke Channel Editor. Admin/Editor hanya perlu menekan tombol yang tersedia di bawah setiap postingan di channel tersebut.
