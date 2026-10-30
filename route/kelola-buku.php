<?php
// CRUD buku dalam satu file: kelola-buku.php
$host = 'localhost';
$db = 'db_pustaka';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // Kolom ini melengkapi tampilan lama yang memiliki penerbit dan jumlah halaman.
    foreach (["penerbit VARCHAR(150) NULL", "jumlah_halaman INT NULL"] as $kolom) {
        $nama = explode(' ', $kolom)[0];
        $cek = $pdo->query("SHOW COLUMNS FROM ebook LIKE " . $pdo->quote($nama))->fetch();
        if (!$cek) $pdo->exec("ALTER TABLE ebook ADD COLUMN $kolom");
    }
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan database db_pustaka sudah diimpor: ' . htmlspecialchars($e->getMessage()));
}
function e($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function idBaru($pdo, $tabel, $kolom, $awalan)
{
    $q = $pdo->query("SELECT $kolom FROM $tabel WHERE $kolom LIKE " . $pdo->quote($awalan . '%') . " ORDER BY $kolom DESC LIMIT 1")->fetchColumn();
    $nomor = $q ? (int)substr($q, strlen($awalan)) + 1 : 1;
    return $awalan . str_pad((string)$nomor, 3, '0', STR_PAD_LEFT);
}
$pesan = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'simpan') {
            $id = trim($_POST['id_book'] ?? '');
            $judul = trim($_POST['judul'] ?? '');
            $penulis = trim($_POST['penulis'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $penerbit = trim($_POST['penerbit'] ?? '');
            $tahun = ($_POST['tahun'] ?? '') !== '' ? (int)$_POST['tahun'] : null;
            $halaman = ($_POST['halaman'] ?? '') !== '' ? (int)$_POST['halaman'] : null;
            $format = trim($_POST['format_file'] ?? 'PDF');
            $sinopsis = trim($_POST['sinopsis'] ?? '');
            if ($judul === '' || $penulis === '' || $kategori === '') throw new Exception('Judul, penulis, dan kategori wajib diisi.');

            // File dipindahkan ke assets/book, sedangkan lokasi relatifnya disimpan di kolom file_book.
            $fileBook = null;
            if (isset($_FILES['file_book']) && $_FILES['file_book']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['file_book'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $pesanUpload = [UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload PHP.', UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar.', UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.', UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload tidak ditemukan.', UPLOAD_ERR_CANT_WRITE => 'File gagal ditulis ke server.', UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh konfigurasi PHP.'];
                    throw new Exception($pesanUpload[$file['error']] ?? 'Upload file gagal.');
                }
                if ($file['size'] > 20 * 1024 * 1024) throw new Exception('Ukuran file maksimal 20 MB.');
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf', 'epub'], true)) throw new Exception('File harus berformat PDF atau EPUB.');
                if (($format === 'PDF' && $ext !== 'pdf') || ($format === 'EPUB' && $ext !== 'epub')) throw new Exception('Format file yang diunggah harus sesuai dengan pilihan Format E-book.');
                $mime = '';
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
                        finfo_close($finfo);
                    }
                }
                if ($ext === 'pdf' && $mime !== '' && $mime !== 'application/pdf') throw new Exception('Isi file tidak terdeteksi sebagai PDF yang valid.');
                if ($ext === 'epub' && $mime !== '' && !in_array($mime, ['application/epub+zip', 'application/zip', 'application/octet-stream'], true)) throw new Exception('Isi file tidak terdeteksi sebagai EPUB yang valid.');
                $folderUpload = __DIR__ . '/../assets/book';
                if (!is_dir($folderUpload) && !mkdir($folderUpload, 0755, true) && !is_dir($folderUpload)) throw new Exception('Folder penyimpanan buku tidak dapat dibuat.');
                $namaFile = bin2hex(random_bytes(12)) . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $folderUpload . '/' . $namaFile)) throw new Exception('File gagal disimpan ke folder assets/book. Periksa izin folder.');
                $fileBook = '../assets/book/' . $namaFile;
            }

            if ($id !== '') {
                if ($fileBook !== null) {
                    $st = $pdo->prepare('UPDATE ebook SET judul=?, tahun_terbit=?, sinopsis=?, format_file=?, kategori_id_kategori=?, penerbit=?, jumlah_halaman=?, file_book=? WHERE id_book=?');
                    $st->execute([$judul, $tahun, $sinopsis, $format, $kategori, $penerbit ?: null, $halaman, $fileBook, $id]);
                } else {
                    $st = $pdo->prepare('UPDATE ebook SET judul=?, tahun_terbit=?, sinopsis=?, format_file=?, kategori_id_kategori=?, penerbit=?, jumlah_halaman=? WHERE id_book=?');
                    $st->execute([$judul, $tahun, $sinopsis, $format, $kategori, $penerbit ?: null, $halaman, $id]);
                }
                $bookId = $id;
                $pdo->prepare('DELETE FROM ebook_author WHERE book_id_book=?')->execute([$bookId]);
                $pesan = $fileBook !== null ? 'Data buku dan file berhasil diubah.' : 'Data buku berhasil diubah.';
            } else {
                $bookId = idBaru($pdo, 'ebook', 'id_book', 'BK');
                $st = $pdo->prepare('INSERT INTO ebook (id_book,judul,tahun_terbit,sinopsis,format_file,kategori_id_kategori,penerbit,jumlah_halaman,file_book) VALUES (?,?,?,?,?,?,?,?,?)');
                $st->execute([$bookId, $judul, $tahun, $sinopsis, $format, $kategori, $penerbit ?: null, $halaman, $fileBook]);
                $pesan = $fileBook !== null ? 'Buku dan file berhasil ditambahkan.' : 'Buku berhasil ditambahkan.';
            }
            $parts = preg_split('/\s+/', $penulis, 2);
            $depan = $parts[0] ?? $penulis;
            $belakang = $parts[1] ?? '';
            $st = $pdo->prepare('SELECT id_author FROM author WHERE CONCAT(nama_depan, " ", COALESCE(nama_belakang, "")) = ? LIMIT 1');
            $st->execute([$penulis]);
            $authorId = $st->fetchColumn();
            if (!$authorId) {
                $authorId = idBaru($pdo, 'author', 'id_author', 'AUT');
                $pdo->prepare('INSERT INTO author (id_author,nama_depan,nama_belakang) VALUES (?,?,?)')->execute([$authorId, $depan, $belakang ?: null]);
            }
            $pdo->prepare('INSERT INTO ebook_author (book_id_book,author_id_author) VALUES (?,?)')->execute([$bookId, $authorId]);
        } elseif ($aksi === 'hapus') {
            $id = $_POST['id_book'] ?? '';
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM reading_progress WHERE book_id_book=?')->execute([$id]);
            $pdo->prepare('DELETE FROM ebook_author WHERE book_id_book=?')->execute([$id]);
            $pdo->prepare('DELETE FROM ebook WHERE id_book=?')->execute([$id]);
            $pdo->commit();
            $pesan = 'Buku berhasil dihapus.';
        }
    }
} catch (Throwable $err) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pesan = 'Gagal memproses data: ' . $err->getMessage();
}
$kategoriList = $pdo->query('SELECT id_kategori,nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();
$sql = 'SELECT b.*, k.nama_kategori, GROUP_CONCAT(CONCAT(a.nama_depan, IFNULL(CONCAT(" ",a.nama_belakang),"")) SEPARATOR ", ") AS penulis FROM ebook b LEFT JOIN kategori k ON b.kategori_id_kategori=k.id_kategori LEFT JOIN ebook_author ea ON b.id_book=ea.book_id_book LEFT JOIN author a ON ea.author_id_author=a.id_author GROUP BY b.id_book ORDER BY b.judul';
$bukuList = $pdo->query($sql)->fetchAll();
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
            <a class="nav-link" href="dashboard-admin.html">
                <i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard
            </a>
            <a class="nav-link" href="katalog.html">
                <i class="bi bi-book me-2" aria-hidden="true"></i>Katalog
            </a>
            <a class="nav-link active" href="kelola-buku.php" aria-current="page">
                <i class="bi bi-collection me-2" aria-hidden="true"></i>Kelola Buku
            </a>
            <a class="nav-link" href="kelola-pengguna.html">
                <i class="bi bi-people me-2" aria-hidden="true"></i>Kelola Pengguna
            </a>
            <a class="nav-link" href="../index.html">
                <i class="bi bi-house me-2" aria-hidden="true"></i>Beranda
            </a>
            <a class="nav-link logout" href="login.html">
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
            <?php if ($pesan !== ''): ?><div class="alert alert-info alert-dismissible fade show" role="alert"><?= e($pesan) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div><?php endif; ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h1 class="page-title page-title-sm">Kelola Buku</h1>
                    <p class="text-secondary mb-0">Semua data buku yang tersimpan di PustakaDigital.</p>
                </div>
                <button class="btn btn-ungu" onclick="bukaModalTambah()">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Buku
                </button>
            </div>

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
                            aria-live="polite"><?= count($bukuList) ?> Buku</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Judul</th>
                                <th scope="col">Penulis</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Penerbit</th>
                                <th scope="col">Tahun</th>
                                <th scope="col">Halaman</th>
                                <th scope="col">Format</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabelBukuAdmin">
                            <?php foreach ($bukuList as $b): ?>
                                <tr class="baris-buku" data-sinopsis="<?= e($b['sinopsis']) ?>">
                                    <td class="kolom-judul"><b><?= e($b['judul']) ?></b>
                                        <div class="small text-secondary text-truncate" style="max-width:260px" title="<?= e($b['sinopsis']) ?>"><?= e($b['sinopsis']) ?></div>
                                    </td>
                                    <td class="kolom-penulis"><?= e($b['penulis'] ?: '-') ?></td>
                                    <td class="kolom-kategori"><?= e($b['nama_kategori'] ?: '-') ?></td>
                                    <td class="kolom-penerbit"><?= e($b['penerbit'] ?: '-') ?></td>
                                    <td class="kolom-tahun"><?= e($b['tahun_terbit'] ?: '-') ?></td>
                                    <td class="kolom-halaman"><?= e($b['jumlah_halaman'] ?: '-') ?></td>
                                    <td class="kolom-format"><?= e($b['format_file'] ?: '-') ?></td>
                                    <td class="kolom-status"><?php if (!empty($b['file_book'])): ?><span class="badge bg-success-subtle text-success">Tersedia</span><?php else: ?><span class="badge bg-danger-subtle text-danger">Belum Tersedia</span><?php endif; ?></td>
                                    <td class="text-nowrap"><button class="btn btn-sm btn-light" title="Ubah" onclick="editBuku(this)" data-book='<?= e(json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus buku ini?')"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_book" value="<?= e($b['id_book']) ?>"><button class="btn btn-sm btn-light text-danger" title="Hapus" type="submit"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="tabelBukuKosong" class="text-center py-5 d-none" role="status">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--abu)" aria-hidden="true"></i>
                    <p class="text-secondary mt-2 mb-0">Tidak ada buku yang cocok.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT -->
    <div class="modal fade" id="modalBuku" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content" style="border-radius:16px; max-height:90vh; overflow-y:auto; overflow-x:hidden">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalBukuTitle">Tambah Buku</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="formBuku" method="post" enctype="multipart/form-data"><input type="hidden" name="aksi" value="simpan"><input type="hidden" id="inputIdBook" name="id_book" value="">
                    <div class="modal-body" style="overflow-y:auto; overflow-x:hidden;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputBerkas"><i class="bi bi-upload me-1" aria-hidden="true"></i>Upload buku, novel, atau bacaan</label>
                            <input id="inputBerkas" name="file_book" type="file" class="form-control" accept=".pdf,.epub,application/pdf,application/epub+zip">
                            <div class="form-text">Format PDF atau EPUB, maksimal 20 MB. Saat mengedit, kosongkan jika tidak ingin mengganti file lama.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputJudul">Judul</label>
                            <input id="inputJudul" name="judul" type="text" class="form-control" placeholder="Judul buku" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputPenulis">Penulis</label>
                            <input id="inputPenulis" name="penulis" type="text" class="form-control" placeholder="Nama penulis" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="form-label fw-semibold" for="inputKategori">Kategori</label>
                                <select id="inputKategori" name="kategori" class="form-select" required><?php foreach ($kategoriList as $k): ?><option value="<?= e($k['id_kategori']) ?>"><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="col-5">
                                <label class="form-label fw-semibold" for="inputFormat">Format E-book</label>
                                <select id="inputFormat" name="format_file" class="form-select" required>
                                    <option value="PDF" selected>PDF</option>
                                    <option value="EPUB">EPUB</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold" for="inputPenerbit">Penerbit</label>
                                <input id="inputPenerbit" name="penerbit" type="text" class="form-control" placeholder="Nama penerbit">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold" for="inputTahun">Tahun</label>
                                <input id="inputTahun" name="tahun" type="number" min="1000" max="2100" class="form-control"
                                    placeholder="2024">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold" for="inputHalaman">Halaman</label>
                                <input id="inputHalaman" name="halaman" type="number" min="1" class="form-control" placeholder="320">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputSinopsis">Sinopsis</label>
                            <textarea id="inputSinopsis" name="sinopsis" class="form-control" rows="4"
                                placeholder="Ringkasan isi buku..."></textarea>
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

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080">
        <div id="toastAdmin" class="toast border-0" role="alert"
            style="background:var(--gelap); color:white; border-radius:12px">
            <div class="d-flex align-items-center">
                <div class="toast-body d-flex align-items-center gap-2">
                    <span
                        style="width:32px;height:32px;border-radius:8px;background:var(--ungu);display:inline-flex;align-items:center;justify-content:center">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                    </span>
                    <span id="toastAdminMsg">Berhasil disimpan</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-3" aria-label="Tutup"
                    data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function bukaModalTambah() {
            document.getElementById('formBuku').reset();
            document.getElementById('inputIdBook').value = '';
            document.getElementById('modalBukuTitle').textContent = 'Tambah Buku';
            new bootstrap.Modal(document.getElementById('modalBuku')).show();
        }

        function editBuku(btn) {
            const b = JSON.parse(btn.dataset.book);
            document.getElementById('formBuku').reset();
            document.getElementById('inputIdBook').value = b.id_book;
            document.getElementById('inputJudul').value = b.judul || '';
            document.getElementById('inputPenulis').value = b.penulis || '';
            document.getElementById('inputKategori').value = b.kategori_id_kategori || '';
            document.getElementById('inputPenerbit').value = b.penerbit || '';
            document.getElementById('inputTahun').value = b.tahun_terbit || '';
            document.getElementById('inputHalaman').value = b.jumlah_halaman || '';
            document.getElementById('inputFormat').value = b.format_file || 'PDF';
            document.getElementById('inputSinopsis').value = b.sinopsis || '';
            document.getElementById('modalBukuTitle').textContent = 'Edit Buku';
            new bootstrap.Modal(document.getElementById('modalBuku')).show();
        }
        document.getElementById('formBuku').addEventListener('submit', function() {});

        function cariBukuAdmin() {
            const kata = document.getElementById('cariBukuAdmin').value.toLowerCase();
            let n = 0;
            document.querySelectorAll('#tabelBukuAdmin .baris-buku').forEach(r => {
                const cocok = r.textContent.toLowerCase().includes(kata);
                r.classList.toggle('d-none', !cocok);
                if (cocok) n++;
            });
            document.getElementById('jumlahBukuAdmin').textContent = n + ' Buku';
            document.getElementById('tabelBukuKosong').classList.toggle('d-none', n !== 0);
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('modalBuku').addEventListener('hidden.bs.modal', () => {});
        });
    </script>
</body>

</html>