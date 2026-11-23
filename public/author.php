<?php
// Profil penulis publik via slug + daftar karyanya (0 JOIN: VIEW).
require __DIR__ . '/../includes/book_helper.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$penulis = preg_match('/^[a-z0-9-]{1,120}$/', $slug)
    ? row('SELECT * FROM authors WHERE slug = ?', [$slug])
    : null;

if (!$penulis) {
    http_response_code(404);
}

$karya = $penulis
    ? rows('SELECT * FROM v_books_catalog WHERE author_slug = ? AND is_active = 1 ORDER BY book_id DESC', [$slug])
    : [];
$rak = rak_semua();

$judul = ($penulis ? $penulis['name'] : 'Penulis tidak ditemukan') . ' - PustakaDigital';
$css = ['catalog.css'];
$aktif = 'katalog';
$user = akun();
$shell = $user !== null;
require __DIR__ . '/../includes/partials/head.php';

if ($shell) {
    $admin = $user['role'] === 'admin';
    require __DIR__ . '/../includes/partials/sidebar.php';
    echo '<div class="main-with-sidebar">';
    $judulTopbar = 'Profil Penulis';
    $subTopbar = 'Bio dan karya di PustakaDigital.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
} else {
    require __DIR__ . '/../includes/partials/navbar.php';
}
?>

<main class="container py-4 py-lg-5">
    <?php if ($shell): ?>
        <a href="<?= url('catalog.php') ?>" class="btn btn-ungu-outline mb-3">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke Katalog
        </a>
    <?php endif; ?>
    <?php tampilkan_flash(); ?>
    <?php if (!$penulis): ?>
        <div class="card-clean p-5 text-center">
            <i class="bi bi-search" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
            <h1 class="page-title mt-3">Penulis tidak ditemukan</h1>
            <p class="text-secondary">Penulis yang kamu cari tidak ada di katalog.</p>
            <a href="<?= url('catalog.php') ?>" class="btn btn-ungu mt-2">Kembali ke Katalog</a>
        </div>
    <?php else: ?>
        <div class="card-clean p-4 p-md-5 mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar" style="width:64px;height:64px;flex:0 0 64px;font-size:1.5rem" aria-hidden="true">
                    <?= e(mb_strtoupper(mb_substr($penulis['name'], 0, 1))) ?>
                </span>
                <div>
                    <h1 class="page-title page-title-sm mb-1"><?= e($penulis['name']) ?></h1>
                    <span class="badge bg-ungu-muda text-ungu">
                        <i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= e($penulis['country'] ?: '-') ?>
                    </span>
                </div>
            </div>
            <p class="text-secondary lh-lg mt-3 mb-0">
                <?= e($penulis['bio'] ?: 'Belum ada bio untuk penulis ini.') ?>
            </p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Karya di PustakaDigital</h2>
            <span class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"><?= count($karya) ?> Buku</span>
        </div>
        <div class="row g-4 grid-buku">
            <?php foreach ($karya as $b): ?>
                <div class="col-6 col-lg-3 book-item">
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="<?= url(e($b['cover_path'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
                        </div>
                        <div class="p-3">
                            <div class="badges">
                                <span class="badge bg-ungu-muda text-ungu"><?= e($b['genre_name']) ?></span>
                            </div>
                            <h3 class="h5 fw-bold mt-2 mb-1" title="<?= e($b['title']) ?>"><?= e($b['title']) ?></h3>
                            <p class="text-secondary small mb-1"><?= e($b['published_year'] ?: '-') ?></p>
                            <div class="d-grid gap-2">
                                <a href="<?= url('book-detail.php?slug=' . urlencode($b['slug'])) ?>" class="btn btn-ungu">Lihat Detail</a>
                                <?= dropdown_status((int) $b['book_id'], $rak[(int) $b['book_id']] ?? null) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$karya): ?>
            <p class="text-secondary">Belum ada karya penulis ini di katalog.</p>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php if ($shell): ?>
    </div>
<?php endif; ?>

<?php if (!$shell): ?>
    <?php require __DIR__ . '/../includes/partials/footer.php'; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
