---

## 🧾 Fungsi `/penghasilan`

### Tujuan:

Memungkinkan **user kreator** melihat **penghasilan dari konten berbayar** yang mereka buat dan dijual lewat bot.

---

## 🧠 Output Ideal dari Perintah `/penghasilan`

Ketika user mengetik `/penghasilan`, bot akan membalas:

```
📈 Penghasilan Kamu

🎨 Jumlah Konten Berbayar: 7
💵 Total Pendapatan: Rp85.000
👥 Jumlah Pembeli Unik: 19

Konten Terlaris:
- Foto  #78 – 12 pembeli – Rp30.000
- Video #102 – 7 pembeli – Rp21.000

🔘 Lihat Riwayat Transaksi
🔘 Tarik Saldo
```

---

## 📊 Data yang Diperlukan

1. Tabel `users` (kolom: `id`, `balance`)
2. Tabel `paid_contents` (kolom: `id`, `user_id`, `price`, `title`, `type`)
3. Tabel `purchases` (kolom: `user_id`, `content_id`, `price`, `created_at`)

---

## 🧱 Struktur Output `/penghasilan`

| Data             | Cara Didapat                                                    |
| ---------------- | --------------------------------------------------------------- |
| Jumlah konten    | `COUNT(*)` dari `paid_contents` milik user                      |
| Total pendapatan | `SUM(price)` dari `purchases` pada konten milik user            |
| Pembeli unik     | `COUNT(DISTINCT user_id)` dari `purchases`                      |
| Konten terlaris  | `GROUP BY content_id` → urutkan `COUNT(*) DESC` → ambil 2 besar |

---

### 🎯 Prompt:

> Kamu adalah agen backend developer.
> Tugasmu adalah membangun fitur `/penghasilan` untuk sistem bot Telegram konten berbayar.

#### Fungsi fitur:

* Menampilkan laporan penghasilan user dari konten berbayar yg dia upload dan telah dibeli oleh user lain.

#### Database yang tersedia:

* `users(id, telegram_id, balance)`
* `paid_contents(id, user_id, type, price)`
* `purchases(id, user_id, content_id, price, created_at)`

#### Alur:

1. Identifikasi user berdasarkan `telegram_id` dari message
2. Ambil semua `paid_contents` milik user tsb
3. Hitung total pendapatan user dari `purchases`
4. Hitung jumlah pembeli unik
5. Cari konten terlaris (paling banyak dibeli)
6. Balas ke user dalam format teks Telegram (markdown/text)

#### Permintaan tambahan:

* Buat fungsi PHP bernama `handlePenghasilan()`
* Buat query SQL terpisah agar bisa di-reuse
* Gunakan PDO / MySQLi dan sanitize input
* Return data sebagai teks siap dikirim ke Telegram

---

### 💡 Tambahan Prompt:

* tampilkan riwayat transaksi jika user klik tombol “Lihat Riwayat Transaksi”
* Buat sistem pagination jika transaksi banyak
* Tambahkan total saldo yang bisa ditarik

---

## 🧠 ALUR LOGIKA PEMROSESAN `/penghasilan`

1. **Terima perintah dari user**

   * Perintah: `/penghasilan`
   * Ambil `telegram_id` user dari update bot

2. **Ambil ID user dari DB**

   * SQL: `SELECT id FROM users WHERE telegram_id = ?`

3. **Ambil total konten berbayar user**

   * SQL: `SELECT COUNT(*) FROM paid_contents WHERE user_id = ?`

4. **Ambil total pendapatan user**

   * SQL gabungan:

   ```sql
   SELECT SUM(p.price)
   FROM purchases p
   JOIN paid_contents c ON p.content_id = c.id
   WHERE c.user_id = ?
   ```

5. **Hitung pembeli unik**

   * SQL:

   ```sql
   SELECT COUNT(DISTINCT p.user_id)
   FROM purchases p
   JOIN paid_contents c ON p.content_id = c.id
   WHERE c.user_id = ?
   ```

6. **Ambil 2 konten terlaris**

   * SQL:

   ```sql
   SELECT c.id, COUNT(p.id) AS jumlah_beli, SUM(p.price) AS total
   FROM purchases p
   JOIN paid_contents c ON p.content_id = c.id
   WHERE c.user_id = ?
   GROUP BY c.id
   ORDER BY jumlah_beli DESC
   LIMIT 2
   ```

7. **Format data menjadi teks Telegram**

   * Gunakan markdown / plain text
   * Tambahkan inline keyboard (Lihat Riwayat | Tarik Saldo)
