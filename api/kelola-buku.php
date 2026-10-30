<?php
require __DIR__ . '/_auth.php';
wajib_admin();

// Upload disimpan ke folder proyek; nama file mengikuti id buku supaya path DB stabil.
function simpan_cover(string $id): ?string
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
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if ($ext === null) {
        throw new RuntimeException('Cover harus berupa gambar JPG, PNG, atau WEBP.');
    }

    $path = 'cover/' . strtolower($id) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $path)) {
        throw new RuntimeException('Cover gagal disimpan. Periksa izin folder cover/.');
    }

    return $path;
}

function simpan_ebook(string $id): ?array
{
    if (($_FILES['file_book']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['file_book'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file e-book gagal. Coba lagi.');
    }
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file e-book maksimal 50 MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'epub', 'mobi'], true)) {
        throw new RuntimeException('File e-book harus PDF, EPUB, atau MOBI.');
    }

    $path = 'ebook/' . strtolower($id) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $path)) {
        throw new RuntimeException('File e-book gagal disimpan. Periksa izin folder ebook/.');
    }

    return ['path' => $path, 'format' => strtoupper($ext)];
}

// Input penulis bebas-teks → tabel author: kembalikan id_author yang cocok, dibuat bila belum ada.
function id_atau_buat_author(string $penulis): string
{
    $nama = array_pad(explode(' ', trim($penulis), 2), 2, '');
    $ada = row(
        "SELECT id_author FROM author WHERE nama_depan = ? AND nama_belakang = ?",
        [$nama[0], $nama[1]]
    );
    if ($ada) {
        return $ada['id_author'];
    }

    $terakhir = row("SELECT id_author FROM author ORDER BY id_author DESC LIMIT 1");
    // shortcut: pad 2 digit seperti seed; > 99 author merusak pad → order by DESC tak lagi akurat id terakhir.
    $id_baru = 'AUT' . str_pad((int) substr((string) ($terakhir['id_author'] ?? ''), 3) + 1, 2, '0', STR_PAD_LEFT);
    q("INSERT INTO author (id_author, nama_depan, nama_belakang) VALUES (?, ?, ?)", [$id_baru, $nama[0], $nama[1]]);
    return $id_baru;
}

