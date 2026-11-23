<?php
// Kelola Penulis: daftar + modal tambah/edit + pencarian PHP; slug otomatis.
require __DIR__ . '/../../includes/data_helper.php';
require __DIR__ . '/../../includes/slug_helper.php';
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['author_id'] ?? 0);
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $country = trim(is_string($_POST['country'] ?? null) ? $_POST['country'] : '');
    $bio = trim(is_string($_POST['bio'] ?? null) ? $_POST['bio'] : '');

    if ($name === '' || mb_strlen($name) > 100) {
        flash('Nama penulis wajib diisi (maksimal 100 karakter).', 'danger');
        header('Location: ' . url('admin/authors.php' . ($id ? '?edit=' . $id : '?tambah=1')));
        exit;
    }

    $slug = generate_unique_slug($name, 'authors', $id > 0 ? $id : null);
    if ($id > 0) {
        q('UPDATE authors SET name = ?, slug = ?, country = ?, bio = ? WHERE author_id = ?', [$name, $slug, $country, $bio, $id]);
        flash('Penulis "' . $name . '" diperbarui.');
    } else {
        q('INSERT INTO authors (name, slug, country, bio) VALUES (?, ?, ?, ?)', [$name, $slug, $country, $bio]);
        flash('Penulis "' . $name . '" ditambahkan.');
    }
    header('Location: ' . url('admin/authors.php'));
    exit;
}

$edit = row('SELECT * FROM authors WHERE author_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$tambah = isset($_GET['tambah']);
$sql = 'SELECT * FROM authors';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR country LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY name';
$daftar = rows($sql, $params);

$judul = 'Kelola Penulis - PustakaDigital';
$css = [];
$aktif = 'authors';
$admin = true;
require __DIR__ . '/../../includes/partials/head.php';
require __DIR__ . '/../../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Kelola Penulis';
    $subTopbar = 'Master penulis untuk data buku.';
    require __DIR__ . '/../../includes/partials/app_topbar.php';
    ?>
    <main class="container-fluid p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="page-title page-title-sm">Kelola Penulis</h1>
                <p class="text-secondary mb-0">Slug dibuat otomatis dari nama; penulis tidak dapat dihapus.</p>
            </div>
            <a class="btn btn-ungu" href="<?= url('admin/authors.php?tambah=1') ?>">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Penulis
            </a>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="table-card">
            <form method="get" action="<?= url('admin/authors.php') ?>" class="row g-2 align-items-center mb-3">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"
                                aria-hidden="true"></i></span>
                        <input name="q" type="search" class="form-control border-start-0"
                            placeholder="Cari nama atau negara..." aria-label="Cari penulis" value="<?= e($q) ?>">
                        <button class="btn btn-ungu" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-md-4 col-lg-6 text-md-end">
                    <span class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"><?= count($daftar) ?> Penulis</span>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Negara</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $a): ?>
                            <tr>
                                <td><b><?= e($a['name']) ?></b></td>
                                <td class="small text-secondary"><?= e($a['slug']) ?></td>
                                <td><?= e($a['country'] ?: '-') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-light" title="Ubah" aria-label="Ubah <?= e($a['name']) ?>"
                                        href="<?= url('admin/authors.php?edit=' . (int) $a['author_id']) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center py-4 <?= $daftar ? 'd-none' : '' ?>">
                <p class="text-secondary mb-0">Tidak ada penulis yang cocok.</p>
            </div>
        </div>
    </main>
</div>

<!-- MODAL TAMBAH / EDIT PENULIS -->
<div class="modal fade" id="modalAuthor" tabindex="-1" aria-labelledby="modalAuthorJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <form method="post" action="<?= url('admin/authors.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="author_id" value="<?= (int) ($edit['author_id'] ?? 0) ?>">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalAuthorJudul"><?= $edit ? 'Edit Penulis' : 'Tambah Penulis' ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="name">Nama penulis</label>
                        <input id="name" name="name" type="text" class="form-control" maxlength="100"
                            value="<?= e($edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="country">Negara asal</label>
                        <input id="country" name="country" type="text" class="form-control" maxlength="60" list="daftarNegara"
                            value="<?= e($edit['country'] ?? '') ?>">
                        <datalist id="daftarNegara">
                            <?php foreach (NEGARA as $negara): ?>
                                <option value="<?= e($negara) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" maxlength="2000"><?= e($edit['bio'] ?? '') ?></textarea>
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
<?php if ($edit || $tambah): ?>
    <script>new bootstrap.Modal(document.getElementById("modalAuthor")).show();</script>
<?php endif; ?>
</body>

</html>
