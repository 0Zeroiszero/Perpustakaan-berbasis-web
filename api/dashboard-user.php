<?php
require __DIR__ . '/_auth.php';
wajib_login();
if (akun()['role'] === 'Admin') {
    header('Location: dashboard-admin.php');
    exit;
}

$user_id = (string) akun()['id'];
$rak = rows(
    "SELECT e.id_book AS id, e.judul,
            e.cover AS cover_path, e.file_book AS file_path,
            k.nama_kategori AS kategori,
            r.progress_percent AS progres, r.last_read AS updated_at, r.status_baca AS status,
            GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
     FROM reading_progress r
     JOIN ebook e ON e.id_book = r.book_id_book
     JOIN kategori k ON k.id_kategori = e.kategori_id_kategori
     LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
     LEFT JOIN author a ON a.id_author = ea.author_id_author
     WHERE r.user_id_user = ?
     GROUP BY e.id_book, e.judul, e.cover, e.file_book, k.nama_kategori, r.progress_percent, r.last_read, r.status_baca
     ORDER BY r.last_read DESC, e.id_book DESC",
    [$user_id]
);
$jumlah_rak = count($rak);
$sedang_dibaca = array_values(array_filter($rak, fn($b) => $b['status'] === 'Sedang dibaca'));
$selesai_dibaca = count(array_filter($rak, fn($b) => $b['status'] === 'Telah dibaca'));
$total_buku = (int) scalar("SELECT COUNT(*) FROM ebook");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/dashboard-user.css">
</head>

<body>
    <aside id="sidebar" class="sidebar">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="../index.html">
            <span class="logo">P</span>
            PustakaDigital
        </a>
        <nav class="nav flex-column">
            <a class="nav-link active" href="<?= dashboard_url() ?>" aria-current="page">
                <i class="bi bi-grid me-2" aria-hidden="true"></i>
                Dashboard
            </a>
            <a class="nav-link" href="katalog.php">
                <i class="bi bi-book me-2" aria-hidden="true"></i>
                Katalog
            </a>
            <a class="nav-link" href="rak-saya.php">
                <i class="bi bi-bookmark me-2" aria-hidden="true"></i>
                Rak Saya
            </a>
            <a class="nav-link" href="../index.html">
                <i class="bi bi-house me-2" aria-hidden="true"></i>
                Beranda
            </a>
            <a class="nav-link logout" href="logout.php">
                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>
                Keluar
            </a>
        </nav>
    </aside>
    <div class="main-with-sidebar">
        <header class="topbar p-3">
            <div class="d-flex align-items-center gap-3">
                <button id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div>
                    <b>
                        Dashboard User
                    </b>
                    <div class="small text-secondary">
                        Selamat datang kembali, <?= e(akun()['nama']) ?>.
                    </div>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-ungu-muda text-ungu">
                        User
                    </span>
                </div>
            </div>
        </header>
        <main class="container-fluid p-3 p-lg-4">
            <div class="mb-4">
                <h1 class="page-title page-title-sm">
                    Ringkasan Aktivitas
                </h1>
                <p class="text-secondary">
                    Kelola bacaan dan koleksi pribadi kamu.
                </p>
            </div>
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-secondary">Rak Saya</small>
                                <div class="stat-number" id="statRakSaya"><?= $jumlah_rak ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-bookmark" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-secondary">Sedang Dibaca</small>
                                <div class="stat-number" id="statSedangDibaca"><?= count($sedang_dibaca) ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-book-half" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-secondary">Selesai Dibaca</small>
                                <div class="stat-number" id="statSelesaiDibaca"><?= $selesai_dibaca ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat">
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-secondary">Total Koleksi</small>
                                <div class="stat-number"><?= $total_buku ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="table-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 fw-bold mb-0">
                                Sedang Dibaca
                            </h2>
                            <a href="katalog.php" class="text-ungu">
                                Lihat Katalog
                            </a>
                        </div>
                        <div id="sedangDibacaList">
                            <?php foreach (array_slice($sedang_dibaca, 0, 3) as $b): ?>
                                <div class="d-flex align-items-center gap-3 border-bottom py-3 sedang-dibaca">
                                    <div class="book-cover thumb rounded-3">
                                        <?php if ($b['cover_path']): ?>
                                            <img src="../<?= e($b['cover_path']) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="bi bi-book text-ungu" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <b><?= e($b['judul']) ?></b>
                                        <div class="small text-secondary">
                                            <?= e($b['penulis']) ?>
                                        </div>
                                        <div class="progress mt-2" style="height: 7px;">
                                            <div class="progress-bar" style="width: <?= (int) $b['progres'] ?>%; background: var(--ungu);"></div>
                                        </div>
                                    </div>
                                    <span class="small fw-semibold">
                                        <?= (int) $b['progres'] ?>%
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$sedang_dibaca): ?>
                                <p class="text-secondary py-3 mb-0">Belum ada buku yang sedang dibaca. Simpan buku dari katalog untuk mulai.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="table-card h-100">
                        <h2 class="h5 fw-bold">
                            Aktivitas Terbaru
                        </h2>
                        <?php foreach (array_slice($rak, 0, 3) as $i => $b): ?>
                            <div class="py-3 <?= $i < 2 ? 'border-bottom' : '' ?>">
                                <b><?= $b['progres'] >= 100 ? 'Menyelesaikan bacaan' : 'Membaca buku' ?></b>
                                <div class="small text-secondary">
                                    <?= e($b['judul']) ?> · <?= e($b['updated_at'] ? date('d/m/Y', strtotime($b['updated_at'])) : '-') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$rak): ?>
                            <p class="text-secondary py-3 mb-0">Belum ada aktivitas.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>