<?php
require __DIR__ . '/_auth.php';
wajib_admin();

$saya = akun();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $target = preg_match('/^[A-Za-z0-9]{1,10}$/', $id)
            ? row(
                "SELECT id_user AS id, CONCAT_WS(' ', nama_depan, nama_belakang) AS nama,
                        CASE WHEN user_role = 'Admin' THEN 'Admin' ELSE 'User' END AS role
                 FROM users WHERE id_user = ?",
                [$id]
            )
            : null;

        if (!$target) {
            flash('Pengguna yang mau dihapus tidak ditemukan.', 'danger');
        } elseif ($id === (string) $saya['id']) {
            flash('Kamu tidak dapat menghapus akun sendiri.', 'danger');
        } elseif ($saya['role'] === 'User' && $target['role'] === 'Admin') {
            // Proteksi role: User tidak boleh menghapus Admin, siapa pun yang mendaftar lebih dulu.
            flash('Pengguna dengan role User tidak boleh menghapus akun Admin.', 'danger');
        } elseif ($target['role'] === 'Admin' && (int) scalar("SELECT COUNT(*) FROM users WHERE user_role = 'Admin'") <= 1) {
            flash('Akun Admin terakhir tidak boleh dihapus.', 'danger');
        } else {
            // FK reading_progress tanpa ON DELETE CASCADE: hapus progres bacaannya dulu.
            q("DELETE FROM reading_progress WHERE user_id_user = ?", [$id]);
            q("DELETE FROM users WHERE id_user = ?", [$id]);
            flash('Pengguna "' . $target['nama'] . '" berhasil dihapus.');
        }
    } else {
        $id = trim((string) ($_POST['id'] ?? ''));
        $id = preg_match('/^[A-Za-z0-9]{1,10}$/', $id) && $id !== '0' ? $id : '';
        $nama = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'User';
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
        $nomor_ponsel = trim($_POST['nomor_ponsel'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $role_valid = ['Admin', 'User'];
        $gender_valid = ['L', 'P'];
        $nama = array_pad(explode(' ', $nama, 2), 2, '');   // [nama_depan, nama_belakang]

        $pesan = '';
        if ($nama[0] === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = 'Nama dan email wajib diisi dengan format email yang benar.';
        } elseif (!in_array($role, $role_valid, true)) {
            $pesan = 'Role pengguna tidak valid.';
        } elseif (!in_array($jenis_kelamin, $gender_valid, true)) {
            $pesan = 'Jenis kelamin wajib dipilih.';
        } elseif (mb_strlen($nomor_ponsel) > 20 || mb_strlen($alamat) > 256 || mb_strlen($bio) > 1000) {
            $pesan = 'Nomor ponsel maksimal 20 karakter, alamat 256 karakter, dan bio 1000 karakter.';
        } elseif ($id === '' && strlen($password) < 6) {
            $pesan = 'Password pengguna baru minimal 6 karakter.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $pesan = 'Password baru minimal 6 karakter.';
        } elseif ($id === (string) $saya['id'] && $role !== 'Admin') {
            $pesan = 'Role akun sendiri tidak dapat diubah.';
        } elseif (row("SELECT id_user FROM users WHERE email = ? AND id_user <> ?", [$email, $id])) {
            $pesan = 'Email sudah dipakai pengguna lain.';
        }

        if ($pesan !== '') {
            flash($pesan, 'danger');
        } elseif ($id) {
            $nomor_ponsel = $nomor_ponsel === '' ? null : $nomor_ponsel;
            $alamat = $alamat === '' ? null : $alamat;
            $bio = $bio === '' ? null : $bio;

            if ($password !== '') {
                q(
                    "UPDATE users SET nama_depan = ?, nama_belakang = ?, email = ?, password = ?,
                     user_role = ?, jenis_kelamin = ?, nomor_ponsel = ?, alamat = ?, bio = ? WHERE id_user = ?",
                    [$nama[0], $nama[1], $email, password_hash($password, PASSWORD_DEFAULT), $role, $jenis_kelamin, $nomor_ponsel, $alamat, $bio, $id]
                );
            } else {
                q(
                    "UPDATE users SET nama_depan = ?, nama_belakang = ?, email = ?,
                     user_role = ?, jenis_kelamin = ?, nomor_ponsel = ?, alamat = ?, bio = ? WHERE id_user = ?",
                    [$nama[0], $nama[1], $email, $role, $jenis_kelamin, $nomor_ponsel, $alamat, $bio, $id]
                );
            }
            flash('Pengguna "' . trim(implode(' ', $nama)) . '" berhasil diperbarui.');
        } else {
            $nomor_ponsel = $nomor_ponsel === '' ? null : $nomor_ponsel;
            $alamat = $alamat === '' ? null : $alamat;
            $bio = $bio === '' ? null : $bio;

            // PK string tanpa AUTO_INCREMENT: lanjutkan nomor dari id terakhir.
            // shortcut: pad 3 digit seperti seed; > 999 pengguna merusak pad → order by DESC tak lagi akurat id terakhir.
            $terakhir = row("SELECT id_user FROM users ORDER BY id_user DESC LIMIT 1");
            $id = 'USR' . str_pad((int) substr((string) ($terakhir['id_user'] ?? ''), 3) + 1, 3, '0', STR_PAD_LEFT);
            q(
                "INSERT INTO users (id_user, nama_depan, nama_belakang, email, password, user_role, jenis_kelamin, nomor_ponsel, alamat, bio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$id, $nama[0], $nama[1], $email, password_hash($password, PASSWORD_DEFAULT), $role, $jenis_kelamin, $nomor_ponsel, $alamat, $bio]
            );
            flash('Pengguna "' . trim(implode(' ', $nama)) . '" berhasil ditambahkan.');
        }
    }

    header('Location: kelola-pengguna.php');
    exit;
}

$ids = trim((string) ($_GET['edit'] ?? ''));
$edit = preg_match('/^[A-Za-z0-9]{1,10}$/', $ids) ? row(
    "SELECT id_user AS id, CONCAT_WS(' ', nama_depan, nama_belakang) AS nama, email,
            CASE WHEN user_role = 'Admin' THEN 'Admin' ELSE 'User' END AS role, jenis_kelamin,
            nomor_ponsel, alamat, bio
     FROM users WHERE id_user = ?",
    [$ids]
) : null;
$pengguna = rows(
    "SELECT u.id_user AS id,
            CONCAT_WS(' ', u.nama_depan, u.nama_belakang) AS nama,
            u.email, CASE WHEN u.user_role = 'Admin' THEN 'Admin' ELSE 'User' END AS role,
            (SELECT COUNT(*) FROM reading_progress r
              WHERE r.user_id_user = u.id_user AND r.progress_percent BETWEEN 1 AND 99) AS dibaca
     FROM users u
     ORDER BY u.id_user DESC"
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - PustakaDigital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%237C3AED'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-weight='800' font-size='58' fill='white'%3EP%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="../assets/css/kelola-pengguna.css">
</head>

<body>
    <aside id="sidebar" class="sidebar">
        <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="../index.html"><span
                class="logo">P</span>PustakaDigital</a>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard-admin.php"><i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard</a>
            <a class="nav-link" href="katalog.php"><i class="bi bi-book me-2" aria-hidden="true"></i>Katalog</a>
            <a class="nav-link" href="kelola-buku.php"><i class="bi bi-collection me-2" aria-hidden="true"></i>Kelola Buku</a>
            <a class="nav-link active" href="kelola-pengguna.php" aria-current="page"><i class="bi bi-people me-2" aria-hidden="true"></i>Kelola Pengguna</a>
            <a class="nav-link" href="../index.html"><i class="bi bi-house me-2" aria-hidden="true"></i>Beranda</a>
            <a class="nav-link logout" href="logout.php"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar</a>
        </nav>
    </aside>

    <div class="main-with-sidebar">
        <header class="topbar p-3">
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu"><i
                        class="bi bi-list" aria-hidden="true"></i></button>
                <div><b>Kelola Pengguna</b>
                    <div class="small text-secondary">Daftar pengguna PustakaDigital.</div>
                </div>
                <div class="ms-auto"><span class="badge bg-ungu-muda text-ungu">Admin</span></div>
            </div>
        </header>

        <main class="container-fluid p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h1 class="page-title page-title-sm">Kelola Pengguna</h1>
                    <p class="text-secondary mb-0">Kelola data pengguna, role, dan status akun.</p>
                </div>
                <button type="button" class="btn btn-ungu" onclick="bukaTambah()"><i
                        class="bi bi-person-plus me-1" aria-hidden="true"></i>Tambah Pengguna</button>
            </div>

            <?php tampilkan_flash(); ?>

            <div class="table-card">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="input-group" style="max-width:320px">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"
                                    aria-hidden="true"></i></span>
                            <input id="cariPengguna" type="search" class="form-control"
                                placeholder="Cari nama atau email..." aria-label="Cari pengguna"
                                oninput="cariPengguna()">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span id="jumlahPengguna" class="badge bg-ungu-muda text-ungu" aria-live="polite"><?= count($pengguna) ?> Pengguna</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Sedang Dibaca</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabelPengguna">
                            <?php foreach ($pengguna as $p): ?>
                                <tr class="baris-pengguna">
                                    <td class="kolom-nama">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar"><i class="bi bi-person" aria-hidden="true"></i></div>
                                            <div>
                                                <b><?= e($p['nama']) ?></b>
                                                <?php if ((string) $p['id'] === (string) $saya['id']): ?>
                                                    <span class="small text-ungu">(Anda)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="kolom-email">
                                        <?= e($p['email']) ?>
                                    </td>
                                    <td class="kolom-role">
                                        <span class="badge <?= $p['role'] === 'Admin' ? 'bg-biru-muda text-biru' : 'bg-ungu-muda text-ungu' ?>">
                                            <?= e($p['role']) ?>
                                        </span>
                                    </td>
                                    <td class="kolom-dibaca"><?= (int) $p['dibaca'] ?> Buku</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a class="btn btn-sm btn-light" title="Ubah"
                                                href="kelola-pengguna.php?edit=<?= e($p['id']) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                            <form method="post" action="kelola-pengguna.php" class="form-hapus">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                                <button type="button" class="btn btn-sm btn-light text-danger" title="Hapus"
                                                    data-nama="<?= e($p['nama']) ?>" onclick="hapusPengguna(this)">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="penggunaKosong" class="text-center py-4 <?= $pengguna ? 'd-none' : '' ?>" role="status">
                    <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--abu)" aria-hidden="true"></i>
                    <p class="text-secondary mt-2 mb-0">Tidak ada pengguna yang cocok.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT -->
    <div class="modal fade" id="modalPengguna" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:16px; max-height:90vh; overflow-y:auto; overflow-x:hidden">
                <div class="modal-header border-0">
                    <h2 id="modalTitle" class="modal-title h5 fw-bold">
                        <?= $edit ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="formPengguna" method="post" action="kelola-pengguna.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" id="inputId" value="<?= e($edit['id'] ?? '') ?>">
                    <div class="modal-body" style="overflow-y:auto; overflow-x:hidden;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputNama">Nama Lengkap</label>
                            <input id="inputNama" name="nama" type="text" class="form-control"
                                placeholder="Nama pengguna" value="<?= e($edit['nama'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputEmail">Email</label>
                            <input id="inputEmail" name="email" type="email" class="form-control"
                                placeholder="email@example.com" value="<?= e($edit['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inputPassword">Password</label>
                            <input id="inputPassword" name="password" type="password" class="form-control"
                                placeholder="<?= $edit ? 'Kosongkan jika tidak diganti' : 'Minimal 6 karakter' ?>"
                                autocomplete="new-password" <?= $edit ? '' : 'required' ?>>
                            <div class="form-text">Password disimpan sebagai hash Bcrypt, bukan teks polos.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="inputRole">Role</label>
                                <select id="inputRole" name="role" class="form-select">
                                    <?php foreach (['User', 'Admin'] as $r): ?>
                                        <option value="<?= e($r) ?>" <?= ($edit['role'] ?? 'User') === $r ? 'selected' : '' ?>>
                                            <?= e($r) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" for="inputJenisKelamin">Jenis Kelamin</label>
                                <select id="inputJenisKelamin" name="jenis_kelamin" class="form-select" required>
                                    <?php foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $kode => $jk): ?>
                                        <option value="<?= e($kode) ?>" <?= ($edit['jenis_kelamin'] ?? '') === $kode ? 'selected' : '' ?>>
                                            <?= e($jk) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputNomorPonsel">Nomor Ponsel</label>
                            <input id="inputNomorPonsel" name="nomor_ponsel" type="tel" class="form-control"
                                maxlength="20" placeholder="0812..." value="<?= e($edit['nomor_ponsel'] ?? '') ?>">
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputAlamat">Alamat</label>
                            <input id="inputAlamat" name="alamat" type="text" class="form-control" maxlength="256"
                                placeholder="Kota atau alamat lengkap" value="<?= e($edit['alamat'] ?? '') ?>">
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="inputBio">Bio</label>
                            <textarea id="inputBio" name="bio" class="form-control" rows="3" maxlength="1000"
                                placeholder="Deskripsi singkat pengguna..."><?= e($edit['bio'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-ungu">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/kelola-pengguna.js"></script>
    <?php if ($edit): ?>
        <script>
            new bootstrap.Modal(document.getElementById("modalPengguna")).show();
        </script>
    <?php endif; ?>
</body>

</html>
