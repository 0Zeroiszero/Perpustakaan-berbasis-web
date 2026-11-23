<?php
// Admin pengguna: modal tambah/edit, reset password sekali-tampil, aktif/nonaktif.
// Tanpa hapus keras; admin tidak dapat mengunci dirinya sendiri atau menghapus admin terakhir.
require __DIR__ . '/../../includes/auth_guard.php';
wajib_admin();
header('Cache-Control: no-store');

$q = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $ke = (int) ($_POST['ke'] ?? 1);
        $target = row('SELECT display_name, role, is_active FROM users WHERE user_id = ?', [$id]);

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
        header('Location: ' . url('admin/users.php?q=' . urlencode($q)));
        exit;
    }

    if ($aksi === 'reset_password') {
        $id = (int) ($_POST['id'] ?? 0);
        $target = row('SELECT display_name FROM users WHERE user_id = ?', [$id]);

        if (!$target) {
            flash('Akun tidak ditemukan.', 'danger');
            header('Location: ' . url('admin/users.php'));
            exit;
        }

        // Password acak hanya disimpan sebagai hash; teks polos tampil sekali via session.
        $abjad = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $plain = '';
        for ($i = 0; $i < 12; $i++) {
            $plain .= $abjad[random_int(0, strlen($abjad) - 1)];
        }
        q('UPDATE users SET password_hash = ? WHERE user_id = ?', [password_hash($plain, PASSWORD_DEFAULT), $id]);
        $_SESSION['reset_password_plain'] = ['id' => $id, 'nama' => $target['display_name'], 'password' => $plain];
        flash('Password "' . $target['display_name'] . '" berhasil direset. Salin password sementara di formulir.');
        header('Location: ' . url('admin/users.php?edit=' . $id));
        exit;
    }

    // Simpan tambah/edit profil pengguna.
    $id = (int) ($_POST['id'] ?? 0);
    $nama = trim(is_string($_POST['display_name'] ?? null) ? $_POST['display_name'] : '');
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
    $role = in_array($_POST['role'] ?? '', ['admin', 'member'], true) ? $_POST['role'] : 'member';
    $aktif = isset($_POST['is_active']) ? 1 : 0;
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $target = $id ? row('SELECT user_id, role, is_active FROM users WHERE user_id = ?', [$id]) : null;

    // Akun sendiri tidak boleh kehilangan akses admin.
    if ($target && $id === (int) akun()['id']) {
        $role = 'admin';
        $aktif = 1;
    }

    $pesan = match (true) {
        $nama === '' || mb_strlen($nama) > 100 => 'Nama wajib diisi (maksimal 100 karakter).',
        !filter_var($email, FILTER_VALIDATE_EMAIL) => 'Email tidak valid.',
        row('SELECT user_id FROM users WHERE email = ? AND user_id <> ?', [$email, $id]) !== null => 'Email sudah dipakai akun lain.',
        $id && !$target => 'Akun tidak ditemukan.',
        !$id && strlen($password) < 6 => 'Password awal minimal 6 karakter.',
        $target && $target['role'] === 'admin' && ($role !== 'admin' || $aktif === 0)
            && (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND user_id <> ?", [$id]) === 0
            => 'Admin aktif terakhir tidak dapat diturunkan atau dinonaktifkan.',
        default => null,
    };

    if ($pesan !== null) {
        flash($pesan, 'danger');
        header('Location: ' . url('admin/users.php' . ($id ? '?edit=' . $id : '?tambah=1')));
        exit;
    }

    if ($target) {
        q('UPDATE users SET display_name = ?, email = ?, role = ?, is_active = ? WHERE user_id = ?',
            [$nama, $email, $role, $aktif, $id]);
        flash('Akun "' . $nama . '" berhasil diperbarui.');
    } else {
        q('INSERT INTO users (display_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)',
            [$nama, $email, password_hash($password, PASSWORD_DEFAULT), $role, $aktif]);
        flash('Akun "' . $nama . '" berhasil ditambahkan.');
    }
    header('Location: ' . url('admin/users.php'));
    exit;
}

$edit = row('SELECT * FROM users WHERE user_id = ?', [(int) ($_GET['edit'] ?? 0)]);
$tambah = isset($_GET['tambah']);
$flashBaru = $_SESSION['reset_password_plain'] ?? null;
unset($_SESSION['reset_password_plain']);
if ($flashBaru && (int) ($edit['user_id'] ?? 0) !== (int) $flashBaru['id']) {
    $flashBaru = null;
}

$sql = 'SELECT * FROM users';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE display_name LIKE ? OR email LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY user_id';
$users = rows($sql, $params);

$judul = 'Kelola Pengguna - PustakaDigital';
$css = ['admin-users.css'];
$aktif = 'users';
$admin = true;
require __DIR__ . '/../../includes/partials/head.php';
require __DIR__ . '/../../includes/partials/sidebar.php';
?>

