<?php
// Katalog publik: kartu dari VIEW (0 JOIN), cari/filter/urut di script.js (kontrak lama).
require __DIR__ . '/../includes/book_helper.php';

$buku = rows('SELECT * FROM v_books_catalog WHERE is_active = 1 ORDER BY book_id DESC');
$genre_list = rows('SELECT name FROM genres ORDER BY name');
$rak = rak_semua();

$judul = 'Katalog - PustakaDigital';
$css = ['catalog.css'];
$aktif = 'katalog';
$cari = true;
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<main class="container py-5">
    <!-- HEADER -->
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

    <!-- GRID BUKU: dirender dari v_books_catalog, bukan kartu statis -->
    <div class="row g-4 grid-buku" id="bookGrid">
        <?php foreach ($buku as $b):
            $badgeKategori = match ($b['genre_name']) {
                'Teknologi' => 'bg-biru-muda text-biru',
                'Novel' => 'bg-danger-subtle text-danger',
                default => 'bg-ungu-muda text-ungu',
            };
            $status = $rak[(int) $b['book_id']] ?? null;
        ?>
            <div class="col-6 col-lg-3 book-item"
                data-search="<?= e(strtolower($b['title'] . ' ' . $b['author_name'] . ' ' . $b['genre_name'])) ?>"
                data-kategori="<?= e($b['genre_name']) ?>" data-judul="<?= e($b['title']) ?>">
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
                        <h3 class="h5 fw-bold mt-2 mb-1"><?= e($b['title']) ?></h3>
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
            <button type="button" class="btn btn-ungu mt-2"
                onclick="filterKategori('Semua', document.querySelector('#modalCari .category-btn'))">Reset Filter</button>
        </div>
    </div>
</main>

<!-- SEARCH + FILTER GABUNGAN (modal, dibuka dari ikon cari di navbar) -->
<div class="modal fade" id="modalCari" tabindex="-1" aria-labelledby="modalCariJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 fw-bold" id="modalCariJudul">Cari &amp; filter buku</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12 col-md">
                        <label class="visually-hidden" for="searchInput">Cari judul atau penulis</label>
                        <input id="searchInput" type="search" class="form-control"
                            placeholder="Cari judul, penulis... (misal: Laskar, Pramoedya)">
                    </div>
                    <div class="col-12 col-md-auto">
                        <label class="visually-hidden" for="sortSelect">Urutkan koleksi</label>
                        <select id="sortSelect" class="form-select" onchange="sortBuku()">
                            <option value="terbaru">Terbaru</option>
                            <option value="az">Judul A-Z</option>
                        </select>
                    </div>
                </div>
                <p class="small text-secondary mb-0 mt-2">
                    Tekan Enter di kolom pencarian untuk mulai mencari.
                </p>
                <!-- CATEGORY BUTTON -->
                <div class="mt-3 d-flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                    <button type="button" class="category-btn active" aria-pressed="true" data-nama="Semua">Semua</button>
                    <?php foreach ($genre_list as $g): ?>
                        <button type="button" class="category-btn" aria-pressed="false"
                            data-nama="<?= e($g['name']) ?>"><?= e($g['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
