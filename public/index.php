<?php
// Beranda: dua pintu masuk (Katalog & Rak Saya); pencarian diteruskan ke katalog.
require __DIR__ . '/../includes/auth_guard.php';

$unggulan = rows(
    'SELECT title, author_name FROM v_books_catalog WHERE is_active = 1 ORDER BY book_id DESC LIMIT 3'
);

$judul = 'PustakaDigital';
$css = ['index.css'];
$aktif = 'beranda';
require __DIR__ . '/../includes/partials/head.php';
require __DIR__ . '/../includes/partials/navbar.php';
?>

<main>
    <section class="hero">
        <div class="container">
            <div class="row align-items-center gy-5 gx-lg-5">
                <div class="col-lg-7">
                    <span class="badge-soft">
                        ✦ Gratis untuk pelajar Indonesia
                    </span>
                    <h1 class="mt-4">
                        Baca Lebih
                        <span>Pintar,</span>
                        <br>
                        Bukan Lebih Lama.
                    </h1>
                    <p class="mt-4">
                        Akses seluruh koleksi buku digital dalam satu
                        perpustakaan yang cepat dan mudah digunakan.
                    </p>
                    <form id="searchForm" class="search-box d-flex mt-4">
                        <input type="search" name="q" class="form-control" placeholder="Cari buku atau penulis..."
                            aria-label="Cari buku atau penulis">
                        <button class="btn btn-ungu px-4">
                            Cari
                        </button>
                    </form>
                    <p class="small mt-3">
                        Populer:
                        <a href="<?= url('catalog.php?kategori=Teknologi') ?>" class="text-ungu fw-bold">Teknologi</a>,
                        <a href="<?= url('catalog.php?kategori=Novel') ?>" class="text-ungu fw-bold">Novel</a>,
                        <a href="<?= url('catalog.php?kategori=Sejarah') ?>" class="text-ungu fw-bold">Sejarah</a>
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="card-clean p-4">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-ungu" aria-hidden="true">
                                ●
                            </span>
                            <span class="text-secondary">
                                PustakaDigital / Rak Saya
                            </span>
                        </div>
                        <?php foreach ($unggulan as $i => $b): ?>
                            <div class="p-3 rounded-4 <?= ['bg-ungu-muda', 'bg-biru-muda', 'bg-light'][$i % 3] ?> mb-3">
                                <b><?= e($b['title']) ?></b>
                                <br>
                                <small class="text-secondary">
                                    <?= e($b['author_name']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="page-title">
                    Jelajahi PustakaDigital
                </h2>
                <p class="text-secondary">
                    Temukan bacaan sesuai kebutuhanmu.
                </p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <a href="<?= url('catalog.php') ?>" class="card-clean entry-card">
                        <span class="entry-icon bg-ungu-muda text-ungu">
                            <i class="bi bi-grid" aria-hidden="true"></i>
                        </span>
                        <h3 class="entry-title h5">
                            Katalog Buku
                        </h3>
                        <p class="text-secondary">
                            Cari dan lihat seluruh koleksi buku yang tersedia.
                        </p>
                    </a>
                </div>
                <div class="col-md-6 col-lg-5">
                    <a href="<?= url('my-shelf.php') ?>" class="card-clean entry-card">
                        <span class="entry-icon bg-biru-muda text-biru">
                            <i class="bi bi-bookmark" aria-hidden="true"></i>
                        </span>
                        <h3 class="entry-title h5">
                            Rak Saya
                        </h3>
                        <p class="text-secondary">
                            Simpan buku pilihanmu dan lanjutkan bacaan.
                        </p>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../includes/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/script.js') ?>"></script>
</body>

</html>
