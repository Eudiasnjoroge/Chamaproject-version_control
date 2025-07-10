<?php
// Dynamically build the base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script = $_SERVER['SCRIPT_NAME'];
$path = str_replace(basename($script), '', $script);

// This becomes something like: http://localhost/chama%20site/
define('SITE_URL', $protocol . '://' . $host . $path);

// Uploads folder (unchanged)
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
?>
