🧱 1. PERANCANGAN DATABASE
A. Tabel users
Menyimpan informasi pengguna:

id, telegram_id, username, role, points, created_at

B. Tabel messages
Menyimpan semua media/konten yg dikirim user:

id, user_id, message_id, type, content, caption

status: pending, ready_review, forwarded, cancelled, deleted, error

auto_publish_at, editor_message_id, public_message_id

created_at, updated_at

C. Tabel logs (opsional)
Menyimpan log aktivitas:

Siapa konfirmasi, siapa batalin, waktu, perubahan caption

⚙️ 2. FUNGSI DASAR BOT
A. Menerima Pesan
Bot menerima pesan dari user (foto, video, teks)

Simpan ke DB → status = pending

Kirim balasan ke user dengan tombol:

✅ Upload

❌ Hapus

B. Auto Delete (5 Menit)
Jika user tidak klik tombol konfirmasi dalam 5 menit:

Bot hapus data + kirim notif jika perlu

📤 3. PROSES UPLOAD (Setelah Klik ✅)
A. Bot kirim media ke channel_editor
Tambahkan:

Caption (asli + countdown auto-publish + poin user)

Inline keyboard:

✅ Publish

❌ Batal

⏱ +1m / +2m / +5m / +10m

B. Simpan:
editor_message_id

auto_publish_at = now + 10 menit

Ubah status: ready_review

🔁 4. CRONJOBS
A. check_pending_delete.php (tiap 1 menit)
Hapus kiriman yg tidak dikonfirmasi user dalam 5 menit

B. update_caption_countdown.php (tiap 1 menit)
Update caption di channel_editor → countdown waktu auto-publish

C. auto_publish.php (tiap 1 menit)
Kirim media ke channel_public jika auto_publish_at <= now

Ubah status: forwarded

Hapus tombol inline di channel_editor

Kirim notifikasi ke user: "Media kamu telah dipublish" + tombol Lihat Posting

🧑‍💻 5. ADMIN / EDITOR ACTION
Jika Admin Klik:
✅ Publish

Kirim ke channel publik

Update status = forwarded

Tambah poin user

Kirim notifikasi ke user

❌ Batal

Update status = cancelled

Kirim notif ke user (opsional)

⏱ Perpanjang

Tambah waktu auto_publish_at

Update countdown caption

🧠 6. FITUR TAMBAHAN PENGGUNA
Perintah:
/topkontributor → tampilkan 10 user dengan poin tertinggi

/statistik → tampilkan poin user, jumlah kiriman, statusnya

(Opsional) /histori, /poin, /statistikmingguan, dll
🧑‍🔧 7. FITUR TAMBAHAN ADMIN
Edit caption langsung dari Telegram

Notifikasi jika gagal publish

Auto-publish jika tidak ada respon admin

Sistem tag # untuk kategori

Tracking log aksi admin

Beda hak akses berdasarkan role (admin, superadmin, editor, member)

🌐 8. DASHBOARD WEB (Opsional Tapi Kuat Banget)
Dibuat pakai CodeIgniter 3, struktur sederhana

Modul Dashboard:
Login admin (role-based)

Tabel data kiriman (filter status, tanggal, user, tag)

Edit caption

Kirim manual ke channel

Log semua aksi

Statistik global + grafik kontribusi

🔐 9. KEAMANAN & PENUTUP
Cek semua callback_data untuk pastikan validitas

Validasi bahwa hanya admin/editor yg bisa klik tombol editor

Gunakan chat_id dan message_id sebagai identitas utama setiap media

Simpan semua waktu dalam UTC lalu convert jika perlu

🎯 HASIL AKHIR
Sistem bot kamu akan:

✅ Menerima & simpan kiriman media dari user
✅ Moderasi otomatis + manual oleh admin
✅ Auto publish jika tidak ada aksi admin
✅ Penghargaan poin + leaderboard user
✅ Bisa dikembangkan jadi platform konten publikasi komunitas atau media
