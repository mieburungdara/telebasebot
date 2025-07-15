<?php
require_once __DIR__ . '/config/config.php';
include 'includes/header.php';
?>

<div class="jumbotron">
    <h1 class="display-4">Selamat Datang!</h1>
    <p class="lead">Ini adalah halaman utama website.</p>
    <hr class="my-4">
    <p>Silakan login untuk melanjutkan.</p>
    <a class="btn btn-primary btn-lg" href="https://t.me/<?php echo BOT_USERNAME; ?>?start=login" role="button">Login dengan Telegram</a>
</div>

<?php include 'includes/footer.php'; ?>
