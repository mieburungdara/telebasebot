<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Login
            </div>
            <div class="card-body">
                <p>Untuk login, silakan masukkan username Telegram Anda dan klik tombol di bawah. Kami akan mengirimkan link login ke akun Telegram Anda.</p>
                <form action="telegram.php" method="post">
                    <div class="form-group">
                        <label for="username">Username Telegram</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim Link Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
