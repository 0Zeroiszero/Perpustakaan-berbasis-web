<?php
require __DIR__ . '/_auth.php';
wajib_login();

$rak = rows(
    "SELECT e.id_book AS id, e.judul, e.sinopsis,
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
     GROUP BY e.id_book, e.judul, e.sinopsis, e.cover, e.file_book, k.nama_kategori, r.progress_percent, r.last_read, r.status_baca
     ORDER BY r.last_read DESC, e.id_book DESC",
    [(string) akun()['id']]
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rak Saya - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/rak-saya.css">
</head>

<body>
    <aside id="sidebar" class="sidebar">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="../index.html">
            <span class="logo">P</span>
            PustakaDigital
        </a>
        <nav class="nav flex-column">
            <a class="nav-link" href="<?= dashboard_url() ?>">
                <i class="bi bi-grid me-2" aria-hidden="true"></i>
                Dashboard
            </a>
            <a class="nav-link" href="katalog.php">
                <i class="bi bi-book me-2" aria-hidden="true"></i>
                Katalog
            </a>
            <a class="nav-link active" href="rak-saya.php" aria-current="page">
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
                    <b>Rak Saya</b>
                    <div class="small text-secondary">
                        Buku yang sudah kamu simpan.
                    </div>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-ungu-muda text-ungu">User</span>
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

            <!-- TOOLBAR RAK — tepat di atas grid, nempel di bawah navbar dashboard.
                 UI saja: belum ada logika JS, nanti cukup listensubmit di kontrol ini.
                 Di ponsel select urutan disembunyikan (d-none d-md-block) dan dipindah ke
                 menu titik tiga, biar barisnya tetap 1 baris dan tidak menutup layar. -->
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
                    $progres = (int) $b['progres'];
                    $status = $b['status'] ?: 'Akan dibaca';
                    $selesai = $status === 'Telah dibaca';
                    $badgeStatus = match ($status) {
                        'Telah dibaca' => 'bg-success-subtle text-success',
                        'Sedang dibaca' => 'bg-ungu-muda text-ungu',
                        'Tidak selesai' => 'bg-danger-subtle text-danger',
                        default => 'bg-biru-muda text-biru',
                    };
                    $badgeKategori = match ($b['kategori']) {
                        'Teknologi' => 'bg-biru-muda text-biru',
                        'Novel' => 'bg-danger-subtle text-danger',
                        default => 'bg-ungu-muda text-ungu',
                    };
                ?>
                    <div class="col-6 col-lg-3 buku-rak">
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if ($b['cover_path']): ?>
                                    <img src="../<?= e($b['cover_path']) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-book" style="font-size:3rem;color:var(--ungu)" aria-hidden="true"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-3">
                                <span class="badge <?= $badgeKategori ?>"><?= e($b['kategori']) ?></span>
                                <h2 class="h5 fw-bold mt-2 mb-1"><?= e($b['judul']) ?></h2>
                                <p class="text-secondary small mb-2"><?= e($b['penulis']) ?></p>
                                <div class="progress mb-1" style="height:7px;">
                                    <div class="progress-bar" style="width:<?= $progres ?>%;background:var(--ungu);"></div>
                                </div>
                                <?php if ($selesai): ?>
                                    <p class="small mb-3"><span class="badge <?= $badgeStatus ?> d-inline-block">Selesai Dibaca</span></p>
                                <?php else: ?>
                                    <p class="small mb-3">
                                        <span class="badge <?= $badgeStatus ?>"><?= e($status) ?></span>
                                        <span class="text-secondary ms-1"><?= $progres ?>%</span>
                                    </p>
                                <?php endif; ?>
                                <div class="d-grid gap-2">
                                    <?php if ($selesai): ?>
                                        <a href="katalog.php" class="btn btn-ungu"><i class="bi bi-compass me-1" aria-hidden="true"></i>Buku Selanjutnya</a>
                                        <?php if ($b['file_path']): ?>
                                            <a href="../<?= e($b['file_path']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Baca Ulang</a>
                                        <?php endif; ?>
                                    <?php elseif ($b['file_path']): ?>
                                        <a href="../<?= e($b['file_path']) ?>" target="_blank" rel="noopener" class="btn btn-baca"><i class="bi bi-book-half me-1" aria-hidden="true"></i><?= $status === 'Sedang dibaca' ? 'Lanjut Baca' : 'Baca Buku' ?></a>
                                    <?php else: ?>
                                        <button class="btn btn-baca" disabled><i class="bi bi-book-half me-1" aria-hidden="true"></i>Berkas Belum Tersedia</button>
                                    <?php endif; ?>
                                    <form method="post" action="rak.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="book_id" value="<?= e($b['id']) ?>">
                                        <button type="button" class="btn btn-light border w-100"
                                            data-judul="<?= e($b['judul']) ?>" onclick="hapusRak(this)">
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
                    <p class="text-secondary">Simpan buku dari katalog untuk mulai membaca.</p>
                    <a href="katalog.php" class="btn btn-ungu mt-2">
                        <i class="bi bi-grid me-1" aria-hidden="true"></i>Jelajahi Katalog
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL URUTKAN & FILTER — dibuka dari ikon corong di toolbar.
         Opsi pakai input .btn-check (radio/checkbox asli) jadi berubah terisi tanpa JS;
         yang belum ada hanya logika penyaringan kartunya. -->
    <div class="modal fade" id="modalFilterRak" tabindex="-1" aria-labelledby="modalFilterRakJudul"
        aria-hidden="true">
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
                    <p class="info-label mb-2 d-md-none" id="labelUrutRak">
                        Urutkan
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4 d-md-none" role="group" aria-labelledby="labelUrutRak">
                        <input type="radio" class="btn-check" name="urutRakOpsi" id="urutTerbaru" checked>
                        <label class="category-btn" for="urutTerbaru">Terbaru</label>
                        <input type="radio" class="btn-check" name="urutRakOpsi" id="urutAZ">
                        <label class="category-btn" for="urutAZ">Judul A-Z</label>
                        <input type="radio" class="btn-check" name="urutRakOpsi" id="urutProgres">
                        <label class="category-btn" for="urutProgres">Progres Baca</label>
                    </div>
                    <p class="info-label mb-2" id="labelStatusRak">
                        Status
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-labelledby="labelStatusRak">
                        <input type="radio" class="btn-check" name="statusRak" id="statusSemua" checked>
                        <label class="category-btn" for="statusSemua">Semua status</label>
                        <input type="radio" class="btn-check" name="statusRak" id="statusBerjalan">
                        <label class="category-btn" for="statusBerjalan">Sedang dibaca</label>
                        <input type="radio" class="btn-check" name="statusRak" id="statusSelesai">
                        <label class="category-btn" for="statusSelesai">Selesai dibaca</label>
                    </div>
                    <p class="info-label mb-2" id="labelKategoriRak">
                        Kategori
                    </p>
                    <div class="d-flex flex-wrap gap-2" role="group" aria-labelledby="labelKategoriRak">
                        <input type="checkbox" class="btn-check" name="kategoriRak" id="katPengembangan" checked>
                        <label class="category-btn" for="katPengembangan">Pengembangan Diri</label>
                        <input type="checkbox" class="btn-check" name="kategoriRak" id="katTeknologi" checked>
                        <label class="category-btn" for="katTeknologi">Teknologi</label>
                        <input type="checkbox" class="btn-check" name="kategoriRak" id="katNovel" checked>
                        <label class="category-btn" for="katNovel">Novel</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ungu w-100" data-bs-dismiss="modal">Terapkan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div id="toastRak" class="toast border-0" role="alert"
            style="background:var(--gelap); color:white; border-radius:12px">
            <div class="d-flex align-items-center">
                <div class="toast-body d-flex align-items-center gap-2">
                    <span
                        style="width:32px;height:32px;border-radius:8px;background:var(--ungu);display:inline-flex;align-items:center;justify-content:center">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                    </span>
                    <span id="toastRakMsg">Buku dihapus dari rak</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-3" aria-label="Tutup"
                    data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/rak-saya.js"></script>
</body>

</html>