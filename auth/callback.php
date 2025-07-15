<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Find user by login token
    $user = findUserByLoginToken($token);

    if ($user) {
        // Valid token, create session for the user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Remove the token from the database
        clearLoginToken($user['id']);

        // Redirect user to the appropriate page
        header('Location: ../pages/' . $_SESSION['role'] . '.php');
        exit();
    } else {
        // Invalid token
        echo "Link login tidak valid atau telah kedaluwarsa.";
    }
} else {
    // No token provided
    echo "Token tidak ditemukan.";
}
?>
