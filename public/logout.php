<?php
// Logout: hancurkan sesi lalu kembali ke beranda.
require __DIR__ . '/../includes/auth_guard.php';
header('Cache-Control: no-store');
logout_user();
header('Location: ' . url('index.php'));
exit;
