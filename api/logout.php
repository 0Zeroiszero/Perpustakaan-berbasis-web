<?php
// Logout eksplisit: satu-satunya tempat cookie login dihapus
require __DIR__ . '/_auth.php';
header('Cache-Control: no-store');
setcookie('pustaka_login', '', ['path' => '/', 'expires' => time() - 3600]);
header('Location: login.php');
exit;
