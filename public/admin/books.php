<?php
// Admin buku: daftar + modal tambah/edit + nonaktifkan (soft-delete). Tanpa hapus keras.
require __DIR__ . '/../../includes/slug_helper.php';
require __DIR__ . '/../../includes/cover_helper.php';
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

// Upload sampul kustom: JPG/PNG/WEBP/GIF maks 5 MB, divalidasi getimagesize.
function simpan_cover(int $id): ?string
{
    if (($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['cover'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload cover gagal. Coba lagi.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran cover maksimal 5 MB.');
    }

    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? null;
    if ($ext === null) {
        throw new RuntimeException('Cover harus berupa gambar JPG, PNG, WEBP, atau GIF.');
    }

    $nama = $id . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], COVER_DIR . '/' . $nama)) {
        throw new RuntimeException('Cover gagal disimpan. Periksa izin folder uploads/covers/.');
    }

    return 'uploads/covers/' . $nama;
}

// PDF ke nama sementara: id buku belum ada sebelum INSERT AUTO_INCREMENT.
function simpan_pdf_tmp(): ?array
{
    if (($_FILES['file_book']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['file_book'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload berkas PDF gagal. Coba lagi.');
    }
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException('Ukuran berkas PDF maksimal 50 MB.');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        throw new RuntimeException('Berkas e-book harus PDF (format lain dikonversi manual di luar sistem).');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        throw new RuntimeException('Isi berkas bukan PDF yang valid.');
    }

    $tmp = STORAGE_DIR . '/tmp-' . bin2hex(random_bytes(8)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $tmp)) {
        throw new RuntimeException('Berkas gagal disimpan. Periksa izin folder storage/ebooks/.');
    }

    $pages = preg_match_all('/\/Type\s*\/Page\b/', (string) file_get_contents($tmp));

    return ['tmp' => $tmp, 'size' => filesize($tmp), 'sha' => hash_file('sha256', $tmp), 'pages' => $pages];
}

function simpan_pdf(int $id): ?array
{
    $pdf = simpan_pdf_tmp();
    if ($pdf === null) {
        return null;
    }
    rename($pdf['tmp'], STORAGE_DIR . '/' . $id . '.pdf');
    $pdf['path'] = 'storage/ebooks/' . $id . '.pdf';
    return $pdf;
}

