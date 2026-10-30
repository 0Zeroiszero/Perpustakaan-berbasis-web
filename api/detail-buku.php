<?php
require __DIR__ . '/_auth.php';

$id = trim((string) ($_GET['id'] ?? ''));
$buku = preg_match('/^[A-Za-z0-9]{1,10}$/', $id)
    ? row(
        "SELECT e.id_book AS id, e.judul, e.tahun_terbit AS tahun, e.jumlah_halaman AS halaman, e.isbn, e.sinopsis,
                e.cover AS cover_path, e.file_book AS file_path, e.format_file AS format,
                k.nama_kategori AS kategori,
                GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
         FROM ebook e
         JOIN kategori k ON k.id_kategori = e.kategori_id_kategori
         LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
         LEFT JOIN author a ON a.id_author = ea.author_id_author
         WHERE e.id_book = ?
         GROUP BY e.id_book, e.judul, e.tahun_terbit, e.jumlah_halaman, e.isbn, e.sinopsis, e.cover, e.file_book, e.format_file, k.nama_kategori",
        [$id]
    )
    : null;

if (!$buku) {
    http_response_code(404);
}

// Status rak user yang login (null kalau buku belum disimpan).
$status_rak = $buku && akun()
    ? (row(
        "SELECT status_baca FROM reading_progress WHERE user_id_user = ? AND book_id_book = ?",
        [akun()['id'], $buku['id']]
    )['status_baca'] ?? null)
    : null;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $buku ? e($buku['judul']) : 'Buku tidak ditemukan' ?> - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/detail-buku.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../index.html">
                <span class="logo">P</span>
                PustakaDigital
            </a>
            <!-- Tombol hanya ikon, jadi butuh aria-label supaya tetap terbaca screen reader -->
            <a href="katalog.php" class="btn btn-ungu-outline btn-kembali ms-auto" aria-label="Kembali ke Katalog"
                title="Kembali ke Katalog">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>
    </nav>
    <main class="container py-5">
        <?php if (!$buku): ?>
            <div class="card-clean p-5 text-center">
                <i class="bi bi-search" style="font-size:3rem;color:var(--abu)" aria-hidden="true"></i>
                <h1 class="page-title mt-3">Buku tidak ditemukan</h1>
                <p class="text-secondary">Buku yang kamu cari mungkin sudah dihapus dari katalog.</p>
                <a href="katalog.php" class="btn btn-ungu mt-2">Kembali ke Katalog</a>
            </div>
        <?php else: ?>
            <?php
            $badgeKategori = match ($buku['kategori']) {
                'Teknologi' => 'bg-biru-muda text-biru',
                'Novel' => 'bg-danger-subtle text-danger',
                default => 'bg-ungu-muda text-ungu',
            };
            $kelasStatus = match ($status_rak) {
                'Akan dibaca' => 'btn-pinjam',
                'Sedang dibaca' => 'btn-rak-sedang',
                'Telah dibaca' => 'btn-rak-selesai',
                'Tidak selesai' => 'btn-rak-gagal',
                default => 'btn-rak-netral',
            };
            $labelStatus = $status_rak ?? 'Tambah ke rak';
            ?>
            <div class="row gy-5 gx-lg-5 align-items-start">
                <div class="col-md-5">
                    <div class="detail-cover">
                        <?php if ($buku['cover_path']): ?>
                            <img src="../<?= e($buku['cover_path']) ?>" alt="<?= e($buku['judul']) ?>">
                        <?php else: ?>
                            <i class="bi bi-book" style="font-size:8rem" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-7">
                    <span class="badge <?= $badgeKategori ?>">
                        <?= e($buku['kategori']) ?>
                    </span>
                    <h1 class="page-title mt-3">
                        <?= e($buku['judul']) ?>
                    </h1>
                    <p class="text-secondary fs-5">
                        <?= e($buku['penulis']) ?>
                    </p>
                    <p class="text-secondary lh-lg">
                        <?= e($buku['sinopsis'] ?: 'Belum ada sinopsis untuk buku ini.') ?>
                    </p>
                    <div class="d-grid gap-2 d-sm-flex mt-4">
                        <?php if ($buku['file_path']): ?>
                            <!-- Buka file e-book di tab baru -->
                            <a href="../<?= e($buku['file_path']) ?>" target="_blank" rel="noopener"
                                class="btn btn-baca px-4">
                                <i class="bi bi-book-half me-1" aria-hidden="true"></i>
                                Baca Buku
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-baca px-4" disabled>
                                <i class="bi bi-book-half me-1" aria-hidden="true"></i>
                                Baca Buku
                            </button>
                        <?php endif; ?>

                        <form method="post" action="rak.php" class="dropdown">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="simpan">
                            <input type="hidden" name="book_id" value="<?= e($buku['id']) ?>">
                            <button type="button" class="btn <?= $kelasStatus ?> px-4 w-100 dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?= e($labelStatus) ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach (['Akan dibaca', 'Sedang dibaca', 'Telah dibaca', 'Tidak selesai'] as $status): ?>
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
            <!-- INFO: kolomnya sama dengan tabel di kelola-buku.php (judul & kategori sudah tampil di atas) -->
            <section class="mt-4 mt-md-5">
                <div class="card-clean p-4">
                    <h2 class="h4 fw-bold mb-3">
                        Informasi Buku
                    </h2>
                    <dl class="row row-cols-2 row-cols-md-3 g-3 mb-0">
                        <div class="col">
                            <dt class="info-label">
                                Penulis
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['penulis']) ?>
                            </dd>
                        </div>
                        <div class="col">
                            <dt class="info-label">
                                Kategori
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['kategori']) ?>
                            </dd>
                        </div>
                        <div class="col">
                            <dt class="info-label">
                                Tahun
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['tahun'] ?: '-') ?>
                            </dd>
                        </div>
                        <div class="col">
                            <dt class="info-label">
                                Halaman
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['halaman'] ?: '-') ?>
                            </dd>
                        </div>
                        <div class="col">
                            <dt class="info-label">
                                ISBN
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['isbn'] ?: '-') ?>
                            </dd>
                        </div>
                        <div class="col">
                            <dt class="info-label">
                                Format
                            </dt>
                            <dd class="mb-0 fw-semibold">
                                <?= e($buku['format']) ?>
                            </dd>
                        </div>
                    </dl>
                    <p class="text-secondary mt-4 mb-0">
                        Buku tersedia dalam koleksi digital PustakaDigital.
                        Pengguna dapat menyimpan buku ke Rak Saya dan
                        mengaksesnya melalui dashboard.
                    </p>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <a href="../index.html" class="footer-brand"><span
                            class="footer-logo">P</span><span>PustakaDigital</span></a>
                    <p class="footer-description mt-3 mb-0">Perpustakaan digital untuk menemukan, membaca, dan mengelola
                        koleksi buku dengan mudah.</p>
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
            <div class="footer-bottom"><span>© Dibuat oleh Kelompok 5 menggunakan Bootstrap.</span><span>Dibuat untuk
                    kebutuhan pembelajaran.</span></div>
        </div>
    </footer>
</body>

</html>
