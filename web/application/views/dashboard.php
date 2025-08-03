<?php $this->load->view('partials/header'); ?>

<div id="container">
    <h1>Selamat Datang di Dasbor Anda</h1>

    <div id="body">
        <p>Di sini Anda dapat mengelola konten dan akun Anda.</p>

        <div class="feature-box">
            <h2>Koleksi Konten Saya</h2>
            <p>Lihat semua konten yang telah Anda beli.</p>
            <a href="/dashboard/koleksi">Lihat Koleksi</a>
        </div>

        <div class="feature-box">
            <h2>Riwayat Pembelian</h2>
            <p>Lacak semua transaksi Anda.</p>
            <a href="/dashboard/riwayat">Lihat Riwayat</a>
        </div>

        <div class="feature-box">
            <h2>Manajemen Saldo</h2>
            <p>Periksa saldo Anda, lakukan pembelian, dan tarik dana.</p>
            <a href="/dashboard/saldo">Kelola Saldo</a>
        </div>

        <div class="feature-box">
            <h2>Manajemen Bot</h2>
            <p>Kelola bot telegram anda.</p>
            <a href="/bot">Kelola Bot</a>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
