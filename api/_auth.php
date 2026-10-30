<?php
require_once __DIR__ . '/_db.php';

if (session_status() === PHP_SESSION_NONE) {
    // Vercel menerminasi TLS di proxy, jadi HTTPS juga dicek dari header forwarded.
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Akun yang sedang login, dibaca ulang dari database supaya perubahan role/status langsung berlaku.
function akun(): ?array
{
    static $akun = false;

    if ($akun !== false) {
        return $akun;
    }

    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $akun = null;
    }

    $user = row(
        "SELECT id_user AS id, CONCAT_WS(' ', nama_depan, nama_belakang) AS nama, email,
                CASE WHEN user_role = 'Admin' THEN 'Admin' ELSE 'User' END AS role
         FROM users WHERE id_user = ?",
        [$id]
    );

    if (!$user) {
        logout_user();
        return $akun = null;
    }

    return $akun = $user;
}

function login_user(string $email, string $password): bool
{
    $user = row(
        "SELECT id_user, password FROM users WHERE email = ?",
        [strtolower(trim($email))]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id_user'];

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
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
    if (akun()['role'] !== 'Admin') {
        header('Location: dashboard-user.php');
        exit;
    }
}

// Halaman dashboard milik akun yang sedang login
function dashboard_url(): string
{
    return (akun()['role'] ?? '') === 'Admin' ? 'dashboard-admin.php' : 'dashboard-user.php';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function cek_csrf(): void
{
    $token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';

    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

function flash(string $pesan, string $tipe = 'success'): void
{
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function ambil_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

// Alert Bootstrap untuk pesan setelah redirect (PRG pattern).
function tampilkan_flash(): void
{
    $flash = ambil_flash();
    if (!$flash) {
        return;
    }

    $tipe = $flash['tipe'] === 'success' ? 'success' : 'danger';
    echo '<div class="alert alert-' . $tipe . ' d-flex align-items-center gap-2" role="alert">'
        . '<i class="bi bi-' . ($tipe === 'success' ? 'check-circle' : 'exclamation-triangle') . '" aria-hidden="true"></i>'
        . '<span>' . e($flash['pesan']) . '</span>'
        . '</div>';
}
