<?php
// Dashboard admin: statistik COUNT dari tabel (tanpa grafik, sesuai kontrak).
require __DIR__ . '/../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

$total_buku = (int) scalar('SELECT COUNT(*) FROM books');
$buku_aktif = (int) scalar('SELECT COUNT(*) FROM books WHERE is_active = 1');
$total_pengguna = (int) scalar('SELECT COUNT(*) FROM users');
$total_penerbit = (int) scalar('SELECT COUNT(*) FROM publishers');
$total_penulis = (int) scalar('SELECT COUNT(*) FROM authors');
$terbaru = rows('SELECT * FROM v_books_catalog ORDER BY book_id DESC LIMIT 5');

$judul = 'Dashboard Admin - PustakaDigital';
$css = ['dashboard-admin.css'];
$aktif = 'dashboard';
$admin = true;
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Dashboard Admin';
    $subTopbar = 'Selamat datang kembali, ' . akun()['nama'] . '.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
    ?>
    <main class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <h1 class="page-title page-title-sm">Ringkasan Sistem</h1>
            <p class="text-secondary">Pantau koleksi dan akun PustakaDigital.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Total Buku</small>
                            <div class="stat-number"><?= $total_buku ?></div>
                            <small class="text-secondary"><?= $buku_aktif ?> aktif</small>
                        </div>
                        <div class="stat-icon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Total Pengguna</small>
                            <div class="stat-number"><?= $total_pengguna ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Penerbit</small>
                            <div class="stat-number"><?= $total_penerbit ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-building" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Penulis</small>
                            <div class="stat-number"><?= $total_penulis ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-pen" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Buku Terbaru</h2>
                <a href="<?= url('admin/books.php') ?>" class="text-ungu">Kelola Buku</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Judul</th>
                            <th scope="col">Penulis</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terbaru as $b): ?>
                            <tr>
                                <td><b><?= e($b['title']) ?></b></td>
                                <td><?= e($b['author_name']) ?></td>
                                <td><?= e($b['genre_name']) ?></td>
                                <td>
                                    <?php if ((int) $b['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
