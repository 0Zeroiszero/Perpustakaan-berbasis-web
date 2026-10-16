<?php
// shortcut: akun demo hardcoded, password polos, tanpa session — ganti database + password_hash + session saat butuh auth sungguhan.
const AKUN = [
    'admin@pustakadigital.id' => ['password' => 'admin123', 'peran' => 'admin', 'dashboard' => 'dashboard-admin.php'],
    'user@pustakadigital.id' => ['password' => 'user123', 'peran' => 'user', 'dashboard' => 'dashboard-user.php'],
];

// Akun dari cookie; null kalau cookie kosong atau bukan email yang terdaftar
function akun(): ?array
{
    $email = strtolower(trim($_COOKIE['pustaka_login'] ?? ''));
    return AKUN[$email] ?? null;
}

function wajib_login(): void
{
    header('Cache-Control: no-store');
    if (!akun()) {
        header('Location: login.php');
        exit;
    }
}

function wajib_admin(): void
{
    wajib_login();
    if (akun()['peran'] !== 'admin') {
        header('Location: dashboard-user.php');
        exit;
    }
}

// Halaman dashboard milik akun yang sedang login
function dashboard_url(): string
{
    return (akun()['peran'] ?? '') === 'admin' ? 'dashboard-admin.php' : 'dashboard-user.php';
}
