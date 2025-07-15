Mantap, Reza! Kalau kamu ingin menambahkan **fitur saldo** untuk user, berikut adalah **alur dan konsep lengkapnya**, termasuk bagaimana user bisa:

* Memiliki saldo 💰
* Menarik saldo ke rekening / e-wallet 💸

---

## 🧱 1. STRUKTUR DASAR (SALDO USER)

### Tambah Kolom di DB:

* Di tabel `users`:

  * `balance` → total saldo saat ini
  * `pending_withdrawal` → sedang proses penarikan

---

## 🔄 2. CARA USER MENDAPAT SALDO

Berikut beberapa cara agar user bisa mengumpulkan saldo:

### A. **Dari Media yg Diterbitkan**

> Misal: 1 kiriman diterbitkan = +Rp500

```plaintext
✅ Kiriman kamu diterbitkan!
🎁 Kamu mendapat Rp500
💰 Saldo kamu sekarang: Rp3.500
```

### B. **Dari Bonus Kontributor / Tantangan**

> Kirim 5 media dalam seminggu = +Rp2.000

### C. **Dari Komisi Afiliasi / Referral**

> Undang teman pakai link / kode = bonus Rp1.000

---

## 💳 3. CEK SALDO

User bisa gunakan `/saldo` untuk melihat:

```
💰 Saldo kamu: Rp4.500
🕓 Dalam proses penarikan: Rp0
🎯 Minimal tarik: Rp10.000
Gunakan perintah: /tarik <jumlah>
```

---

## 🏧 4. TARIK SALDO (MANUAL)

### Format:

```
/tarik 15000
```

Bot akan balas:

```
📤 Permintaan penarikan Rp15.000 telah diterima.
Silakan kirim info penarikan:
- Nama Bank / eWallet
- Nomor Rekening
- Nama Pemilik

Kirim ke admin melalui tombol di bawah:
🔘 Hubungi Admin
```


## 🚫 6. BATASAN & KEAMANAN

| Proteksi           | Penjelasan                                          |
| ------------------ | --------------------------------------------------- |
| Minimum Penarikan  | Misal: Rp10.000                                     |
| Pending Withdrawal | Saldo tidak bisa ditarik dua kali                   |

---
