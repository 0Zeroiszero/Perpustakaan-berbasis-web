<?php
// Rak Saya: tab 4 status server-side; kartu + CTA sesuai kontrak UI lama.
require __DIR__ . '/../includes/book_helper.php';
wajib_login();
header('Cache-Control: no-store');

$status = (string) ($_GET['status'] ?? '');
if (!isset(STATUS_LABEL[$status])) {
    $status = '';
}
$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$sort = in_array($_GET['sort'] ?? '', ['terbaru', 'judul-az', 'progres'], true) ? $_GET['sort'] : 'terbaru';
$raw_kategori = $_GET['kategori'] ?? [];
$raw_kategori = is_array($raw_kategori) ? $raw_kategori : [$raw_kategori];
$kategori = array_values(array_filter(
    $raw_kategori,
    fn($x) => is_string($x) && $x !== ''
));

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
if ($q !== '') {
    $sql .= ' AND (v.title LIKE ? OR v.author_name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($kategori !== []) {
    $sql .= ' AND v.genre_name IN (' . implode(',', array_fill(0, count($kategori), '?')) . ')';
    $params = array_merge($params, $kategori);
}
$sql .= $sort === 'judul-az' ? ' ORDER BY v.title ASC' : ' ORDER BY c.created_at DESC, v.book_id DESC';
$rak = rows($sql, $params);
foreach ($rak as &$b) {
    $b['persen'] = persen_baca($b);
}
unset($b);
if ($sort === 'progres') {
    usort($rak, fn($a, $b) => [$b['persen'], $a['title']] <=> [$a['persen'], $b['title']]);
}

$genre_list = rows('SELECT name FROM genres ORDER BY name');
$kategori_cek = $kategori === [] ? array_column($genre_list, 'name') : $kategori;

// URL tab status tetap membawa pencarian/filter/sort yang aktif.
function rak_url(string $status, string $q, string $sort, array $kategori): string
{
    $p = [];
    if ($status !== '') {
        $p['status'] = $status;
    }
    if ($q !== '') {
        $p['q'] = $q;
    }
    if ($sort !== 'terbaru') {
        $p['sort'] = $sort;
    }
    if ($kategori !== []) {
        $p['kategori'] = $kategori;
    }
    return url('my-shelf.php' . ($p ? '?' . http_build_query($p) : ''));
}

