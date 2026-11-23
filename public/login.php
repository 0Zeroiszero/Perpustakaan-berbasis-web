<?php
// Login: email + password terhadap tabel users; ?next= mengembalikan ke halaman asal.
require __DIR__ . '/../includes/auth_guard.php';
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $hasil = login_user($email, (string) ($_POST['password'] ?? ''));

    if ($hasil === null) {
        $next = (string) ($_POST['next'] ?? '');
        header('Location: ' . (next_valid($next) ? $next : dashboard_url()));
        exit;
    }

    $param = $hasil === 'nonaktif' ? 'nonaktif=1' : 'gagal=1';
    header('Location: ' . url('login.php?' . $param . '&email=' . urlencode($email)));
    exit;
}

if (akun()) {
    header('Location: ' . dashboard_url());
    exit;
}

$email = strtolower(trim($_GET['email'] ?? ''));
$gagal = isset($_GET['gagal']);
$nonaktif = isset($_GET['nonaktif']);
$next = next_valid((string) ($_GET['next'] ?? '')) ? (string) $_GET['next'] : '';

$judul = 'Login - PustakaDigital';
$css = ['login.css'];
$bodyClass = 'login-page';
$aktif = '';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<!-- HEADER -->
<header class="text-center py-5">
    <div class="container">
        <h1 class="page-title">Selamat Datang</h1>
        <p class="text-secondary mb-0">Masuk ke akun perpustakaanmu.</p>
    </div>
</header>

<!-- MAIN -->
<main class="pb-5">
    <div class="container">
        <?php if ($nonaktif): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                <span>Akun ini dinonaktifkan oleh admin. Hubungi pengelola perpustakaan untuk mengaktifkannya kembali.</span>
            </div>
        <?php endif; ?>

        <div class="dropdown demo-akun mb-3">
            <button class="btn btn-ungu w-100 dropdown-toggle" type="button" id="demoAkun"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-badge me-1" aria-hidden="true"></i>Akun demo
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="demoAkun">
                <li>
                    <button class="dropdown-item py-2" type="button" data-email="admin@pustaka.com"
                        data-password="admin123">
                        <span class="fw-semibold text-ungu d-block">
                            <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Admin
                        </span>
                        <span class="small text-secondary">admin@pustaka.com</span>
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button class="dropdown-item py-2" type="button" data-email="andi@email.com"
                        data-password="andi123">
                        <span class="fw-semibold text-ungu d-block">
                            <i class="bi bi-person me-1" aria-hidden="true"></i>User
                        </span>
                        <span class="small text-secondary">andi@email.com</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="login-card">

            <form id="loginForm" method="post" action="<?= url('login.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">Email</label>
                    <input id="email" name="email" type="email" class="form-control<?= $gagal ? ' is-invalid' : '' ?>"
                        value="<?= e($email) ?>" placeholder="nama@email.com"
                        autocomplete="email" required>
                    <div class="invalid-feedback">Email atau password salah.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <input id="password" name="password" type="password" class="form-control"
                        placeholder="Masukkan password" autocomplete="current-password" required>
                </div>

                <button class="btn btn-ungu w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                    Masuk
                </button>
            </form>

            <p class="text-center text-secondary mt-4 mb-0">
                Belum punya akun?
                <a href="<?= url('register.php') ?>" class="text-ungu fw-bold">
                    Daftar
                </a>
            </p>

        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>

<script>
    // Akun demo: klik salah satu -> kolom email + password keisi.
    // Inline di halaman ini (bukan script.js) supaya tidak pernah kena cache browser.
    document.querySelectorAll(".demo-akun [data-email]").forEach(function (item) {
        item.addEventListener("click", function () {
            document.getElementById("email").value = item.dataset.email;
            document.getElementById("password").value = item.dataset.password;
        });
    });
</script>
</body>

</html>
