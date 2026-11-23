<?php
// Detail buku via slug (0 JOIN: SELECT dari v_books_catalog).
require __DIR__ . '/../includes/book_helper.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$buku = preg_match('/^[a-z0-9-]{1,220}$/', $slug)
    ? row('SELECT * FROM v_books_catalog WHERE slug = ? AND is_active = 1', [$slug])
    : null;

if (!$buku) {
    http_response_code(404);
}

$status_rak = $buku ? rak_status((int) $buku['book_id']) : null;
$user = akun();
$shell = $user !== null;

$judul = ($buku ? $buku['title'] : 'Buku tidak ditemukan') . ' - PustakaDigital';
$css = ['book-detail.css'];
$aktif = 'katalog';
require __DIR__ . '/../includes/partials/head.php';

if ($shell) {
    $admin = $user['role'] === 'admin';
    require __DIR__ . '/../includes/partials/sidebar.php';
    echo '<div class="main-with-sidebar">';
    $judulTopbar = 'Detail Buku';
    $subTopbar = 'Informasi koleksi dan aksi baca.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
} else {
    ?>
    <nav class="navbar navbar-expand-lg border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
                <span class="logo">P</span> PustakaDigital
            </a>
            <a href="<?= url('catalog.php') ?>" class="btn btn-ungu-outline btn-kembali ms-auto" aria-label="Kembali ke Katalog"
                title="Kembali ke Katalog">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>
    </nav>
    <?php
}
?>

<main class="container py-4 py-lg-5">
    <?php if ($shell): ?>
        <a href="<?= url('catalog.php') ?>" class="btn btn-ungu-outline mb-3">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali ke Katalog
        </a>
    <?php endif; ?>
    <?php tampilkan_flash(); ?>
    <?php if (!$buku): ?>
        <div class="card-clean p-5 text-center">
            <i class="bi bi-search" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
            <h1 class="page-title mt-3">Buku tidak ditemukan</h1>
            <p class="text-secondary">Buku yang kamu cari mungkin sudah dinonaktifkan dari katalog.</p>
            <a href="<?= url('catalog.php') ?>" class="btn btn-ungu mt-2">Kembali ke Katalog</a>
        </div>
    <?php else: ?>
        <?php
        $badgeKategori = match ($buku['genre_name']) {
            'Teknologi' => 'bg-biru-muda text-biru',
            'Novel' => 'bg-danger-subtle text-danger',
            default => 'bg-ungu-muda text-ungu',
        };
        ?>
        <div class="row gy-5 gx-lg-5 align-items-start">
            <div class="col-md-5">
                <div class="detail-cover">
                    <?php if ($buku['cover_path']): ?>
                        <img src="<?= url(e($buku['cover_path'])) ?>" alt="<?= e($buku['title']) ?>">
                    <?php else: ?>
                        <i class="bi bi-book" style="font-size:8rem" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-7">
                <span class="badge <?= $badgeKategori ?>">
                    <?= e($buku['genre_name']) ?>
                </span>
                <h1 class="page-title mt-3">
                    <?= e($buku['title']) ?>
                </h1>
                <p class="text-secondary fs-5">
                    <a href="<?= url('author.php?slug=' . urlencode($buku['author_slug'])) ?>" class="text-secondary">
                        <?= e($buku['author_name']) ?>
                    </a>
                </p>
                <p class="text-secondary lh-lg">
                    <?= e($buku['description'] ?: 'Belum ada sinopsis untuk buku ini.') ?>
                </p>
                <div class="d-grid gap-2 d-sm-flex mt-4">
                    <!-- Baca online: gerbang login terjadi di read.php (?next= kembali ke buku ini) -->
                    <a href="<?= url('read.php?slug=' . urlencode($buku['slug'])) ?>" class="btn btn-baca px-4">
                        <i class="bi bi-book-half me-1" aria-hidden="true"></i>
                        Baca Buku
                    </a>
                    <?= dropdown_status((int) $buku['book_id'], $status_rak, 'px-4 w-100', '') ?>
                </div>
            </div>
        </div>
        <!-- INFO: kolom dari skema final (judul & kategori sudah tampil di atas) -->
        <section class="mt-4 mt-md-5">
            <div class="card-clean p-4">
                <h2 class="h4 fw-bold mb-3">
                    Informasi Buku
                </h2>
                <dl class="row row-cols-2 row-cols-md-3 g-3 mb-0">
                    <div class="col">
                        <dt class="info-label">Penulis</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['author_name']) ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">Kategori</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['genre_name']) ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">Penerbit</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['publisher_name'] ?: '-') ?><?php if ($buku['publisher_city']): ?><span
                                    class="d-block small text-secondary fw-normal"><?= e($buku['publisher_city']) ?></span><?php endif; ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">Tahun</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['published_year'] ?: '-') ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">Halaman</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['page_count'] ?: '-') ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">ISBN</dt>
                        <dd class="mb-0 fw-semibold"><?= e($buku['isbn'] ?: '-') ?></dd>
                    </div>
                    <div class="col">
                        <dt class="info-label">Format</dt>
                        <dd class="mb-0 fw-semibold">PDF</dd>
                    </div>
                </dl>
                <p class="text-secondary mt-4 mb-0">
                    Buku tersedia dalam koleksi digital PustakaDigital.
                    Pengguna dapat menyimpan buku ke Rak Saya dan
                    membacanya langsung di peramban.
                </p>
            </div>
        </section>
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
