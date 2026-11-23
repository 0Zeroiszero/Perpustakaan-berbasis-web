<?php
// Kelola Publisher: daftar + modal tambah/edit + pencarian PHP. Tanpa hapus master.
require __DIR__ . '/../../includes/data_helper.php';
require __DIR__ . '/../../includes/slug_helper.php';
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['publisher_id'] ?? 0);
    $name = trim(is_string($_POST['name'] ?? null) ? $_POST['name'] : '');
    $city = trim(is_string($_POST['city'] ?? null) ? $_POST['city'] : '');

    if ($name === '' || mb_strlen($name) > 100) {
        flash('Nama penerbit wajib diisi (maksimal 100 karakter).', 'danger');
        header('Location: ' . url('admin/publishers.php' . ($id ? '?edit=' . $id : '?tambah=1')));
        exit;
    }

    $slug = generate_unique_slug($name, 'publishers', $id ?: null);

    if ($id > 0) {
        q('UPDATE publishers SET name = ?, slug = ?, city = ? WHERE publisher_id = ?', [$name, $slug, $city, $id]);
        flash('Penerbit "' . $name . '" diperbarui.');
    } else {
        q('INSERT INTO publishers (name, slug, city) VALUES (?, ?, ?)', [$name, $slug, $city]);
        flash('Penerbit "' . $name . '" ditambahkan.');
    }
    header('Location: ' . url('admin/publishers.php'));
    exit;
}

$edit = row('SELECT * FROM publishers WHERE publisher_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$tambah = isset($_GET['tambah']);
$sql = 'SELECT * FROM publishers';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR city LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY name';
$daftar = rows($sql, $params);

$judul = 'Kelola Publisher - PustakaDigital';
$css = [];
$aktif = 'publishers';
$admin = true;
require __DIR__ . '/../../includes/partials/head.php';
require __DIR__ . '/../../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Kelola Publisher';
    $subTopbar = 'Master penerbit untuk data buku.';
    require __DIR__ . '/../../includes/partials/app_topbar.php';
    ?>
    <main class="container-fluid p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="page-title page-title-sm">Kelola Publisher</h1>
                <p class="text-secondary mb-0">Penerbit tidak dapat dihapus; buku nonaktif tetap menjaga relasinya.</p>
            </div>
            <a class="btn btn-ungu" href="<?= url('admin/publishers.php?tambah=1') ?>">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Tambah Publisher
            </a>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="table-card">
            <form method="get" action="<?= url('admin/publishers.php') ?>" class="row g-2 align-items-center mb-3">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"
                                aria-hidden="true"></i></span>
                        <input name="q" type="search" class="form-control border-start-0"
                            placeholder="Cari nama atau kota..." aria-label="Cari publisher" value="<?= e($q) ?>">
                        <button class="btn btn-ungu" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-md-4 col-lg-6 text-md-end">
                    <span class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"><?= count($daftar) ?> Publisher</span>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Kota</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $p): ?>
                            <tr>
                                <td><b><?= e($p['name']) ?></b></td>
                                <td><?= e($p['city'] ?: '-') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-light" title="Ubah" aria-label="Ubah <?= e($p['name']) ?>"
                                        href="<?= url('admin/publishers.php?edit=' . (int) $p['publisher_id']) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center py-4 <?= $daftar ? 'd-none' : '' ?>">
                <p class="text-secondary mb-0">Tidak ada publisher yang cocok.</p>
            </div>
        </div>
    </main>
</div>

<!-- MODAL TAMBAH / EDIT PUBLISHER -->
<div class="modal fade" id="modalPublisher" tabindex="-1" aria-labelledby="modalPublisherJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <form method="post" action="<?= url('admin/publishers.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="publisher_id" value="<?= (int) ($edit['publisher_id'] ?? 0) ?>">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalPublisherJudul"><?= $edit ? 'Edit Publisher' : 'Tambah Publisher' ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="name">Nama publisher</label>
                        <input id="name" name="name" type="text" class="form-control" maxlength="100"
                            value="<?= e($edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="city">Kota</label>
                        <input id="city" name="city" type="text" class="form-control" maxlength="100" list="daftarKota"
                            value="<?= e($edit['city'] ?? '') ?>">
                        <datalist id="daftarKota">
                            <?php foreach (KOTA as $kota): ?>
                                <option value="<?= e($kota) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
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
    <script>new bootstrap.Modal(document.getElementById("modalPublisher")).show();</script>
<?php endif; ?>
</body>

</html>
