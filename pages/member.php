<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header('Location: ../auth/login.php');
    exit();
}

include '../includes/header.php';
?>

<h1>Halaman Member</h1>
<p>Selamat datang, <?php echo $_SESSION['username']; ?>!</p>
<a href="../auth/logout.php">Logout</a>

<?php include '../includes/footer.php'; ?>
