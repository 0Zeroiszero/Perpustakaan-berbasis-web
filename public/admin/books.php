<?php
// Admin buku: daftar + nonaktifkan/aktifkan (soft-delete is_active). Tanpa hapus keras.
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $ke = (int) ($_POST['ke'] ?? 1);
    $buku = row('SELECT title FROM books WHERE book_id = ?', [$id]);

    if (!$buku || !in_array($ke, [0, 1], true)) {
        flash('Buku atau status tidak valid.', 'danger');
    } else {
        q('UPDATE books SET is_active = ? WHERE book_id = ?', [$ke, $id]);
        flash('Buku "' . $buku['title'] . '" ' . ($ke ? 'diaktifkan kembali.' : 'dinonaktifkan dari katalog.'));
    }
    header('Location: ' . url('admin/books.php'));
    exit;
}

$buku = rows('SELECT * FROM v_books_catalog ORDER BY book_id DESC');

$judul = 'Kelola Buku - PustakaDigital';
$css = ['admin-books.css'];
$aktif = 'books';
$admin = true;
require __DIR__ . '/../../includes/partials/head.php';
require __DIR__ . '/../../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <header class="topbar p-3">
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div>
                <b>Kelola Buku</b>
                <div class="small text-secondary">Tambah, ubah, dan nonaktifkan data buku.</div>
            </div>
            <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="page-title page-title-sm">Kelola Buku</h1>
                <p class="text-secondary mb-0">Semua data buku yang tersimpan di PustakaDigital.</p>
            </div>
            <a class="btn btn-ungu" href="<?= url('admin/book-form.php') ?>">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Buku
            </a>
        </div>

        <?php tampilkan_flash(); ?>

        <div class="table-card">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary" aria-hidden="true"></i>
                        </span>
                        <input id="cariBukuAdmin" type="search" class="form-control border-start-0"
                            placeholder="Cari judul atau penulis..." aria-label="Cari buku"
                            oninput="cariBukuAdmin()">
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <span id="jumlahBukuAdmin" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"
                        aria-live="polite"><?= count($buku) ?> Buku</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Judul</th>
                            <th scope="col">Penulis</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Tahun</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabelBukuAdmin">
                        <?php foreach ($buku as $b): ?>
                            <tr class="baris-buku">
                                <td class="kolom-judul">
                                    <b><?= e($b['title']) ?></b>
                                    <div class="small text-secondary text-truncate" style="max-width:260px"
                                        title="<?= e($b['description']) ?>">
                                        <?= e($b['description']) ?>
                                    </div>
                                </td>
                                <td class="kolom-penulis"><?= e($b['author_name']) ?></td>
                                <td class="kolom-kategori"><?= e($b['genre_name']) ?></td>
                                <td class="kolom-tahun"><?= e($b['published_year'] ?: '-') ?></td>
                                <td>
                                    <?php if ((int) $b['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-light" title="Ubah" aria-label="Ubah <?= e($b['title']) ?>"
                                        href="<?= url('admin/book-form.php?edit=' . (int) $b['book_id']) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form method="post" action="<?= url('admin/books.php') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="aksi" value="status">
                                        <input type="hidden" name="id" value="<?= (int) $b['book_id'] ?>">
                                        <input type="hidden" name="ke" value="<?= (int) $b['is_active'] === 1 ? 0 : 1 ?>">
                                        <button type="button" class="btn btn-sm btn-light <?= (int) $b['is_active'] === 1 ? 'text-danger' : 'text-success' ?>"
                                            title="<?= (int) $b['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                            aria-label="<?= (int) $b['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> <?= e($b['title']) ?>"
                                            data-judul="<?= e($b['title']) ?>" data-ke="<?= (int) $b['is_active'] === 1 ? 0 : 1 ?>"
                                            onclick="toggleBuku(this)">
                                            <i class="bi bi-<?= (int) $b['is_active'] === 1 ? 'eye-slash' : 'eye' ?>" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="tabelBukuKosong" class="text-center py-5 <?= $buku ? 'd-none' : '' ?>" role="status">
                <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--abu)" aria-hidden="true"></i>
                <p class="text-secondary mt-2 mb-0">Belum ada buku. Tambahkan buku pertama lewat tombol di atas.</p>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/admin-books.js') ?>"></script>
</body>

</html>