<div class="main-with-sidebar">
    <?php
    $judulTopbar = 'Kelola Pengguna';
    $subTopbar = 'Edit profil, reset password, atau nonaktifkan akun.';
    require __DIR__ . '/../../includes/partials/app_topbar.php';
    ?>

    <main class="container-fluid p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h1 class="page-title page-title-sm">Kelola Pengguna</h1>
                <p class="text-secondary mb-0">Akun nonaktif ditolak saat login; koleksi & progres tetap utuh.</p>
            </div>
            <a class="btn btn-ungu" href="<?= url('admin/users.php?tambah=1') ?>">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Tambah Pengguna
            </a>
        </div>
        <?php tampilkan_flash(); ?>
        <div class="table-card">
            <form method="get" action="<?= url('admin/users.php') ?>" class="row g-2 align-items-center mb-3">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary" aria-hidden="true"></i>
                        </span>
                        <input id="cariUserAdmin" name="q" type="search" class="form-control border-start-0"
                            placeholder="Cari nama atau email..." aria-label="Cari pengguna" value="<?= e($q) ?>">
                        <button class="btn btn-ungu" type="submit">Cari</button>
                    </div>
                </div>
                <div class="col-md-4 col-lg-6 text-md-end">
                    <span id="jumlahUserAdmin" class="badge bg-ungu-muda text-ungu rounded-pill px-3 py-2"
                        aria-live="polite"><?= count($users) ?> Pengguna</span>
                </div>
            </form>

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
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-light" title="Edit" aria-label="Edit <?= e($u['display_name']) ?>"
                                        href="<?= url('admin/users.php?edit=' . (int) $u['user_id']) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <?php if ((int) $u['user_id'] !== (int) akun()['id']): ?>
                                        <form method="post" action="<?= url('admin/users.php') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="aksi" value="toggle">
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
            <div class="text-center py-4 <?= $users ? 'd-none' : '' ?>">
                <p class="text-secondary mb-0">Tidak ada pengguna yang cocok.</p>
            </div>
        </div>
    </main>
</div>

<!-- MODAL TAMBAH / EDIT PENGGUNA -->
<div class="modal fade" id="modalUser" tabindex="-1" aria-labelledby="modalUserJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:var(--radius-lg)">
            <form method="post" action="<?= url('admin/users.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="save">
                <input type="hidden" name="id" value="<?= (int) ($edit['user_id'] ?? 0) ?>">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5 fw-bold" id="modalUserJudul"><?= $edit ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <?php if ($flashBaru): ?>
                        <div class="alert alert-success" role="status">
                            <p class="fw-semibold mb-2">Password sementara (tampil sekali):</p>
                            <div class="input-group">
                                <input id="passwordBaru" type="text" class="form-control" readonly
                                    value="<?= e($flashBaru['password']) ?>">
                                <button type="button" class="btn btn-ungu" onclick="salinPassword(this)">Salin</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="inputNama">Nama tampilan</label>
                        <input id="inputNama" name="display_name" type="text" class="form-control" maxlength="100"
                            value="<?= e($edit['display_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="inputEmail">Email</label>
                        <input id="inputEmail" name="email" type="email" class="form-control"
                            value="<?= e($edit['email'] ?? '') ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="inputRole">Role</label>
                            <select id="inputRole" name="role" class="form-select"
                                <?= $edit && (int) $edit['user_id'] === (int) akun()['id'] ? 'disabled' : '' ?>>
                                <option value="member" <?= ($edit['role'] ?? '') === 'member' ? 'selected' : '' ?>>Member</option>
                                <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($edit && (int) $edit['user_id'] === (int) akun()['id']): ?>
                                <input type="hidden" name="role" value="admin">
                                <div class="form-text">Role akun sendiri tidak dapat diturunkan.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input id="inputAktif" name="is_active" type="checkbox" class="form-check-input"
                                    <?= (!$edit || (int) $edit['is_active'] === 1) ? 'checked' : '' ?>
                                    <?= $edit && (int) $edit['user_id'] === (int) akun()['id'] ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="inputAktif">Akun aktif</label>
                                <?php if ($edit && (int) $edit['user_id'] === (int) akun()['id']): ?>
                                    <input type="hidden" name="is_active" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!$edit): ?>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputPasswordAwal">Password awal</label>
                            <input id="inputPasswordAwal" name="password" type="password" class="form-control" required>
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <div>
                        <?php if ($edit): ?>
                            <button type="submit" name="aksi" value="reset_password" class="btn btn-light border"
                                formnovalidate>
                                <i class="bi bi-key me-1" aria-hidden="true"></i>Reset Password
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-ungu"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
<script src="<?= asset('js/admin-users.js') ?>"></script>
<?php if ($edit || $tambah || $flashBaru): ?>
    <script>new bootstrap.Modal(document.getElementById("modalUser")).show();</script>
<?php endif; ?>
</body>

</html>
