<?php
// Katalog: pencarian, filter, dan urutan sepenuhnya server-side (tanpa JS filter).
require __DIR__ . '/../includes/book_helper.php';

$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$kategori = trim(is_string($_GET['kategori'] ?? null) ? $_GET['kategori'] : '');
$sort = ($_GET['sort'] ?? '') === 'az' ? 'az' : 'terbaru';

$sql = 'SELECT * FROM v_books_catalog WHERE is_active = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (title LIKE ? OR author_name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($kategori !== '') {
    $sql .= ' AND genre_name = ?';
    $params[] = $kategori;
}
$sql .= $sort === 'az' ? ' ORDER BY title ASC' : ' ORDER BY book_id DESC';
$buku = rows($sql, $params);
$genre_list = rows('SELECT name FROM genres ORDER BY name');
$rak = rak_semua();

$user = akun();
$shell = $user !== null;

$judul = 'Katalog - PustakaDigital';
$css = ['catalog.css'];
$aktif = 'katalog';
$cari = true;
require __DIR__ . '/../includes/partials/head.php';

if ($shell) {
    $admin = $user['role'] === 'admin';
    require __DIR__ . '/../includes/partials/sidebar.php';
    echo '<div class="main-with-sidebar">';
    $judulTopbar = 'Katalog';
    $subTopbar = 'Cari, filter, lalu simpan dan baca koleksinya.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
    echo '<main class="container-fluid p-3 p-lg-4">';
} else {
    require __DIR__ . '/../includes/partials/navbar.php';
    echo '<main class="container py-5">';
}
?>

<div class="mb-4">
    <h1 class="page-title mb-1">Temukan Buku Favoritmu</h1>
    <p class="text-secondary mb-0">Cari, filter, lalu simpan dan baca koleksinya secara digital.</p>
</div>

<?php tampilkan_flash(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 fw-bold mb-0">Koleksi Buku</h2>
    <span id="jumlahBuku" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2" aria-live="polite"><?= count($buku) ?>
        Buku</span>
</div>

<!-- GRID BUKU: hasil query PHP, bukan filter JS -->
<div class="row g-4 grid-buku" id="bookGrid">
    <?php foreach ($buku as $b):
        $badgeKategori = match ($b['genre_name']) {
            'Teknologi' => 'bg-biru-muda text-biru',
            'Novel' => 'bg-danger-subtle text-danger',
            default => 'bg-ungu-muda text-ungu',
        };
        $status = $rak[(int) $b['book_id']] ?? null;
    ?>
        <div class="col-6 col-lg-3 book-item">
            <div class="book-card">
                <div class="book-cover">
                    <?php if ($b['cover_path']): ?>
                        <img src="<?= url(e($b['cover_path'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <i class="bi bi-book" style="font-size:3rem;color:var(--ungu)" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <div class="badges">
                        <span class="badge <?= $badgeKategori ?>"><?= e($b['genre_name']) ?></span>
                    </div>
                    <h3 class="h5 fw-bold mt-2 mb-1" title="<?= e($b['title']) ?>"><?= e($b['title']) ?></h3>
                    <p class="text-secondary small mb-1"><?= e($b['author_name']) ?></p>
                    <div class="d-grid gap-2">
                        <a href="<?= url('book-detail.php?slug=' . urlencode($b['slug'])) ?>" class="btn btn-ungu">Lihat Detail</a>
                        <?= dropdown_status((int) $b['book_id'], $status) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="tidakDitemukan" class="text-center py-5 <?= $buku ? 'd-none' : '' ?>" role="status">
    <div class="card-clean p-5">
        <i class="bi bi-search" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
        <h2 class="h5 mt-3"><?= $buku ? 'Buku tidak ditemukan' : 'Belum ada buku' ?></h2>
        <p class="text-secondary"><?= $buku ? 'Coba kata kunci lain atau pilih kategori Semua' : 'Admin belum menambahkan koleksi.' ?></p>
        <a href="<?= url('catalog.php') ?>" class="btn btn-ungu mt-2">Reset Filter</a>
    </div>
</div>
</main>
<?php if ($shell): ?>
    </div>
<?php endif; ?>

<!-- SEARCH + FILTER: form GET, diproses PHP. Tanpa JS filter. -->
<div class="modal fade" id="modalCari" tabindex="-1" aria-labelledby="modalCariJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <form method="get" action="<?= url('catalog.php') ?>">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h5 fw-bold" id="modalCariJudul">Cari &amp; filter buku</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold" for="searchInput">Cari judul atau penulis</label>
                            <input id="searchInput" name="q" type="search" class="form-control" value="<?= e($q) ?>"
                                placeholder="Cari judul, penulis... (misal: Laskar, Pramoedya)">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="sortSelect">Urutkan</label>
                            <select id="sortSelect" name="sort" class="form-select">
                                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                                <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>Judul A-Z</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                        <button type="submit" name="kategori" value=""
                            class="category-btn <?= $kategori === '' ? 'active' : '' ?>"
                            aria-pressed="<?= $kategori === '' ? 'true' : 'false' ?>">Semua</button>
                        <?php foreach ($genre_list as $g): ?>
                            <button type="submit" name="kategori" value="<?= e($g['name']) ?>"
                                class="category-btn <?= $kategori === $g['name'] ? 'active' : '' ?>"
                                aria-pressed="<?= $kategori === $g['name'] ? 'true' : 'false' ?>"><?= e($g['name']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-ungu w-100">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$shell): ?>
    <?php require __DIR__ . '/../includes/partials/footer.php'; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
