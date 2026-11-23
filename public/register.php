<?php
// Registrasi akun member baru (pengganti halaman info daftar.php lama).
require __DIR__ . '/../includes/auth_guard.php';
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $nama = trim((string) ($_POST['display_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $gagal = match (true) {
        $nama === '' || mb_strlen($nama) > 100 => 'nama',
        !filter_var($email, FILTER_VALIDATE_EMAIL) => 'email',
        row('SELECT user_id FROM users WHERE email = ?', [$email]) !== null => 'email',
        strlen($password) < 6 => 'password',
        default => null,
    };

    if ($gagal !== null) {
        header('Location: ' . url('register.php?gagal=' . $gagal . '&email=' . urlencode($email) . '&nama=' . urlencode($nama)));
        exit;
    }

    q(
        'INSERT INTO users (email, password_hash, display_name, role, is_active) VALUES (?, ?, ?, ?, 1)',
        [$email, password_hash($password, PASSWORD_DEFAULT), $nama, 'member']
    );
    flash('Akun berhasil dibuat. Silakan masuk.');
    header('Location: ' . url('login.php'));
    exit;
}

if (akun()) {
    header('Location: ' . dashboard_url());
    exit;
}

$gagal = (string) ($_GET['gagal'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$nama = trim($_GET['nama'] ?? '');

$judul = 'Daftar - PustakaDigital';
$css = ['login.css'];
$bodyClass = 'login-page';
$aktif = '';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<header class="text-center py-5">
    <div class="container">
        <h1 class="page-title">Buat Akun</h1>
        <p class="text-secondary mb-0">Gratis untuk pelajar Indonesia.</p>
    </div>
</header>

<main class="pb-5">
    <div class="container">
        <div class="login-card">
            <?php tampilkan_flash(); ?>
            <form method="post" action="<?= url('register.php') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="display_name">Nama tampilan</label>
                    <input id="display_name" name="display_name" type="text"
                        class="form-control<?= $gagal === 'nama' ? ' is-invalid' : '' ?>"
                        value="<?= e($nama) ?>" placeholder="Nama kamu" maxlength="100" required>
                    <div class="invalid-feedback">Nama wajib diisi (maksimal 100 karakter).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">Email</label>
                    <input id="email" name="email" type="email"
                        class="form-control<?= $gagal === 'email' ? ' is-invalid' : '' ?>"
                        value="<?= e($email) ?>" placeholder="nama@email.com" autocomplete="email" required>
                    <div class="invalid-feedback">Email tidak valid atau sudah terdaftar.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <input id="password" name="password" type="password"
                        class="form-control<?= $gagal === 'password' ? ' is-invalid' : '' ?>"
                        placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                    <div class="invalid-feedback">Password minimal 6 karakter.</div>
                </div>

                <button class="btn btn-ungu w-100 py-2">
                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                    Daftar
                </button>
            </form>

            <p class="text-center text-secondary mt-4 mb-0">
                Sudah punya akun?
                <a href="<?= url('login.php') ?>" class="text-ungu fw-bold">Masuk</a>
            </p>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