$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$edit = row('SELECT * FROM books WHERE book_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$tambah = isset($_GET['tambah']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $ke = (int) ($_POST['ke'] ?? 1);
        $buku = row('SELECT title FROM books WHERE book_id = ?', [$id]);

        if (!$buku || !in_array($ke, [0, 1], true)) {
            flash('Buku atau status tidak valid.', 'danger');
        } else {
            q('UPDATE books SET is_active = ? WHERE book_id = ?', [$ke, $id]);
            flash('Buku "' . $buku['title'] . '" ' . ($ke ? 'diaktifkan kembali.' : 'dinonaktifkan dari katalog.'));
        }
        header('Location: ' . url('admin/books.php?q=' . urlencode($q)));
        exit;
    }

    // Simpan tambah/edit buku dari modal.
    $id = (int) ($_POST['book_id'] ?? 0);
    $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
    $author_id = (int) ($_POST['author_id'] ?? 0);
    $publisher_id = (int) ($_POST['publisher_id'] ?? 0);
    $genre_id = (int) ($_POST['genre_id'] ?? 0);
    $tahun = trim(is_string($_POST['published_year'] ?? null) ? $_POST['published_year'] : '');
    $isbn = trim(is_string($_POST['isbn'] ?? null) ? $_POST['isbn'] : '');
    $description = trim(is_string($_POST['description'] ?? null) ? $_POST['description'] : '');
    $ada_pdf = ($_FILES['file_book']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $pesan = match (true) {
        $title === '' || mb_strlen($title) > 200 => 'Judul wajib diisi (maksimal 200 karakter).',
        !row('SELECT author_id FROM authors WHERE author_id = ?', [$author_id]) => 'Penulis tidak valid.',
        !row('SELECT publisher_id FROM publishers WHERE publisher_id = ?', [$publisher_id]) => 'Penerbit tidak valid.',
        !row('SELECT genre_id FROM genres WHERE genre_id = ?', [$genre_id]) => 'Kategori tidak valid.',
        mb_strlen($description) > 2000 => 'Sinopsis maksimal 2000 karakter.',
        $tahun !== '' && (!ctype_digit($tahun) || (int) $tahun < 1000 || (int) $tahun > 2100) => 'Tahun harus angka 1000-2100.',
        mb_strlen($isbn) > 20 => 'ISBN maksimal 20 karakter.',
        $isbn !== '' && row('SELECT book_id FROM books WHERE isbn = ? AND book_id <> ?', [$isbn, $id]) !== null => 'ISBN sudah dipakai buku lain.',
        $id === 0 && !$ada_pdf => 'Berkas PDF wajib diunggah untuk buku baru.',
        default => null,
    };

    if ($pesan !== null) {
        flash($pesan, 'danger');
        header('Location: ' . url('admin/books.php' . ($id ? '?edit=' . $id : '?tambah=1')));
        exit;
    }

    $lama = $id ? row('SELECT * FROM books WHERE book_id = ?', [$id]) : null;
    $tahun = $tahun === '' ? null : (int) $tahun;
    $isbn = $isbn === '' ? null : $isbn;
    $slug = generate_unique_slug($title, 'books', $id ?: null);

    $cover_baru = null;
    $pdf_baru = null;
    try {
        if ($lama) {
            $cover_baru = simpan_cover($id);
            $pdf_baru = simpan_pdf($id);
        } else {
            $pdf_baru = simpan_pdf_tmp();
        }
    } catch (RuntimeException $e) {
        if ($cover_baru !== null && $cover_baru !== ($lama['cover_path'] ?? null)) {
            @unlink(__DIR__ . '/../../public/' . $cover_baru);
        }
        if ($pdf_baru !== null) {
            @unlink($pdf_baru['tmp'] ?? __DIR__ . '/../../' . $pdf_baru['path']);
        }
        flash($e->getMessage(), 'danger');
        header('Location: ' . url('admin/books.php' . ($lama ? '?edit=' . $id : '?tambah=1')));
        exit;
    }

    if ($lama) {
        q(
            'UPDATE books SET title = ?, slug = ?, description = ?, published_year = ?, isbn = ?,
             author_id = ?, publisher_id = ?, genre_id = ?, cover_path = ?, storage_path = ?,
             file_size = ?, checksum_sha256 = ?, page_count = ? WHERE book_id = ?',
            [
                $title, $slug, $description, $tahun, $isbn, $author_id, $publisher_id, $genre_id,
                $cover_baru ?? $lama['cover_path'],
                $pdf_baru['path'] ?? $lama['storage_path'],
                $pdf_baru['size'] ?? $lama['file_size'],
                $pdf_baru['sha'] ?? $lama['checksum_sha256'],
                $pdf_baru['pages'] ?? $lama['page_count'],
                $id,
            ]
        );
        flash('Buku "' . $title . '" berhasil diperbarui.');
    } else {
        q(
            'INSERT INTO books (author_id, publisher_id, genre_id, title, slug, description, published_year, isbn,
             storage_path, file_size, checksum_sha256, page_count, cover_path, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [$author_id, $publisher_id, $genre_id, $title, $slug, $description, $tahun, $isbn,
             'storage/ebooks/' . basename($pdf_baru['tmp']), $pdf_baru['size'], $pdf_baru['sha'], $pdf_baru['pages'], '']
        );
        global $koneksi;
        $id = (int) $koneksi->insert_id;
        rename($pdf_baru['tmp'], STORAGE_DIR . '/' . $id . '.pdf');

        try {
            $cover_path = simpan_cover($id);
        } catch (RuntimeException $e) {
            q('DELETE FROM books WHERE book_id = ?', [$id]);
            @unlink(STORAGE_DIR . '/' . $id . '.pdf');
            flash($e->getMessage(), 'danger');
            header('Location: ' . url('admin/books.php?tambah=1'));
            exit;
        }
        if ($cover_path === null) {
            $penulis = row('SELECT name FROM authors WHERE author_id = ?', [$author_id]);
            $genre = row('SELECT name FROM genres WHERE genre_id = ?', [$genre_id]);
            $nama = generate_default_cover($id, $title, $penulis['name'] ?? '', $genre['name'] ?? '');
            $cover_path = $nama ? 'uploads/covers/' . $nama : '';
        }
        q('UPDATE books SET storage_path = ?, cover_path = ? WHERE book_id = ?', ['storage/ebooks/' . $id . '.pdf', $cover_path, $id]);
        flash('Buku "' . $title . '" berhasil ditambahkan.');
    }

    header('Location: ' . url('admin/books.php'));
    exit;
}

$sql = 'SELECT * FROM v_books_catalog';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE title LIKE ? OR author_name LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY book_id DESC';
$buku = rows($sql, $params);

