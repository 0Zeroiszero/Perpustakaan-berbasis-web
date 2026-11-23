<?php
// Profil mandiri: hanya display_name, email, dan kata sandi milik sesi sendiri.
require __DIR__ . '/../includes/auth_guard.php';
wajib_login();
header('Cache-Control: no-store');

$id = (int) akun()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $nama = trim(is_string($_POST['display_name'] ?? null) ? $_POST['display_name'] : '');
    $email = strtolower(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $gagal = match (true) {
        $nama === '' || mb_strlen($nama) > 100 => 'nama',
        !filter_var($email, FILTER_VALIDATE_EMAIL) => 'email',
        row('SELECT user_id FROM users WHERE email = ? AND user_id <> ?', [$email, $id]) !== null => 'email',
        $password !== '' && strlen($password) < 6 => 'password',
        default => null,
    };

    if ($gagal !== null) {
        header('Location: ' . url('profile.php?gagal=' . $gagal));
        exit;
    }

    if ($password !== '') {
        q(
            'UPDATE users SET display_name = ?, email = ?, password_hash = ? WHERE user_id = ?',
            [$nama, $email, password_hash($password, PASSWORD_DEFAULT), $id]
        );
    } else {
        q('UPDATE users SET display_name = ?, email = ? WHERE user_id = ?', [$nama, $email, $id]);
    }

    flash('Profil berhasil diperbarui.');
    header('Location: ' . url('profile.php'));
    exit;
}

$user = akun();
$gagal = (string) ($_GET['gagal'] ?? '');

$judul = 'Profil Saya - PustakaDigital';
$css = ['login.css'];
$aktif = '';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <h1 class="page-title page-title-sm mb-3">Profil Saya</h1>
            <?php tampilkan_flash(); ?>
            <div class="card-clean p-4">
                <form method="post" action="<?= url('profile.php') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="display_name">Nama tampilan</label>
                        <input id="display_name" name="display_name" type="text"
                            class="form-control<?= $gagal === 'nama' ? ' is-invalid' : '' ?>"
                            value="<?= e($user['nama']) ?>" maxlength="100" required>
                        <div class="invalid-feedback">Nama wajib diisi (maksimal 100 karakter).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="email">Email</label>
                        <input id="email" name="email" type="email"
                            class="form-control<?= $gagal === 'email' ? ' is-invalid' : '' ?>"
                            value="<?= e($user['email']) ?>" autocomplete="email" required>
                        <div class="invalid-feedback">Email tidak valid atau sudah dipakai akun lain.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password">Password baru</label>
                        <input id="password" name="password" type="password"
                            class="form-control<?= $gagal === 'password' ? ' is-invalid' : '' ?>"
                            placeholder="Kosongkan bila tidak diganti" autocomplete="new-password">
                        <div class="invalid-feedback">Password minimal 6 karakter.</div>
                    </div>

                    <button class="btn btn-ungu w-100 py-2">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
