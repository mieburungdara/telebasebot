# Bot Monetisasi Konten Telegram

Bot Telegram canggih yang dirancang untuk memonetisasi konten. Ini memberdayakan para kreator untuk menjual media berbayar (gambar, video) kepada pelanggan, sementara juga menyediakan sistem manajemen konten yang kuat untuk admin. Bot ini dilengkapi dengan fitur-fitur seperti analitik, sistem peringkat, dan alur kerja moderasi konten.

## Ikhtisar

Proyek ini menyediakan platform berbasis bot Telegram di mana pengguna dapat membeli konten eksklusif dari para kreator. Kreator dapat mengunggah konten mereka, menetapkan harga, dan melacak penghasilan mereka. Admin dapat memoderasi konten untuk memastikan kualitas dan kepatuhan terhadap pedoman. Bot ini dibangun dengan PHP dan menggunakan database MySQL untuk menyimpan data.

## Fitur

### Untuk Pengguna (Pembeli)
- **/start**: Memulai interaksi dengan bot.
- **/katalog**: Menelusuri konten yang tersedia untuk dibeli.
- **/belikonten <id_konten>**: Membeli konten.
- **/riwayatbeli**: Melihat riwayat pembelian.
- **/saldo**: Memeriksa saldo akun.
- **/tarik <jumlah>**: Meminta penarikan dana.
- **Rating Konten**: Memberi peringkat pada konten setelah pembelian.
- **Pengembalian Dana Otomatis**: Secara otomatis menerima pengembalian dana jika pengiriman konten gagal.

### Untuk Kreator
- **/buatkonten**: Mengunggah konten baru untuk dijual.
- **/kontenku**: Mengelola konten yang telah diunggah.
- **/penghasilan**: Melacak total pendapatan, jumlah pembeli, dan konten terlaris.
- **/analitik <id_konten>**: Mendapatkan analitik terperinci untuk setiap konten, termasuk jumlah penayangan, pembelian, dan tingkat konversi.
- **Pratinjau Gambar Buram**: Gambar secara otomatis dibuat versi buramnya untuk pratinjau di katalog.

### Untuk Admin/Moderator
- **/moderasi**: Meninjau dan menyetujui/menolak konten yang dikirimkan.
- **Manajemen Kreator**: Memblokir kreator yang melanggar aturan.
- **Manajemen Konten**: Memantau dan mengelola semua konten di platform.

### Fitur Papan Peringkat
- **/topkreator**: Menampilkan 10 kreator teratas berdasarkan pendapatan.
- **/topkontributor**: Menampilkan 10 kontributor teratas berdasarkan poin.

## Alur Kerja Bot

1. **Pengiriman Konten**: Kreator mengirimkan media (gambar/video) ke bot, menetapkan harga, dan menambahkan deskripsi.
2. **Moderasi**: Konten yang dikirimkan muncul di saluran editor pribadi di mana admin dapat meninjaunya.
3. **Persetujuan/Penolakan**: Admin dapat menyetujui atau menolak konten. Konten yang disetujui akan tersedia di katalog publik.
4. **Pembelian Konten**: Pengguna dapat menelusuri katalog dan membeli konten menggunakan saldo mereka.
5. **Pengiriman Konten**: Setelah pembelian berhasil, bot mengirimkan konten ke pengguna.
6. **Distribusi Pendapatan**: Pendapatan dari penjualan secara otomatis didistribusikan ke kreator (dengan potongan platform).

## Instalasi

### 1. Prasyarat
- Server web dengan PHP
- Database MySQL/MariaDB
- Composer (direkomendasikan untuk mengelola dependensi)

### 2. Kloning Repositori
```bash
git clone https://github.com/username/repo.git
cd repo
```

### 3. Konfigurasi Database
- Buat database baru di MySQL/MariaDB.
- Impor file `database.sql` untuk membuat tabel yang diperlukan:
```bash
mysql -u username -p database_name < database.sql
```

### 4. Konfigurasi Bot
- Salin file konfigurasi contoh:
```bash
cp config/config.php.example config/config.php
```
- Edit `config/config.php` dan isi konstanta berikut:
    - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`: Kredensial database Anda.
    - `BOT_TOKEN`: Token bot Telegram Anda (dapatkan dari @BotFather).
    - `EDITOR_CHANNEL_ID`: ID saluran Telegram pribadi untuk moderasi konten.
    - `PUBLIC_CHANNEL_ID`: ID saluran Telegram publik tempat konten yang disetujui dipublikasikan.
    - `PUBLIC_CHANNEL_USERNAME`: Nama pengguna saluran publik Anda (tanpa "@").

### 5. Atur Webhook
- Unggah file proyek ke server web Anda.
- Atur webhook agar Telegram mengirim pembaruan ke skrip `bot.php` Anda. Jalankan perintah ini sekali dengan mengganti `YOUR_BOT_TOKEN` dan `YOUR_WEB_APP_URL`:
```bash
curl "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=<YOUR_WEB_APP_URL>/bot.php"
```

### 6. Atur Cronjobs
Cronjob diperlukan untuk tugas-tugas otomatis seperti mempublikasikan konten, mengirim pengingat, dan memproses pengembalian dana. Atur cronjob berikut untuk berjalan setiap menit:

```
* * * * * php /path/to/project/cronjobs/auto_publish.php
* * * * * php /path/to/project/cronjobs/check_confirmation_reminder.php
* * * * * php /path/to/project/cronjobs/check_pending_delete.php
* * * * * php /path/to/project/cronjobs/refund_errors.php
* * * * * php /path/to/project/cronjobs/update_caption_countdown.php
```

#### Detail Cronjob
- `auto_publish.php`: Secara otomatis mempublikasikan konten yang menunggu lama.
- `check_confirmation_reminder.php`: Mengirim pengingat untuk kiriman yang belum dikonfirmasi.
- `check_pending_delete.php`: Menghapus kiriman yang kedaluwarsa dan belum dikonfirmasi.
- `refund_errors.php`: Memproses pengembalian dana untuk pembelian yang gagal.
- `update_caption_countdown.php`: Memperbarui waktu hitung mundur pada pesan moderasi.

## Skema Database

Database terdiri dari tabel-tabel berikut:
- `users`: Menyimpan informasi tentang pengguna, kreator, dan admin.
- `messages`: Melacak pesan dan media yang dikirimkan.
- `paid_contents`: Menyimpan detail tentang konten berbayar.
- `purchases`: Mencatat semua transaksi pembelian.
- `ratings`: Menyimpan peringkat dan ulasan untuk konten.
- `logs`: Mencatat tindakan penting untuk tujuan audit.
- `error_logs`: Mencatat kesalahan, terutama yang terkait dengan pengiriman konten.

## Lisensi

Proyek ini dilisensikan di bawah Lisensi MIT. Lihat file `LICENSE` untuk detailnya.
