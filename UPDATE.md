Berikut daftar **perintah (commands)** dan **template pesan** yang bisa kamu kirimkan ke pengguna, lengkap dengan **tujuan dan kapan digunakan**:

---

## 🟢 **PERINTAH UNTUK PENGGUNA**

### 1. `/start`

> 🔹 Tujuan: Perkenalan bot & aturan awal
> 🔹 Waktu: Saat user pertama kali kirim pesan

**Contoh balasan:**

```
👋 Hai, selamat datang di bot kiriman media!
Kamu bisa mengirimkan foto, video, atau teks untuk kami moderasi dan publikasikan ke channel publik.

📌 Setelah kirim, kamu akan dapat tombol untuk mengkonfirmasi.
⏳ Jika tidak dikonfirmasi dalam 5 menit, kiriman akan dihapus otomatis.

Ketik /bantuan untuk info lebih lanjut.
```

---

### 2. `/bantuan`

> 🔹 Tujuan: Menjelaskan fungsi-fungsi bot
> 🔹 Waktu: Kapan saja user bingung

**Contoh balasan:**

```
📖 *Panduan Bot*

1. Kirim media (foto/video/teks)
2. Klik tombol ✅ Upload atau ❌ Hapus
3. Media kamu akan ditinjau oleh admin
4. Jika disetujui → akan diterbitkan ke channel
5. Kamu akan mendapat poin setiap media diterbitkan

📌 Perintah:
- /statistik → Lihat kontribusimu
- /topkontributor → Lihat 10 kontributor terbaik
```

---

### 3. `/statistik`

> 🔹 Tujuan: Tampilkan statistik kontribusi pribadi

**Contoh balasan:**

```
📊 Statistik Kontribusi Kamu

✨ Total Poin: 76  
📝 Total Kiriman: 5  
✅ Diterbitkan: 3  
❌ Dibatalkan: 1  
⏳ Menunggu Editor: 1
```

---

### 4. `/topkontributor`

> 🔹 Tujuan: Pancing semangat user, tunjukkan leaderboard

**Contoh balasan:**

```
🏆 Top 10 Kontributor:

1. @reza_dev – 152 poin  
2. @alya88 – 140 poin  
3. 👤 (tanpa username) – 125 poin  
...
```

---

### 5. `/poin` *(opsional)*

> 🔹 Tujuan: Info poin user saja (lebih ringkas dari `/statistik`)

**Contoh balasan:**

```
✨ Poin kamu saat ini: 87
```

---

### 6. `/histori` *(opsional)*

> 🔹 Tujuan: Tampilkan 5 kiriman terakhir + status

**Contoh balasan:**

```
🗂️ Riwayat Kiriman Kamu:

1. Foto – Diterbitkan  
2. Video – Dibatalkan  
3. Teks – Menunggu Editor  
...
```

---

## 🔴 **PESAN SISTEM OTOMATIS KE USER**

### A. Setelah kirim media (status pending)

```
📩 Media kamu telah kami terima!

Klik tombol di bawah ini:
✅ Upload → Untuk melanjutkan ke admin
❌ Hapus → Untuk membatalkan
⏳ Jika tidak dikonfirmasi dalam 5 menit, akan dihapus otomatis.
```

---

### B. Setelah disetujui & dipublish

```
✅ Media kamu telah diterbitkan ke channel!

📎 Klik tombol di bawah untuk melihat postingan:
🔘 [Lihat Posting]
```

---

### C. Jika kiriman dibatalkan admin

```
❌ Mohon maaf, kiriman kamu tidak kami terbitkan kali ini.

Tetap semangat dan kirim konten menarik lainnya ya! 😊
```

---

### D. Jika kiriman gagal dipublish

```
⚠️ Gagal menerbitkan media kamu karena kesalahan teknis. Silakan coba lagi nanti.
```

---
