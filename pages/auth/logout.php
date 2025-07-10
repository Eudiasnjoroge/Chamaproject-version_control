<?php
require_once __DIR__ . '/../../includes/header.php';

logoutUser();
$_SESSION['message'] = 'You have been logged out.';
redirect(SITE_URL . '/pages/auth/login.php');
?>