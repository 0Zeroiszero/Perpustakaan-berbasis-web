<?php
require __DIR__ . '/_auth.php';
header('Cache-Control: no-store');
logout_user();
header('Location: login.php');
exit;
