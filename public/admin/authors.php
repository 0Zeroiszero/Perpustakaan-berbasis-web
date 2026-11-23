<?php
// Master penulis: tambah/edit oleh admin; slug otomatis; tanpa hapus.
require __DIR__ . '/../../includes/slug_helper.php';
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['author_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));
    $bio = trim((string) ($_POST['bio'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        flash('Nama penulis wajib diisi (maksimal 100 karakter).', 'danger');
        header('Location: ' . url('admin/authors.php' . ($id ? '?edit=' . $id : '')));
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
$daftar = rows('SELECT * FROM authors ORDER BY name');

$judul = 'Master Penulis - PustakaDigital';
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
            <h1 class="page-title page-title-sm">Master Penulis</h1>
            <p class="text-secondary mb-0">Slug dibuat otomatis dari nama; penulis tidak dapat dihapus.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-clean p-4">
                    <h2 class="h5 fw-bold mb-3"><?= $edit ? 'Edit Penulis' : 'Tambah Penulis' ?></h2>
                    <form method="post" action="<?= url('admin/authors.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="author_id" value="<?= (int) ($edit['author_id'] ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="name">Nama penulis</label>
                            <input id="name" name="name" type="text" class="form-control" maxlength="100"
                                value="<?= e($edit['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="country">Negara asal</label>
                            <input id="country" name="country" type="text" class="form-control" maxlength="60"
                                value="<?= e($edit['country'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="bio">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" rows="3" maxlength="2000"><?= e($edit['bio'] ?? '') ?></textarea>
                        </div>
                        <button class="btn btn-ungu w-100"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan</button>
                        <?php if ($edit): ?>
                            <a href="<?= url('admin/authors.php') ?>" class="btn btn-light border w-100 mt-2">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 fw-bold mb-0">Daftar Penulis</h2>
                        <span class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"><?= count($daftar) ?> Penulis</span>
                    </div>
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
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
