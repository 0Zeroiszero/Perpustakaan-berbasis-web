<?php
// Landing khusus tamu: sampul koleksi di kiri, registrasi singkat di kanan.
// User yang sudah login langsung ke katalog (shell dashboard).
require __DIR__ . '/../includes/auth_guard.php';

if (akun()) {
    header('Location: ' . url('catalog.php'));
    exit;
}

$buku = rows(
    'SELECT title, slug, cover_path, author_name FROM v_books_catalog
     WHERE is_active = 1 ORDER BY book_id DESC LIMIT 3'
);

$judul = 'PustakaDigital';
$css = ['index.css'];
$aktif = 'beranda';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<main class="landing">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-7">
                <span class="badge-soft">✦ Gratis untuk pelajar Indonesia</span>
                <h1 class="mt-3">Baca Lebih <span>Pintar,</span><br>Bukan Lebih Lama.</h1>
                <p class="text-secondary mb-3">
                    Akses koleksi buku digital dalam satu perpustakaan yang cepat dan mudah digunakan.
                </p>
                <form class="search-box d-flex mb-3" method="get" action="<?= url('catalog.php') ?>">
                    <label class="visually-hidden" for="cariLanding">Cari buku atau penulis</label>
                    <input id="cariLanding" name="q" type="search" class="form-control"
                        placeholder="Cari buku atau penulis...">
                    <button class="btn btn-ungu px-4">Cari</button>
                </form>

                <div class="landing-books" aria-label="Sebagian koleksi terbaru">
                    <?php foreach ($buku as $b): ?>
                        <a class="landing-book" href="<?= url('book-detail.php?slug=' . urlencode($b['slug'])) ?>"
                            title="<?= e($b['title']) ?> — <?= e($b['author_name']) ?>">
                            <span class="landing-cover">
                                <img src="<?= url(e($b['cover_path'])) ?>" alt="<?= e($b['title']) ?>" loading="lazy">
                            </span>
                            <span class="landing-title"><?= e($b['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5 landing-register">
                <div class="register-card">
                    <h2 class="h4 fw-bold mb-1">Mulai membaca gratis</h2>
                    <p class="text-secondary small mb-3">Buat akun untuk menyimpan rak dan melanjutkan bacaan.</p>
                    <form method="post" action="<?= url('register.php') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label fw-semibold" for="regNama">Nama tampilan</label>
                            <input id="regNama" name="display_name" type="text" class="form-control"
                                placeholder="Nama kamu" maxlength="100" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold" for="regEmail">Email</label>
                            <input id="regEmail" name="email" type="email" class="form-control"
                                placeholder="nama@email.com" autocomplete="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="regPassword">Password</label>
                            <input id="regPassword" name="password" type="password" class="form-control"
                                placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-ungu w-100 py-2">Daftar Sekarang</button>
                    </form>
                    <p class="text-center text-secondary small mt-3 mb-0">
                        Sudah punya akun? <a href="<?= url('login.php') ?>" class="text-ungu fw-bold">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
