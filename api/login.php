<?php
require __DIR__ . '/_auth.php';
header('Cache-Control: no-store');
header('Clear-Site-Data: "cache"'); // sementara: paksa browser buang script.js lama; hapus setelah semua device hard refresh

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (login_user($email, (string) ($_POST['password'] ?? ''))) {
        header('Location: ' . dashboard_url());
        exit;
    }

    header('Location: login.php?gagal=1&email=' . urlencode($email));
    exit;
}

if (akun()) {
    header('Location: ' . dashboard_url());
    exit;
}

$email = strtolower(trim($_GET['email'] ?? ''));
$gagal = isset($_GET['gagal']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body class="login-page">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg border-bottom sticky-top bg-white">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../index.html">
                <span class="logo">P</span>
                PustakaDigital
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
                aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="katalog.php">Katalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= dashboard_url() ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-ungu ms-lg-2" href="login.php">Masuk</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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

                <form id="loginForm" method="post" action="login.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control<?= $gagal ? ' is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="nama@email.com"
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
                    <a href="daftar.php" class="text-ungu fw-bold">
                        Daftar
                    </a>
                </p>

            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <a href="../index.html" class="footer-brand">
                        <span class="footer-logo">P</span>
                        <span>PustakaDigital</span>
                    </a>
                    <p class="footer-description mt-3 mb-0">
                        Perpustakaan digital untuk menemukan, membaca, dan mengelola koleksi buku dengan mudah.
                    </p>
                </div>

                <div class="col-6 col-lg-3 col-md-3">
                    <h2 class="footer-title h6">Navigasi</h2>
                    <a href="../index.html" class="footer-link">Beranda</a>
                    <a href="katalog.php" class="footer-link">Katalog Buku</a>
                </div>

                <div class="col-6 col-lg-4 col-md-3">
                    <h2 class="footer-title h6">Informasi</h2>
                    <span class="footer-info"><i class="bi bi-envelope me-2" aria-hidden="true"></i>info@pustakadigital.id</span>
                    <div class="footer-social mt-3">
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                        <a href="#" aria-label="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© Dibuat oleh Kelompok 5 menggunakan Bootstrap.</span>
                <span>Dibuat untuk kebutuhan pembelajaran.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>

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