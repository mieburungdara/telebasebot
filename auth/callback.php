<?php
session_start();

if (isset($_GET['token']) && isset($_SESSION['login_token']) && $_GET['token'] === $_SESSION['login_token']) {
    // Token valid, buat sesi untuk pengguna
    $_SESSION['username'] = $_SESSION['login_username'];

    // Di dunia nyata, Anda akan mengambil peran pengguna dari database
    // berdasarkan username mereka. Untuk saat ini, kita akan memberikan peran
    // secara acak untuk tujuan demonstrasi.
    $roles = ['member', 'editor', 'admin'];
    $_SESSION['role'] = $roles[array_rand($roles)];

    // Hapus token dari sesi
    unset($_SESSION['login_token']);
    unset($_SESSION['login_username']);

    // Arahkan pengguna ke halaman yang sesuai
    header('Location: ../pages/' . $_SESSION['role'] . '.php');
    exit();
} else {
    // Token tidak valid
    echo "Link login tidak valid atau telah kedaluwarsa.";
}
?>