$judul = 'Rak Saya - PustakaDigital';
$css = ['my-shelf.css'];
$aktif = 'rak';
$admin = akun()['role'] === 'admin';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Rak Saya';
    $subTopbar = 'Buku yang sudah kamu simpan.';
    require __DIR__ . '/../includes/partials/app_topbar.php';
    ?>
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

        <!-- TAB STATUS: filter server-side, membawa q/kategori/sort -->
        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filter status rak">
            <a class="category-btn <?= $status === '' ? 'active' : '' ?>" href="<?= rak_url('', $q, $sort, $kategori) ?>"
                <?= $status === '' ? 'aria-current="page"' : '' ?>>Semua status</a>
            <?php foreach (STATUS_LABEL as $nilai => $label): ?>
                <a class="category-btn <?= $status === $nilai ? 'active' : '' ?>"
                    href="<?= rak_url($nilai, $q, $sort, $kategori) ?>"
                    <?= $status === $nilai ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <!-- TOOLBAR RAK: pencarian/urut diproses PHP lewat GET. -->
        <form class="rak-toolbar mb-3" method="get" action="<?= url('my-shelf.php') ?>">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <div class="row g-2">
                <div class="col">
                    <label class="visually-hidden" for="cariRak">Cari buku di rak</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="bi bi-search text-secondary" aria-hidden="true"></i></span>
                        <input id="cariRak" name="q" type="search" class="form-control border-start-0"
                            value="<?= e($q) ?>" placeholder="Cari di rak">
                        <button class="btn btn-ungu" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block">
                    <label class="visually-hidden" for="urutRak">Urutkan buku</label>
                    <select id="urutRak" name="sort" class="form-select">
                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="judul-az" <?= $sort === 'judul-az' ? 'selected' : '' ?>>Judul A-Z</option>
                        <option value="progres" <?= $sort === 'progres' ? 'selected' : '' ?>>Progres Baca</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-light border h-100 px-3" data-bs-toggle="modal"
                        data-bs-target="#modalFilterRak" aria-label="Urutkan dan filter rak">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="row g-4 grid-buku" id="rakGrid">
            <?php foreach ($rak as $b):
                $progres = $b['persen'];
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
                            <h2 class="h5 fw-bold mt-2 mb-1" title="<?= e($b['title']) ?>"><?= e($b['title']) ?></h2>
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
                                    <form method="post" action="<?= url('actions/progress-reset.php') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="book_id" value="<?= (int) $b['book_id'] ?>">
                                        <button type="button" class="btn btn-outline-secondary w-100"
                                            data-judul="<?= e($b['title']) ?>" onclick="bacaUlang(this)">
                                            <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Baca Ulang
                                        </button>
                                    </form>
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
                <h2 class="h5 mt-3">Tidak ada buku yang cocok</h2>
                <p class="text-secondary">
                    <?= ($q !== '' || $kategori !== [] || $status !== '') ? 'Coba kata kunci, kategori, atau status lain.' : 'Rak kamu masih kosong. Simpan buku dari katalog untuk mulai membaca.' ?>
                </p>
                <a href="<?= url('my-shelf.php') ?>" class="btn btn-ungu mt-2">
                    <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset Pencarian
                </a>
            </div>
        </div>
    </main>
</div>

<!-- MODAL URUTKAN & FILTER: form GET, diproses PHP. -->
<div class="modal fade" id="modalFilterRak" tabindex="-1" aria-labelledby="modalFilterRakJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="get" action="<?= url('my-shelf.php') ?>" style="border-radius:var(--radius-lg)">
            <input type="hidden" name="q" value="<?= e($q) ?>">
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
                        <?php foreach (['terbaru' => 'Terbaru', 'judul-az' => 'Judul A-Z', 'progres' => 'Progres Baca'] as $nilai => $label): ?>
                            <input type="radio" class="btn-check" name="sort" id="urut<?= e($nilai) ?>" value="<?= e($nilai) ?>"
                                <?= $sort === $nilai ? 'checked' : '' ?>>
                            <label class="category-btn" for="urut<?= e($nilai) ?>"><?= e($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="info-label mb-2" id="labelStatusRak">Status</p>
                    <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-labelledby="labelStatusRak">
                        <input type="radio" class="btn-check" name="status" id="statusSemua" value=""
                            <?= $status === '' ? 'checked' : '' ?>>
                        <label class="category-btn" for="statusSemua">Semua status</label>
                        <?php foreach (STATUS_LABEL as $nilai => $label): ?>
                            <input type="radio" class="btn-check" name="status" id="status<?= e(ucfirst($nilai)) ?>"
                                value="<?= e($nilai) ?>" <?= $status === $nilai ? 'checked' : '' ?>>
                            <label class="category-btn" for="status<?= e(ucfirst($nilai)) ?>"><?= e($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="info-label mb-2" id="labelKategoriRak">Kategori</p>
                    <div class="d-flex flex-wrap gap-2" role="group" aria-labelledby="labelKategoriRak">
                        <?php foreach ($genre_list as $i => $g): ?>
                            <input type="checkbox" class="btn-check" name="kategori[]" id="kat<?= (int) $i ?>"
                                value="<?= e($g['name']) ?>" <?= in_array($g['name'], $kategori_cek, true) ? 'checked' : '' ?>>
                            <label class="category-btn" for="kat<?= (int) $i ?>"><?= e($g['name']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-ungu w-100">Terapkan</button>
                </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/my-shelf.js') ?>"></script>
</body>

</html>
