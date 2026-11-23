<?php
// Admin pengguna: aktif/nonaktif akun (soft-delete). Tanpa hapus keras & tanpa edit profil orang lain.
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $ke = (int) ($_POST['ke'] ?? 1);
    $target = row('SELECT user_id, display_name, role, is_active FROM users WHERE user_id = ?', [$id]);

    $pesan = match (true) {
        !$target || !in_array($ke, [0, 1], true) => 'Akun atau status tidak valid.',
        $id === (int) akun()['id'] && $ke === 0 => 'Admin tidak dapat menonaktifkan akunnya sendiri.',
        $ke === 0 && $target['role'] === 'admin'
            && (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND user_id <> ?", [$id]) === 0
            => 'Admin aktif terakhir tidak dapat dinonaktifkan.',
        default => null,
    };

    if ($pesan !== null) {
        flash($pesan, 'danger');
    } else {
        q('UPDATE users SET is_active = ? WHERE user_id = ?', [$ke, $id]);
        flash('Akun "' . $target['display_name'] . '" ' . ($ke ? 'diaktifkan.' : 'dinonaktifkan.'));
    }
    header('Location: ' . url('admin/users.php'));
    exit;
}

$users = rows('SELECT * FROM users ORDER BY user_id');

$judul = 'Kelola Pengguna - PustakaDigital';
$css = ['admin-users.css'];
$aktif = 'users';
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
                <b>Kelola Pengguna</b>
                <div class="small text-secondary">Aktifkan atau nonaktifkan akun pembaca.</div>
            </div>
            <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
        </div>
    </header>

    <main class="container-fluid p-3 p-lg-4">
        <div class="mb-4">
            <h1 class="page-title page-title-sm">Kelola Pengguna</h1>
            <p class="text-secondary mb-0">Akun nonaktif ditolak saat login; data koleksi & progres tetap utuh.</p>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="table-card">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary" aria-hidden="true"></i>
                        </span>
                        <input id="cariUserAdmin" type="search" class="form-control border-start-0"
                            placeholder="Cari nama atau email..." aria-label="Cari pengguna"
                            oninput="cariUserAdmin()">
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <span id="jumlahUserAdmin" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"
                        aria-live="polite"><?= count($users) ?> Pengguna</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabelUserAdmin">
                        <?php foreach ($users as $u): ?>
                            <tr class="baris-user">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['display_name'], 0, 1))) ?></span>
                                        <b><?= e($u['display_name']) ?></b>
                                    </div>
                                </td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e(ucfirst($u['role'])) ?></td>
                                <td>
                                    <?php if ((int) $u['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $u['user_id'] === (int) akun()['id']): ?>
                                        <span class="small text-secondary">Akun Anda</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('admin/users.php') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $u['user_id'] ?>">
                                            <input type="hidden" name="ke" value="<?= (int) $u['is_active'] === 1 ? 0 : 1 ?>">
                                            <button type="button" class="btn btn-sm btn-light <?= (int) $u['is_active'] === 1 ? 'text-danger' : 'text-success' ?>"
                                                title="<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                                aria-label="<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> <?= e($u['display_name']) ?>"
                                                data-nama="<?= e($u['display_name']) ?>" data-ke="<?= (int) $u['is_active'] === 1 ? 0 : 1 ?>"
                                                onclick="toggleUser(this)">
                                                <i class="bi bi-<?= (int) $u['is_active'] === 1 ? 'person-slash' : 'person-check' ?>" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/admin-users.js') ?>"></script>
</body>

</html>