// Semua aksi tulis lewat POST + redirect (PRG), bukan mengubah tabel lewat JavaScript.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $buku = preg_match('/^[A-Za-z0-9]{1,10}$/', $id) ? row("SELECT judul FROM ebook WHERE id_book = ?", [$id]) : null;

        if ($buku) {
            // FK tanpa ON DELETE CASCADE: hapus baris anak dulu.
            q("DELETE FROM reading_progress WHERE book_id_book = ?", [$id]);
            q("DELETE FROM ebook_author WHERE book_id_book = ?", [$id]);
            q("DELETE FROM ebook WHERE id_book = ?", [$id]);
            flash('Buku "' . $buku['judul'] . '" berhasil dihapus.');
        } else {
            flash('Buku yang mau dihapus tidak ditemukan.', 'danger');
        }
    } else {
        $id = trim((string) ($_POST['id'] ?? ''));
        $id = preg_match('/^[A-Za-z0-9]{1,10}$/', $id) && $id !== '0' ? $id : '';
        $judul = trim($_POST['judul'] ?? '');
        $penulis = trim($_POST['penulis'] ?? '');
        $kategori = trim((string) ($_POST['kategori'] ?? ''));
        $tahun = trim($_POST['tahun'] ?? '');
        $halaman = trim($_POST['halaman'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $format = $_POST['format'] ?? 'PDF';
        $sinopsis = trim($_POST['sinopsis'] ?? '');

        $format_valid = ['PDF', 'EPUB', 'MOBI'];

        $pesan = '';
        if ($judul === '' || $penulis === '' || !row("SELECT id_kategori FROM kategori WHERE id_kategori = ?", [$kategori])) {
            $pesan = 'Judul, penulis, dan kategori wajib diisi dengan benar.';
        } elseif (!in_array($format, $format_valid, true)) {
            $pesan = 'Format e-book tidak valid.';
        } elseif (mb_strlen($judul) > 255 || mb_strlen($penulis) > 100 || mb_strlen($sinopsis) > 2000) {
            $pesan = 'Judul maksimal 255, penulis 100, dan sinopsis 2000 karakter.';
        } elseif ($halaman !== '' && (!ctype_digit($halaman) || (int) $halaman < 1)) {
            $pesan = 'Jumlah halaman harus berupa angka positif.';
        } elseif (mb_strlen($isbn) > 20) {
            $pesan = 'ISBN maksimal 20 karakter.';
        } elseif ($isbn !== '' && row("SELECT id_book FROM ebook WHERE isbn = ? AND id_book <> ?", [$isbn, $id])) {
            $pesan = 'ISBN sudah dipakai buku lain.';
        }

        if ($pesan !== '') {
            flash($pesan, 'danger');
        } else {
            $tahun = $tahun === '' ? null : max(1000, min(2100, (int) $tahun));
            $halaman = $halaman === '' ? null : (int) $halaman;
            $isbn = $isbn === '' ? null : $isbn;
            $lama = null;
            $cover_path = null;
            $file_path = null;

            if ($id !== '') {
                $lama = row("SELECT cover, file_book FROM ebook WHERE id_book = ?", [$id]);
                if (!$lama) {
                    flash('Buku yang mau diubah tidak ditemukan.', 'danger');
                    header('Location: kelola-buku.php');
                    exit;
                }
                $cover_path = $lama['cover'];
                $file_path = $lama['file_book'];
            } else {
                // PK string tanpa AUTO_INCREMENT: lanjutkan nomor dari id terakhir.
                // shortcut: pad 3 digit seperti seed; > 999 buku merusak pad → order by DESC tak lagi akurat id terakhir.
                $terakhir = row("SELECT id_book FROM ebook ORDER BY id_book DESC LIMIT 1");
                $id = 'BK' . str_pad((int) substr((string) ($terakhir['id_book'] ?? ''), 2) + 1, 3, '0', STR_PAD_LEFT);
            }

            $cover_baru = null;
            $ebook_baru = null;
            try {
                $cover_baru = simpan_cover($id);
                if ($cover_baru !== null) {
                    $cover_path = $cover_baru;
                }
                $ebook_baru = simpan_ebook($id);
                if ($ebook_baru !== null) {
                    $file_path = $ebook_baru['path'];
                    $format = $ebook_baru['format'];
                }
            } catch (RuntimeException $e) {
                // Jangan tinggalkan file yatim kalau upload kedua gagal; cover lama yang path-nya
                // sama sudah tertimpa, jadi jangan dihapus.
                if ($cover_baru !== null && (!$lama || $cover_baru !== $lama['cover'])) {
                    @unlink(__DIR__ . '/../' . $cover_baru);
                }
                if ($ebook_baru !== null && (!$lama || $ebook_baru['path'] !== $lama['file_book'])) {
                    @unlink(__DIR__ . '/../' . $ebook_baru['path']);
                }
                flash($e->getMessage(), 'danger');
                header('Location: kelola-buku.php' . ($lama ? '?edit=' . urlencode($id) : ''));
                exit;
            }

            if ($lama) {
                q(
                    "UPDATE ebook SET judul = ?, tahun_terbit = ?, jumlah_halaman = ?, isbn = ?, sinopsis = ?,
                     cover = ?, file_book = ?, format_file = ?, kategori_id_kategori = ? WHERE id_book = ?",
                    [$judul, $tahun, $halaman, $isbn, $sinopsis, $cover_path, $file_path, $format, $kategori, $id]
                );
                // Penulis diperlakukan tunggal sesuai form: relasi lama diganti satu penulis baru.
                q("DELETE FROM ebook_author WHERE book_id_book = ?", [$id]);
                q("INSERT INTO ebook_author (book_id_book, author_id_author) VALUES (?, ?)", [$id, id_atau_buat_author($penulis)]);
                flash('Buku "' . $judul . '" berhasil diperbarui.');
            } else {
                q(
                    "INSERT INTO ebook
                         (id_book, judul, tahun_terbit, jumlah_halaman, isbn, sinopsis, cover, file_book, format_file, kategori_id_kategori)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$id, $judul, $tahun, $halaman, $isbn, $sinopsis, $cover_path, $file_path, $format, $kategori]
                );
                q("INSERT INTO ebook_author (book_id_book, author_id_author) VALUES (?, ?)", [$id, id_atau_buat_author($penulis)]);
                flash('Buku "' . $judul . '" berhasil ditambahkan.');
            }
        }
    }

    header('Location: kelola-buku.php');
    exit;
}

