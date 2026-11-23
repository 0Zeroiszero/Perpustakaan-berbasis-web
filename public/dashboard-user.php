<?php
// Dashboard user: statistik COUNT dari collections/reading_progress (bukan dummy).
require __DIR__ . '/../includes/book_helper.php';
wajib_login();
header('Cache-Control: no-store');

if (akun()['role'] === 'admin') {
    header('Location: ' . url('dashboard-admin.php'));
    exit;
}

$user_id = (int) akun()['id'];
$jumlah_rak = (int) scalar('SELECT COUNT(*) FROM collections WHERE user_id = ?', [$user_id]);
$sedang = (int) scalar("SELECT COUNT(*) FROM collections WHERE user_id = ? AND status = 'reading'", [$user_id]);
$selesai = (int) scalar("SELECT COUNT(*) FROM collections WHERE user_id = ? AND status = 'finished'", [$user_id]);
$total_buku = (int) scalar('SELECT COUNT(*) FROM books WHERE is_active = 1');

$sedang_dibaca = rows(
    "SELECT v.title, v.cover_path, v.author_name, v.page_count, rp.last_page
     FROM collections c
     JOIN v_books_catalog v ON v.book_id = c.book_id
     LEFT JOIN reading_progress rp ON rp.user_id = c.user_id AND rp.book_id = c.book_id
     WHERE c.user_id = ? AND c.status = 'reading'
     ORDER BY c.created_at DESC LIMIT 3",
    [$user_id]
);
$aktivitas = rows(
    'SELECT c.status, c.created_at, v.title
     FROM collections c JOIN v_books_catalog v ON v.book_id = c.book_id
     WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 3',
    [$user_id]
);

$judul = 'Dashboard User - PustakaDigital';
$css = ['dashboard-user.css'];
$aktif = 'dashboard';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Dashboard User';
    $subTopbar = 'Selamat datang kembali, ' . akun()['nama'] . '.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
    ?>
    <main class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <h1 class="page-title page-title-sm">Ringkasan Aktivitas</h1>
            <p class="text-secondary">Kelola bacaan dan koleksi pribadi kamu.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Rak Saya</small>
                            <div class="stat-number" id="statRakSaya"><?= $jumlah_rak ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-bookmark" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Sedang Dibaca</small>
                            <div class="stat-number" id="statSedangDibaca"><?= $sedang ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-book-half" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-secondary">Selesai Dibaca</small>
                            <div class="stat-number" id="statSelesaiDibaca"><?= $selesai ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi bi-check-circle" aria-hidden="true"></i></div>
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
                        <div class="stat-icon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Sedang Dibaca</h2>
                        <a href="<?= url('catalog.php') ?>" class="text-ungu">Lihat Katalog</a>
                    </div>
                    <div id="sedangDibacaList">
                        <?php foreach ($sedang_dibaca as $b):
                            $progres = (int) $b['page_count'] > 0
                                ? max(1, min(99, (int) round(((int) $b['last_page'] / (int) $b['page_count']) * 100)))
                                : 0;
                        ?>
                            <div class="d-flex align-items-center gap-3 border-bottom py-3 sedang-dibaca">
                                <div class="book-cover thumb rounded-3">
                                    <img src="<?= url(e($b['cover_path'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
                                </div>
                                <div class="flex-grow-1">
                                    <b><?= e($b['title']) ?></b>
                                    <div class="small text-secondary"><?= e($b['author_name']) ?></div>
                                    <div class="progress mt-2" style="height: 7px;">
                                        <div class="progress-bar" style="width: <?= $progres ?>%; background: var(--ungu);"></div>
                                    </div>
                                </div>
                                <span class="small fw-semibold"><?= $progres ?>%</span>
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
                    <h2 class="h5 fw-bold">Aktivitas Terbaru</h2>
                    <?php foreach ($aktivitas as $i => $b): ?>
                        <div class="py-3 <?= $i < 2 ? 'border-bottom' : '' ?>">
                            <b><?= $b['status'] === 'finished' ? 'Menyelesaikan bacaan' : 'Membaca buku' ?></b>
                            <div class="small text-secondary">
                                <?= e($b['title']) ?> · <?= e(date('d/m/Y', strtotime($b['created_at']))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$aktivitas): ?>
                        <p class="text-secondary py-3 mb-0">Belum ada aktivitas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
