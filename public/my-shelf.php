<?php
// Rak Saya: tab 4 status server-side; kartu + CTA sesuai kontrak UI lama.
require __DIR__ . '/../includes/book_helper.php';
wajib_login();
header('Cache-Control: no-store');

$status = (string) ($_GET['status'] ?? '');
if (!isset(STATUS_LABEL[$status])) {
    $status = '';
}

$sql = 'SELECT c.status, c.created_at, v.*, rp.last_page
        FROM collections c
        JOIN v_books_catalog v ON v.book_id = c.book_id
        LEFT JOIN reading_progress rp ON rp.user_id = c.user_id AND rp.book_id = c.book_id
        WHERE c.user_id = ?';
$params = [akun()['id']];
if ($status !== '') {
    $sql .= ' AND c.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY c.created_at DESC, v.book_id DESC';
$rak = rows($sql, $params);

$genre_list = rows('SELECT name FROM genres ORDER BY name');

$judul = 'Rak Saya - PustakaDigital';
$css = ['my-shelf.css'];
$aktif = 'rak';
$admin = akun()['role'] === 'admin';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <header class="topbar p-3">
        <div class="d-flex align-items-center gap-3">
            <button id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div>
                <b>Rak Saya</b>
                <div class="small text-secondary">
                    Buku yang sudah kamu simpan.
                </div>
            </div>
            <div class="ms-auto">
                <span class="badge bg-ungu-muda text-ungu"><?= e(ucfirst(akun()['role'])) ?></span>
            </div>
        </div>
    </header>
    <main class="container-fluid p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title page-title-sm">Rak Saya</h1>
                <p class="text-secondary mb-0">Koleksi buku pribadi yang sudah kamu tambahkan.</p>
            </div>
            <span id="jumlahRak" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2" aria-live="polite"><?= count($rak) ?>
                Buku</span>
        </div>

        <?php tampilkan_flash(); ?>

        <!-- TAB STATUS: filter server-side lewat ?status= -->
        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filter status rak">
            <a class="category-btn <?= $status === '' ? 'active' : '' ?>" href="<?= url('my-shelf.php') ?>"
                <?= $status === '' ? 'aria-current="page"' : '' ?>>Semua status</a>
            <?php foreach (STATUS_LABEL as $nilai => $label): ?>
                <a class="category-btn <?= $status === $nilai ? 'active' : '' ?>"
                    href="<?= url('my-shelf.php?status=' . $nilai) ?>"
                    <?= $status === $nilai ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- TOOLBAR RAK — UI saja sesuai kontrak lama (belum ada logika JS). -->
        <div class="rak-toolbar mb-3">
            <div class="row g-2">
                <div class="col">
                    <label class="visually-hidden" for="cariRak">Cari buku di rak</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-secondary" aria-hidden="true"></i></span>
                        <input id="cariRak" type="search" class="form-control border-start-0"
                            placeholder="Cari di rak">
                    </div>
                </div>
                <div class="col-auto d-none d-md-block">
                    <label class="visually-hidden" for="urutRak">Urutkan buku</label>
                    <select id="urutRak" class="form-select">
                        <option value="terbaru">Terbaru</option>
                        <option value="az">Judul A-Z</option>
                        <option value="progres">Progres Baca</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-light border h-100 px-3" data-bs-toggle="modal"
                        data-bs-target="#modalFilterRak" aria-label="Urutkan dan filter rak">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 grid-buku" id="rakGrid">
            <?php foreach ($rak as $b):
                // Persentase diturunkan saat render dari last_page/page_count (bukan kolom tersimpan).
                $progres = match ($b['status']) {
                    'finished' => 100,
                    'to_read' => 0,
                    default => (int) $b['page_count'] > 0
                        ? max(1, min(99, (int) round(((int) $b['last_page'] / (int) $b['page_count']) * 100)))
                        : 0,
                };
                $selesai = $b['status'] === 'finished';
                $badgeStatus = match ($b['status']) {
                    'finished' => 'bg-success-subtle text-success',
                    'reading' => 'bg-ungu-muda text-ungu',
                    'not_interested' => 'bg-danger-subtle text-danger',
                    default => 'bg-biru-muda text-biru',
                };
                $badgeKategori = match ($b['genre_name']) {
                    'Teknologi' => 'bg-biru-muda text-biru',
                    'Novel' => 'bg-danger-subtle text-danger',
                    default => 'bg-ungu-muda text-ungu',
                };
            ?>
                <div class="col-6 col-lg-3 buku-rak">
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="<?= url(e($b['cover_path'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
                        </div>
                        <div class="p-3">
                            <span class="badge <?= $badgeKategori ?>"><?= e($b['genre_name']) ?></span>
                            <h2 class="h5 fw-bold mt-2 mb-1"><?= e($b['title']) ?></h2>
                            <p class="text-secondary small mb-2"><?= e($b['author_name']) ?></p>
                            <div class="progress mb-1" style="height:7px;">
                                <div class="progress-bar" style="width:<?= $progres ?>%;background:var(--ungu);"></div>
                            </div>
                            <?php if ($selesai): ?>
                                <p class="small mb-3"><span class="badge <?= $badgeStatus ?> d-inline-block">Selesai Dibaca</span></p>
                            <?php else: ?>
                                <p class="small mb-3">
                                    <span class="badge <?= $badgeStatus ?>"><?= e(STATUS_LABEL[$b['status']]) ?></span>
                                    <span class="text-secondary ms-1"><?= $progres ?>%</span>
                                </p>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <?php if ($selesai): ?>
                                    <a href="<?= url('catalog.php') ?>" class="btn btn-ungu"><i class="bi bi-compass me-1" aria-hidden="true"></i>Buku Selanjutnya</a>
                                    <a href="<?= url('read.php?slug=' . urlencode($b['slug'])) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Baca Ulang</a>
                                <?php else: ?>
                                    <a href="<?= url('read.php?slug=' . urlencode($b['slug'])) ?>" class="btn btn-baca"><i class="bi bi-book-half me-1" aria-hidden="true"></i><?= $b['status'] === 'reading' ? 'Lanjut Baca' : 'Baca Buku' ?></a>
                                <?php endif; ?>
                                <form method="post" action="<?= url('actions/collection-delete.php') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="book_id" value="<?= (int) $b['book_id'] ?>">
                                    <button type="button" class="btn btn-light border w-100"
                                        data-judul="<?= e($b['title']) ?>" onclick="hapusRak(this)">
                                        <i class="bi bi-bookmark-x me-1" aria-hidden="true"></i>Hapus dari Rak
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="rakKosong" class="text-center py-5 <?= $rak ? 'd-none' : '' ?>" role="status">
            <div class="card-clean p-5">
                <i class="bi bi-bookmark" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
                <h2 class="h5 mt-3">Rak kamu masih kosong</h2>
                <p class="text-secondary"><?= $status !== '' ? 'Tidak ada buku dengan status ini.' : 'Simpan buku dari katalog untuk mulai membaca.' ?></p>
                <a href="<?= url('catalog.php') ?>" class="btn btn-ungu mt-2">
                    <i class="bi bi-grid me-1" aria-hidden="true"></i>Jelajahi Katalog
                </a>
            </div>
        </div>
    </main>
</div>

<!-- MODAL URUTKAN & FILTER — UI saja; opsi .btn-check berubah tanpa JS (kontrak lama). -->
<div class="modal fade" id="modalFilterRak" tabindex="-1" aria-labelledby="modalFilterRakJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5 fw-bold" id="modalFilterRakJudul">
                    <i class="bi bi-funnel me-2" aria-hidden="true"></i>
                    Urutkan & filter rak
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="info-label mb-2 d-md-none" id="labelUrutRak">Urutkan</p>
                <div class="d-flex flex-wrap gap-2 mb-4 d-md-none" role="group" aria-labelledby="labelUrutRak">
                    <input type="radio" class="btn-check" name="urutRakOpsi" id="urutTerbaru" checked>
                    <label class="category-btn" for="urutTerbaru">Terbaru</label>
                    <input type="radio" class="btn-check" name="urutRakOpsi" id="urutAZ">
                    <label class="category-btn" for="urutAZ">Judul A-Z</label>
                    <input type="radio" class="btn-check" name="urutRakOpsi" id="urutProgres">
                    <label class="category-btn" for="urutProgres">Progres Baca</label>
                </div>
                <p class="info-label mb-2" id="labelStatusRak">Status</p>
                <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-labelledby="labelStatusRak">
                    <input type="radio" class="btn-check" name="statusRak" id="statusSemua" checked>
                    <label class="category-btn" for="statusSemua">Semua status</label>
                    <?php foreach (STATUS_LABEL as $nilai => $label): ?>
                        <input type="radio" class="btn-check" name="statusRak" id="status<?= e(ucfirst($nilai)) ?>">
                        <label class="category-btn" for="status<?= e(ucfirst($nilai)) ?>"><?= e($label) ?></label>
                    <?php endforeach; ?>
                </div>
                <p class="info-label mb-2" id="labelKategoriRak">Kategori</p>
                <div class="d-flex flex-wrap gap-2" role="group" aria-labelledby="labelKategoriRak">
                    <?php foreach ($genre_list as $i => $g): ?>
                        <input type="checkbox" class="btn-check" name="kategoriRak" id="kat<?= (int) $i ?>" checked>
                        <label class="category-btn" for="kat<?= (int) $i ?>"><?= e($g['name']) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-ungu w-100" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/my-shelf.js') ?>"></script>
</body>

</html>
