<?php
require __DIR__ . '/_auth.php';
wajib_admin();

$total_buku = (int) scalar("SELECT COUNT(*) FROM ebook");
$total_pengguna = (int) scalar("SELECT COUNT(*) FROM users");
$total_admin = (int) scalar("SELECT COUNT(*) FROM users WHERE user_role = 'Admin'");
$sedang_dibaca = (int) scalar("SELECT COUNT(*) FROM reading_progress WHERE status_baca = 'Sedang dibaca'");
$total_kategori = (int) scalar("SELECT COUNT(*) FROM kategori");

$buku_terbaru = rows(
    "SELECT e.id_book AS id, e.judul, e.format_file AS format, k.nama_kategori AS kategori,
            GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
     FROM ebook e
     JOIN kategori k ON k.id_kategori = e.kategori_id_kategori
     LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
     LEFT JOIN author a ON a.id_author = ea.author_id_author
     GROUP BY e.id_book, e.judul, e.format_file, k.nama_kategori
     ORDER BY e.id_book DESC LIMIT 5"
);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='64' height='64' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='18' fill='%237C3AED'/%3E%3Ctext x='50%25' y='52%25' dominant-baseline='middle' text-anchor='middle' font-family='Space Grotesk, sans-serif' font-weight='700' font-size='32' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/dashboard-admin.css">
</head>

<body>
    <aside id="sidebar" class="sidebar">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="../index.html"><span
                class="logo">P</span>PustakaDigital</a>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard-admin.php" aria-current="page"><i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard</a>
            <a class="nav-link" href="katalog.php"><i class="bi bi-book me-2" aria-hidden="true"></i>Katalog</a>
            <a class="nav-link" href="kelola-buku.php"><i class="bi bi-collection me-2" aria-hidden="true"></i>Kelola Buku</a>
            <a class="nav-link" href="kelola-pengguna.php"><i class="bi bi-people me-2" aria-hidden="true"></i>Kelola Pengguna</a>
            <a class="nav-link" href="../index.html"><i class="bi bi-house me-2" aria-hidden="true"></i>Beranda</a>
            <a class="nav-link logout" href="logout.php"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar</a>
        </nav>
    </aside>

    <div class="main-with-sidebar">
        <header class="topbar p-3">
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu"><i
                        class="bi bi-list" aria-hidden="true"></i></button>
                <div><b>Dashboard Admin</b>
                    <div class="small text-secondary">Selamat datang, <?= e(akun()['nama']) ?>.</div>
                </div>
                <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
            </div>
        </header>

        <main class="container-fluid p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h1 class="page-title page-title-sm">Dashboard Admin</h1>
                    <p class="text-secondary mb-0">Pantau seluruh aktivitas PustakaDigital.</p>
                </div>
                <a href="kelola-buku.php" class="btn btn-ungu">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Kelola Buku
                </a>
            </div>

            <!-- STAT CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between align-items-center mb-2"><span
                                class="text-secondary">Total Buku</span><span class="stat-icon"><i class="bi bi-book" aria-hidden="true"></i></span></div>
                        <div class="stat-number"><?= $total_buku ?></div>
                        <div class="small text-secondary">Koleksi e-book seluruhnya</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between align-items-center mb-2"><span
                                class="text-secondary">Total Pengguna</span><span class="stat-icon"><i class="bi bi-people" aria-hidden="true"></i></span></div>
                        <div class="stat-number"><?= $total_pengguna ?></div>
                        <div class="small text-secondary"><?= $total_admin ?> akun Admin</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between align-items-center mb-2"><span
                                class="text-secondary">Sedang Dibaca</span><span class="stat-icon"><i class="bi bi-book-half" aria-hidden="true"></i></span></div>
                        <div class="stat-number"><?= $sedang_dibaca ?></div>
                        <div class="small text-secondary">Buku di rak pengguna</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between align-items-center mb-2"><span
                                class="text-secondary">Kategori</span><span class="stat-icon"><i class="bi bi-tags" aria-hidden="true"></i></span></div>
                        <div class="stat-number"><?= $total_kategori ?></div>
                        <div class="small text-secondary">Genre tersedia</div>
                    </div>
                </div>
            </div>

            <!-- DATA BUKU TERBARU -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="table-card">
                        <h2 class="h5 fw-bold mb-1">Data Buku Terbaru</h2>
                        <small class="text-secondary">Buku yang baru ditambahkan ke sistem.</small>
                        <div class="table-responsive mt-3">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Judul</th>
                                        <th scope="col">Kategori</th>
                                        <th scope="col">Format</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buku_terbaru as $b): ?>
                                        <tr>
                                            <td>
                                                <b><?= e($b['judul']) ?></b>
                                                <div class="small text-secondary"><?= e($b['penulis']) ?></div>
                                            </td>
                                            <td><?= e($b['kategori']) ?></td>
                                            <td><?= e($b['format']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="katalog.php" class="btn btn-light mt-3">Lihat Katalog</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