$authors = rows('SELECT author_id, name FROM authors ORDER BY name');
$publishers = rows('SELECT publisher_id, name FROM publishers ORDER BY name');
$genres = rows('SELECT genre_id, name FROM genres ORDER BY name');

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
            <a class="btn btn-ungu" href="<?= url('admin/books.php?tambah=1') ?>">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Buku
            </a>
        </div>

        <?php tampilkan_flash(); ?>

        <div class="table-card">
            <form method="get" action="<?= url('admin/books.php') ?>" class="row g-2 align-items-center mb-3">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary" aria-hidden="true"></i>
                        </span>
                        <input id="cariBukuAdmin" name="q" type="search" class="form-control border-start-0"
                            placeholder="Cari judul atau penulis..." aria-label="Cari buku" value="<?= e($q) ?>">
                        <button class="btn btn-ungu" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-md-4 col-lg-6 text-md-end">
                    <span id="jumlahBukuAdmin" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"
                        aria-live="polite"><?= count($buku) ?> Buku</span>
                </div>
            </form>

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
                                        title="<?= e($b['description']) ?>"><?= e($b['description']) ?></div>
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
                                        href="<?= url('admin/books.php?edit=' . (int) $b['book_id']) ?>">
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
                <p class="text-secondary mt-2 mb-0">Tidak ada buku yang cocok.</p>
            </div>
        </div>
    </main>
</div>

<!-- MODAL TAMBAH / EDIT BUKU -->
<div class="modal fade" id="modalBuku" tabindex="-1" aria-labelledby="modalBukuJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <form method="post" action="<?= url('admin/books.php') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="simpan">
                <input type="hidden" name="book_id" value="<?= (int) ($edit['book_id'] ?? 0) ?>">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalBukuJudul"><?= $edit ? 'Edit Buku' : 'Tambah Buku' ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="inputJudul">Judul</label>
                        <input id="inputJudul" name="title" type="text" class="form-control" maxlength="200"
                            value="<?= e($edit['title'] ?? '') ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="inputAuthor">Penulis</label>
                            <select id="inputAuthor" name="author_id" class="form-select" required>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= (int) $a['author_id'] ?>"
                                        <?= (int) ($edit['author_id'] ?? 0) === (int) $a['author_id'] ? 'selected' : '' ?>>
                                        <?= e($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="inputPublisher">Penerbit</label>
                            <select id="inputPublisher" name="publisher_id" class="form-select" required>
                                <?php foreach ($publishers as $p): ?>
                                    <option value="<?= (int) $p['publisher_id'] ?>"
                                        <?= (int) ($edit['publisher_id'] ?? 0) === (int) $p['publisher_id'] ? 'selected' : '' ?>>
                                        <?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="inputGenre">Kategori</label>
                            <select id="inputGenre" name="genre_id" class="form-select" required>
                                <?php foreach ($genres as $g): ?>
                                    <option value="<?= (int) $g['genre_id'] ?>"
                                        <?= (int) ($edit['genre_id'] ?? 0) === (int) $g['genre_id'] ? 'selected' : '' ?>>
                                        <?= e($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="inputTahun">Tahun</label>
                            <input id="inputTahun" name="published_year" type="number" min="1000" max="2100"
                                class="form-control" value="<?= e($edit['published_year'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="inputIsbn">ISBN</label>
                            <input id="inputIsbn" name="isbn" type="text" class="form-control" maxlength="20"
                                value="<?= e($edit['isbn'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="inputCover">Cover</label>
                            <input id="inputCover" name="cover" type="file" class="form-control"
                                accept="image/jpeg,image/png,image/webp,image/gif">
                            <div class="form-text">Kosongkan untuk sampul otomatis.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="inputFile">Berkas PDF</label>
                            <input id="inputFile" name="file_book" type="file" class="form-control" accept=".pdf,application/pdf">
                            <div class="form-text">
                                <?php if (!empty($edit['storage_path'])): ?>
                                    Saat ini: <?= e(basename($edit['storage_path'])) ?>. Kosongkan jika tidak diganti.
                                <?php else: ?>
                                    PDF maks 50 MB; wajib untuk buku baru.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" for="inputSinopsis">Sinopsis</label>
                        <textarea id="inputSinopsis" name="description" class="form-control" rows="4"
                            maxlength="2000"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-ungu"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/admin-books.js') ?>"></script>
<?php if ($edit || $tambah): ?>
    <script>new bootstrap.Modal(document.getElementById("modalBuku")).show();</script>
<?php endif; ?>
</body>

</html>
