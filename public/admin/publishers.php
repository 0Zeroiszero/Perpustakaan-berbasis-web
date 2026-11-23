<?php
// Master penerbit: tambah/edit oleh admin; tanpa hapus (kontrak skema final).
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['publisher_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        flash('Nama penerbit wajib diisi (maksimal 100 karakter).', 'danger');
        header('Location: ' . url('admin/publishers.php' . ($id ? '?edit=' . $id : '')));
        exit;
    }

    if ($id > 0) {
        q('UPDATE publishers SET name = ?, city = ? WHERE publisher_id = ?', [$name, $city, $id]);
        flash('Penerbit "' . $name . '" diperbarui.');
    } else {
        q('INSERT INTO publishers (name, city) VALUES (?, ?)', [$name, $city]);
        flash('Penerbit "' . $name . '" ditambahkan.');
    }
    header('Location: ' . url('admin/publishers.php'));
    exit;
}

$edit = row('SELECT * FROM publishers WHERE publisher_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$daftar = rows('SELECT * FROM publishers ORDER BY name');

$judul = 'Master Penerbit - PustakaDigital';
$css = [];
$aktif = 'masters';
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
                <b>Master Data</b>
                <div class="small text-secondary">Penerbit & penulis dikelola di sini.</div>
            </div>
            <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
        </div>
    </header>
    <main class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <h1 class="page-title page-title-sm">Master Penerbit</h1>
            <p class="text-secondary mb-0">Penerbit tidak dapat dihapus; buku nonaktif tetap menjaga relasinya.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-clean p-4">
                    <h2 class="h5 fw-bold mb-3"><?= $edit ? 'Edit Penerbit' : 'Tambah Penerbit' ?></h2>
                    <form method="post" action="<?= url('admin/publishers.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="publisher_id" value="<?= (int) ($edit['publisher_id'] ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="name">Nama penerbit</label>
                            <input id="name" name="name" type="text" class="form-control" maxlength="100"
                                value="<?= e($edit['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="city">Kota</label>
                            <input id="city" name="city" type="text" class="form-control" maxlength="100"
                                value="<?= e($edit['city'] ?? '') ?>">
                        </div>
                        <button class="btn btn-ungu w-100"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan</button>
                        <?php if ($edit): ?>
                            <a href="<?= url('admin/publishers.php') ?>" class="btn btn-light border w-100 mt-2">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Daftar Penerbit</h2>
                        <span class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"><?= count($daftar) ?> Penerbit</span>
                    </div>
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
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