$ids = trim((string) ($_GET['edit'] ?? ''));
$edit = preg_match('/^[A-Za-z0-9]{1,10}$/', $ids) ? row(
    "SELECT e.id_book AS id, e.judul, e.tahun_terbit AS tahun, e.jumlah_halaman AS halaman, e.isbn,
            e.sinopsis, e.cover, e.file_book, e.format_file AS format, e.kategori_id_kategori,
            GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
     FROM ebook e
     LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
     LEFT JOIN author a ON a.id_author = ea.author_id_author
     WHERE e.id_book = ?
     GROUP BY e.id_book, e.judul, e.tahun_terbit, e.jumlah_halaman, e.isbn, e.sinopsis, e.cover, e.file_book, e.format_file, e.kategori_id_kategori",
    [$ids]
) : null;
$buku = rows(
    "SELECT e.id_book AS id, e.judul, e.tahun_terbit AS tahun, e.sinopsis, e.format_file AS format,
            k.nama_kategori AS kategori,
            GROUP_CONCAT(CONCAT_WS(' ', a.nama_depan, a.nama_belakang) SEPARATOR ', ') AS penulis
     FROM ebook e
     JOIN kategori k ON k.id_kategori = e.kategori_id_kategori
     LEFT JOIN ebook_author ea ON ea.book_id_book = e.id_book
     LEFT JOIN author a ON a.id_author = ea.author_id_author
     GROUP BY e.id_book, e.judul, e.tahun_terbit, e.sinopsis, e.format_file, k.nama_kategori
     ORDER BY e.id_book DESC"
);
$kategori_list = rows("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/kelola-buku.css">
</head>

<body>
    <!-- SIDEBAR -->
    <aside id="sidebar" class="sidebar">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="../index.html">
            <span class="logo">P</span>
            PustakaDigital
        </a>

        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-admin.php">
                <i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard
            </a>
            <a class="nav-link" href="katalog.php">
                <i class="bi bi-book me-2" aria-hidden="true"></i>Katalog
            </a>
            <a class="nav-link active" href="kelola-buku.php" aria-current="page">
                <i class="bi bi-collection me-2" aria-hidden="true"></i>Kelola Buku
            </a>
            <a class="nav-link" href="kelola-pengguna.php">
                <i class="bi bi-people me-2" aria-hidden="true"></i>Kelola Pengguna
            </a>
            <a class="nav-link" href="../index.html">
                <i class="bi bi-house me-2" aria-hidden="true"></i>Beranda
            </a>
            <a class="nav-link logout" href="logout.php">
                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <div class="main-with-sidebar">
        <header class="topbar p-3">
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <div>
                    <b>Kelola Buku</b>
                    <div class="small text-secondary">
                        Tambah, ubah, dan hapus data buku.
                    </div>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-ungu-muda text-ungu">Admin</span>
                </div>
            </div>
        </header>

        <main class="container-fluid p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h1 class="page-title page-title-sm">Kelola Buku</h1>
                    <p class="text-secondary mb-0">Semua data buku yang tersimpan di PustakaDigital.</p>
                </div>
                <button class="btn btn-ungu" onclick="bukaModalTambah()">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Buku
                </button>
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
                                <th scope="col">Format</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabelBukuAdmin">
                            <?php foreach ($buku as $b): ?>
                                <tr class="baris-buku" data-sinopsis="<?= e($b['sinopsis']) ?>">
                                    <td class="kolom-judul">
                                        <b><?= e($b['judul']) ?></b>
                                        <div class="small text-secondary text-truncate" style="max-width:260px"
                                            title="<?= e($b['sinopsis']) ?>">
                                            <?= e($b['sinopsis']) ?>
                                        </div>
                                    </td>
                                    <td class="kolom-penulis"><?= e($b['penulis']) ?></td>
                                    <td class="kolom-kategori"><?= e($b['kategori']) ?></td>
                                    <td class="kolom-tahun"><?= e($b['tahun'] ?: '-') ?></td>
                                    <td class="kolom-format"><?= e($b['format']) ?></td>
                                    <td class="text-nowrap">
                                        <a class="btn btn-sm btn-light" title="Ubah"
                                            href="kelola-buku.php?edit=<?= e($b['id']) ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form method="post" action="kelola-buku.php" class="d-inline form-hapus">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= e($b['id']) ?>">
                                            <button type="button" class="btn btn-sm btn-light text-danger" title="Hapus"
                                                data-judul="<?= e($b['judul']) ?>" onclick="hapusBuku(this)">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
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

    <!-- MODAL TAMBAH / EDIT -->
    <div class="modal fade" id="modalBuku" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content" style="border-radius:16px; max-height:90vh; overflow-y:auto; overflow-x:hidden">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalBukuTitle">
                        <?= $edit ? 'Edit Buku' : 'Tambah Buku' ?>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="formBuku" method="post" action="kelola-buku.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" id="inputId" value="<?= e($edit['id'] ?? '') ?>">
                    <div class="modal-body" style="overflow-y:auto; overflow-x:hidden;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputJudul">Judul</label>
                            <input id="inputJudul" name="judul" type="text" class="form-control" placeholder="Judul buku"
                                maxlength="255" value="<?= e($edit['judul'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputPenulis">Penulis</label>
                            <input id="inputPenulis" name="penulis" type="text" class="form-control"
                                placeholder="Nama penulis (misal: Andrea Hirata)" maxlength="100"
                                value="<?= e($edit['penulis'] ?? '') ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="form-label fw-semibold" for="inputKategori">Kategori</label>
                                <select id="inputKategori" name="kategori" class="form-select" required>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?= e($kat['id_kategori']) ?>"
                                            <?= ($edit['kategori_id_kategori'] ?? '') === $kat['id_kategori'] ? 'selected' : '' ?>>
                                            <?= e($kat['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-5">
                                <label class="form-label fw-semibold" for="inputFormat">Format E-book</label>
                                <select id="inputFormat" name="format" class="form-select" required>
                                    <?php foreach (['PDF', 'EPUB', 'MOBI'] as $fmt): ?>
                                        <option value="<?= e($fmt) ?>" <?= ($edit['format'] ?? 'PDF') === $fmt ? 'selected' : '' ?>>
                                            <?= e($fmt) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="inputTahun">Tahun</label>
                                <input id="inputTahun" name="tahun" type="number" min="1000" max="2100"
                                    class="form-control" placeholder="2024" value="<?= e($edit['tahun'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="inputHalaman">Jumlah Halaman</label>
                                <input id="inputHalaman" name="halaman" type="number" min="1" class="form-control"
                                    placeholder="320" value="<?= e($edit['halaman'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold" for="inputIsbn">ISBN</label>
                                <input id="inputIsbn" name="isbn" type="text" class="form-control" maxlength="20"
                                    placeholder="978-602-0001-01-1" value="<?= e($edit['isbn'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="inputCover">Cover</label>
                                <input id="inputCover" name="cover" type="file" class="form-control"
                                    accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">
                                    <?php if (!empty($edit['cover'])): ?>
                                        Saat ini: <a href="../<?= e($edit['cover']) ?>" target="_blank"
                                            rel="noopener"><?= e(basename($edit['cover'])) ?></a>. Kosongkan jika tidak diganti.
                                    <?php else: ?>
                                        JPG, PNG, atau WEBP maksimal 5 MB.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="inputFile">File E-book</label>
                                <input id="inputFile" name="file_book" type="file" class="form-control"
                                    accept=".pdf,.epub,.mobi">
                                <div class="form-text">
                                    <?php if (!empty($edit['file_book'])): ?>
                                        Saat ini: <a href="../<?= e($edit['file_book']) ?>" target="_blank"
                                            rel="noopener"><?= e(basename($edit['file_book'])) ?></a>. Kosongkan jika tidak diganti.
                                    <?php else: ?>
                                        PDF, EPUB, atau MOBI maksimal 50 MB.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputSinopsis">Sinopsis</label>
                            <textarea id="inputSinopsis" name="sinopsis" class="form-control" rows="4" maxlength="2000"
                                placeholder="Ringkasan isi buku..."><?= e($edit['sinopsis'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-ungu">
                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/kelola-buku.js"></script>
    <?php if ($edit): ?>
        <script>
            new bootstrap.Modal(document.getElementById("modalBuku")).show();
        </script>
    <?php endif; ?>
</body>

</html>
