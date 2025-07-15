# Telegram Bot Monetisasi Konten

Bot Telegram ini memungkinkan pengguna untuk mengirimkan media, dan bagi kreator untuk menjual konten berbayar kepada pengguna lain. Bot ini mencakup sistem moderasi, pembayaran, dan analitik yang komprehensif.

## Fitur

### Untuk Pengguna
- **/start**: Memulai interaksi dengan bot.
- **/bantuan**: Menampilkan pesan bantuan dengan daftar perintah.
- **/katalog**: Menelusuri konten berbayar yang tersedia untuk dibeli.
- **/belikonten <id_konten>**: Membeli konten.
- **/riwayatbeli**: Melihat riwayat semua konten yang telah Anda beli.
- **/saldo**: Memeriksa saldo Anda saat ini.
- **/tarik <jumlah>**: Meminta penarikan dana.

### Untuk Kreator
- **/buatkonten**: Memulai proses pembuatan konten berbayar baru.
- **/kontenku**: Melihat daftar konten berbayar yang telah Anda buat.
- **/penghasilan**: Melihat statistik penghasilan Anda.
- **/analitik <id_konten>**: Melihat analitik terperinci untuk konten tertentu, termasuk penayangan, pembelian, dan tingkat konversi.
- **/topkreator**: Melihat 10 kreator teratas berdasarkan penghasilan.
- **Gambar Blur**: Gambar yang diunggah secara otomatis dibuat versi buramnya untuk pratinjau di katalog.

### Untuk Admin/Moderator
- **/moderasi**: Melihat konten yang tertunda untuk disetujui atau ditolak.
- **Persetujuan Konten**: Menyetujui atau menolak konten yang dikirimkan oleh kreator.
- **Blokir Kreator**: Memblokir kreator agar tidak dapat mengirimkan atau menjual konten.

## Fitur Tambahan
- **Saldo Refund Otomatis**: Jika pengiriman konten gagal setelah pembelian, saldo pengguna akan dikembalikan secara otomatis.
- **Rating & Ulasan**: Setelah membeli konten, pengguna dapat memberikan rating.
- **Papan Peringkat**: Papan peringkat untuk kontributor dan kreator teratas.

## Instalasi

1.  **Database**:
    *   Buat database baru di MySQL/MariaDB.
    *   Impor `database.sql` untuk membuat tabel yang diperlukan.

2.  **Konfigurasi**:
    *   Salin `config/config.php.example` menjadi `config/config.php`.
    *   Isi semua konstanta yang ada di `config/config.php`:
        *   `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`: Sesuaikan dengan konfigurasi database Anda.
        *   `BOT_TOKEN`: Token bot Anda yang didapat dari @BotFather.
        *   `EDITOR_CHANNEL_ID`: ID Channel Editor (channel privat).
        *   `PUBLIC_CHANNEL_ID`: ID Channel Publik.
        *   `'your_public_channel_username'`: ganti dengan username channel publik Anda.

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
        * * * * * php /path/to/your/project/cronjobs/refund_errors.php
        ```

## Cara Kerja Admin/Editor

1.  **Menambahkan Admin**: Untuk menjadikan pengguna sebagai admin/editor, ubah kolom `role` di tabel `users` secara manual dari `member` menjadi `admin` atau `editor`.
2.  **Moderasi**: Semua kiriman yang dikonfirmasi oleh pengguna akan masuk ke Channel Editor. Admin/Editor hanya perlu menekan tombol yang tersedia di bawah setiap postingan di channel tersebut.
