<?php
// Form tambah/edit buku (admin). PDF-saja; sampul kustom atau GD otomatis.
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

// Upload PDF ke nama sementara (id buku belum ada sebelum INSERT AUTO_INCREMENT).
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

    // Jumlah halaman objek /Type /Page; PDF ber-object-stream kompleks bisa 0 (lihat ruling ledger).
    $pages = preg_match_all('/\/Type\s*\/Page\b/', (string) file_get_contents($tmp));

    return [
        'tmp' => $tmp,
        'size' => filesize($tmp),
        'sha' => hash_file('sha256', $tmp),
        'pages' => $pages,
    ];
}

// Upload PDF langsung ke {id}.pdf (mode edit, id sudah diketahui).
function simpan_pdf(int $id): ?array
{
    $pdf = simpan_pdf_tmp();
    if ($pdf === null) {
        return null;
    }
    $path = STORAGE_DIR . '/' . $id . '.pdf';
    rename($pdf['tmp'], $path);
    $pdf['path'] = 'storage/ebooks/' . $id . '.pdf';
    return $pdf;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['book_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $author_id = (int) ($_POST['author_id'] ?? 0);
    $publisher_id = (int) ($_POST['publisher_id'] ?? 0);
    $genre_id = (int) ($_POST['genre_id'] ?? 0);
    $tahun = trim((string) ($_POST['published_year'] ?? ''));
    $isbn = trim((string) ($_POST['isbn'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

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
        header('Location: ' . url('admin/book-form.php' . ($id ? '?edit=' . $id : '')));
        exit;
    }

    $lama = $id ? row('SELECT * FROM books WHERE book_id = ?', [$id]) : null;
    if ($id && !$lama) {
        flash('Buku yang mau diubah tidak ditemukan.', 'danger');
        header('Location: ' . url('admin/books.php'));
        exit;
    }

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
            // Buku baru: id belum ada, PDF masuk nama sementara dulu.
            $pdf_baru = simpan_pdf_tmp();
        }
    } catch (RuntimeException $e) {
        // Jangan tinggalkan berkas yatim bila upload gagal.
        if ($cover_baru !== null && $cover_baru !== ($lama['cover_path'] ?? null)) {
            @unlink(__DIR__ . '/../../public/' . $cover_baru);
        }
        if ($pdf_baru !== null) {
            @unlink($pdf_baru['tmp'] ?? __DIR__ . '/../../' . $pdf_baru['path']);
        }
        flash($e->getMessage(), 'danger');
        header('Location: ' . url('admin/book-form.php' . ($lama ? '?edit=' . $id : '')));
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

        // Nama berkas final mengikuti id buku (aman path traversal, path DB stabil).
        rename($pdf_baru['tmp'], STORAGE_DIR . '/' . $id . '.pdf');

        // Sampul: kustom bila diunggah, else GD otomatis meniru cover referensi.
        try {
            $cover_path = simpan_cover($id);
        } catch (RuntimeException $e) {
            q('UPDATE books SET storage_path = ? WHERE book_id = ?', ['storage/ebooks/' . $id . '.pdf', $id]);
            flash($e->getMessage(), 'danger');
            header('Location: ' . url('admin/book-form.php?edit=' . $id));
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

$edit = row('SELECT * FROM books WHERE book_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$authors = rows('SELECT author_id, name FROM authors ORDER BY name');
$publishers = rows('SELECT publisher_id, name FROM publishers ORDER BY name');
$genres = rows('SELECT genre_id, name FROM genres ORDER BY name');

$judul = ($edit ? 'Edit Buku' : 'Tambah Buku') . ' - PustakaDigital';
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
                <div class="small text-secondary"><?= $edit ? 'Ubah data buku.' : 'Tambah buku baru ke koleksi.' ?></div>
            </div>
            <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <h1 class="page-title page-title-sm"><?= $edit ? 'Edit Buku' : 'Tambah Buku' ?></h1>
            <p class="text-secondary mb-0">Satu judul satu berkas PDF; jumlah halaman dihitung dari berkas.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card-clean p-4">
                    <form method="post" action="<?= url('admin/book-form.php') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="book_id" value="<?= (int) ($edit['book_id'] ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="title">Judul</label>
                            <input id="title" name="title" type="text" class="form-control" maxlength="200"
                                value="<?= e($edit['title'] ?? '') ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="author_id">Penulis</label>
                                <select id="author_id" name="author_id" class="form-select" required>
                                    <?php foreach ($authors as $a): ?>
                                        <option value="<?= (int) $a['author_id'] ?>"
                                            <?= (int) ($edit['author_id'] ?? 0) === (int) $a['author_id'] ? 'selected' : '' ?>>
                                            <?= e($a['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="publisher_id">Penerbit</label>
                                <select id="publisher_id" name="publisher_id" class="form-select" required>
                                    <?php foreach ($publishers as $p): ?>
                                        <option value="<?= (int) $p['publisher_id'] ?>"
                                            <?= (int) ($edit['publisher_id'] ?? 0) === (int) $p['publisher_id'] ? 'selected' : '' ?>>
                                            <?= e($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="genre_id">Kategori</label>
                                <select id="genre_id" name="genre_id" class="form-select" required>
                                    <?php foreach ($genres as $g): ?>
                                        <option value="<?= (int) $g['genre_id'] ?>"
                                            <?= (int) ($edit['genre_id'] ?? 0) === (int) $g['genre_id'] ? 'selected' : '' ?>>
                                            <?= e($g['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="published_year">Tahun</label>
                                <input id="published_year" name="published_year" type="number" min="1000" max="2100"
                                    class="form-control" placeholder="2024" value="<?= e($edit['published_year'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="isbn">ISBN</label>
                                <input id="isbn" name="isbn" type="text" class="form-control" maxlength="20"
                                    placeholder="978-602-0001-01-1" value="<?= e($edit['isbn'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="cover">Cover</label>
                                <input id="cover" name="cover" type="file" class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="form-text">
                                    <?php if (!empty($edit['cover_path'])): ?>
                                        Saat ini: <?= e(basename($edit['cover_path'])) ?>. Kosongkan jika tidak diganti.
                                    <?php else: ?>
                                        JPG, PNG, WEBP, GIF maks 5 MB. Kosongkan untuk sampul otomatis.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="file_book">Berkas PDF</label>
                                <input id="file_book" name="file_book" type="file" class="form-control" accept=".pdf,application/pdf">
                                <div class="form-text">
                                    <?php if (!empty($edit['storage_path'])): ?>
                                        Saat ini: <?= e(basename($edit['storage_path'])) ?> (<?= (int) $edit['page_count'] ?> halaman). Kosongkan jika tidak diganti.
                                    <?php else: ?>
                                        PDF maks 50 MB; wajib untuk buku baru.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="description">Sinopsis</label>
                            <textarea id="description" name="description" class="form-control" rows="4" maxlength="2000"
                                placeholder="Ringkasan isi buku..."><?= e($edit['description'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-ungu">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan
                            </button>
                            <a href="<?= url('admin/books.php') ?>" class="btn btn-light border">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
