<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chama App</title>
</head>
<body>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/pages/dashboard.php">Dashboard</a>
            <a href="<?= SITE_URL ?>/pages/chamas/create.php">Create Chama</a>
            <a href="<?= SITE_URL ?>/pages/auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/pages/auth/login.php">Login</a>
            <a href="<?= SITE_URL ?>/pages/auth/register.php">Register</a>
        <?php endif; ?>
    </nav>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>