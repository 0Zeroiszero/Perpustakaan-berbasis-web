<?php
require __DIR__ . '/_auth.php'; // untuk dashboard_url() di navbar
$buku = rows(
    "SELECT e.id_book AS id, e.judul, e.tahun_terbit AS tahun, e.sinopsis,
            e.cover AS cover_path, e.file_book AS file_path, e.format_file AS format,
            k.nama_kategori AS kategori,
            GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
     FROM ebook e
     JOIN kategori k ON k.id_kategori = e.kategori_id_kategori
     LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
     LEFT JOIN author a ON a.id_author = ea.author_id_author
     GROUP BY e.id_book, e.judul, e.tahun_terbit, e.sinopsis, e.cover, e.file_book, e.format_file, k.nama_kategori
     ORDER BY e.id_book DESC"
);
$kategori_list = rows("SELECT nama_kategori FROM kategori ORDER BY nama_kategori");
$status_baca = ['Akan dibaca', 'Sedang dibaca', 'Telah dibaca', 'Tidak selesai'];

// Status rak milik user yang login, dipakai untuk label & warna tombol tiap kartu.
$rak = [];
if ($user = akun()) {
    foreach (rows("SELECT book_id_book, status_baca FROM reading_progress WHERE user_id_user = ?", [$user['id']]) as $r) {
        $rak[$r['book_id_book']] = $r['status_baca'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/katalog.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../index.html">
                <span class="logo">P</span> PustakaDigital
            </a>

            <!-- Ikon search (mobile + desktop): membuka panel cari & filter dalam modal -->
            <div class="ms-auto d-flex align-items-center gap-2">
                <button type="button" class="catalog-search-icon" data-bs-toggle="modal" data-bs-target="#modalCari"
                    aria-label="Cari buku" title="Cari buku">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#catalogNavbarMenu" aria-controls="catalogNavbarMenu" aria-expanded="false"
                    aria-label="Buka menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="catalogNavbarMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="katalog.php" aria-current="page">Katalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= dashboard_url() ?>">Dashboard</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <?php if ($user): ?>
                            <div class="dropdown">
                                <button type="button" class="btn btn-akun dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <span class="avatar" aria-hidden="true">
                                        <?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
                                    </span>
                                    <span class="nama-akun"><?= e($user['nama']) ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <div class="dropdown-header">
                                            <b class="d-block text-dark"><?= e($user['nama']) ?></b>
                                            <span class="small text-secondary"><?= e($user['role']) ?></span>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="rak-saya.php">
                                            <i class="bi bi-bookmark me-2" aria-hidden="true"></i>Rak Saya
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item logout" href="logout.php">
                                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-ungu">Masuk</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <!-- HEADER -->
        <div class="mb-4">
            <h1 class="page-title mb-1">Temukan Buku Favoritmu</h1>
            <p class="text-secondary mb-0">Cari, filter, lalu simpan dan baca koleksinya secara digital.</p>
        </div>

        <!-- CATATAN: blok SEARCH + FILTER GABUNGAN dipindah ke modal #modalCari (lihat bawah),
             supaya tetap bisa dicari tanpa harus scroll naik ke bagian atas halaman -->

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Koleksi Buku</h2>
            <span id="jumlahBuku" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2" aria-live="polite"><?= count($buku) ?>
                Buku</span>
        </div>

        <!-- GRID BUKU: dirender dari tabel books, bukan kartu statis -->
        <div class="row g-4 grid-buku" id="bookGrid">
            <?php foreach ($buku as $b):
                $badgeKategori = match ($b['kategori']) {
                    'Teknologi' => 'bg-biru-muda text-biru',
                    'Novel' => 'bg-danger-subtle text-danger',
                    default => 'bg-ungu-muda text-ungu',
                };
                $status = $rak[$b['id']] ?? null;
                $kelasStatus = match ($status) {
                    'Akan dibaca' => 'btn-pinjam',
                    'Sedang dibaca' => 'btn-rak-sedang',
                    'Telah dibaca' => 'btn-rak-selesai',
                    'Tidak selesai' => 'btn-rak-gagal',
                    default => 'btn-rak-netral',
                };
                $labelStatus = $status ?? 'Tambah ke rak';
            ?>
                <div class="col-6 col-lg-3 book-item"
                    data-search="<?= e(strtolower(($b['judul'] ?? '') . ' ' . ($b['penulis'] ?? '') . ' ' . ($b['kategori'] ?? ''))) ?>"
                    data-kategori="<?= e($b['kategori']) ?>" data-judul="<?= e($b['judul']) ?>">
                    <div class="book-card">
                        <div class="book-cover">
                            <?php if ($b['cover_path']): ?>
                                <img src="../<?= e($b['cover_path']) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
                            <?php else: ?>
                                <i class="bi bi-book" style="font-size:3rem;color:var(--ungu)" aria-hidden="true"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <div class="badges">
                                <span class="badge <?= $badgeKategori ?>"><?= e($b['kategori']) ?></span>
                            </div>
                            <h3 class="h5 fw-bold mt-2 mb-1"><?= e($b['judul']) ?></h3>
                            <p class="text-secondary small mb-1"><?= e($b['penulis']) ?></p>
                            <div class="d-grid gap-2">
                                <a href="detail-buku.php?id=<?= e($b['id']) ?>" class="btn btn-ungu">Lihat Detail</a>
                                <form method="post" action="rak.php" class="dropdown">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="simpan">
                                    <input type="hidden" name="book_id" value="<?= e($b['id']) ?>">
                                    <button type="button" class="btn <?= $kelasStatus ?> w-100 dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <span><?= e($labelStatus) ?></span>
                                    </button>
                                    <ul class="dropdown-menu w-100">
                                        <?php foreach ($status_baca as $status): ?>
                                            <li>
                                                <button type="submit" class="dropdown-item" name="status"
                                                    value="<?= e($status) ?>"><?= e($status) ?></button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </form>
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
                    onclick="filterKategori('Semua', document.querySelector('.category-btn'))">Reset Filter</button>
            </div>
        </div>
    </main>

    <!-- SEARCH + FILTER GABUNGAN (dipindah ke modal, dibuka dari ikon cari di navbar) -->
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
                                placeholder="Cari judul, penulis... (misal: Atomic, Pramoedya)">
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
                        <button type="button" class="category-btn active" aria-pressed="true"
                            onclick="filterKategori('Semua', this)">Semua</button>
                        <?php foreach ($kategori_list as $kat): ?>
                            <button type="button" class="category-btn" aria-pressed="false"
                                onclick="filterKategori('<?= e($kat['nama_kategori']) ?>', this)">
                                <?= e($kat['nama_kategori']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <a href="../index.html" class="footer-brand">
                        <span class="footer-logo">P</span>
                        <span>PustakaDigital</span>
                    </a>
                    <p class="footer-description mt-3 mb-0">
                        Perpustakaan digital untuk menemukan, membaca, dan mengelola koleksi buku dengan mudah.
                    </p>
                </div>
                <div class="col-6 col-lg-3 col-md-3">
                    <h2 class="footer-title h6">Navigasi</h2>
                    <a href="../index.html" class="footer-link">Beranda</a>
                    <a href="katalog.php" class="footer-link">Katalog Buku</a>
                </div>
                <div class="col-6 col-lg-4 col-md-3">
                    <h2 class="footer-title h6">Informasi</h2>
                    <span class="footer-info"><i class="bi bi-envelope me-2" aria-hidden="true"></i>info@pustakadigital.id</span>
                    <div class="footer-social mt-3">
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                        <a href="#" aria-label="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© Dibuat oleh Kelompok 5 menggunakan Bootstrap.</span>
                <span>Dibuat untuk kebutuhan pembelajaran.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>