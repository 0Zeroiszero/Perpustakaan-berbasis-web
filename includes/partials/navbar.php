<?php
// Navbar publik bersama. Pemanggil menyetel $aktif ('beranda'|'katalog'|'') dan $cari (ikon cari modal).
$user = akun();
?>
<nav class="navbar navbar-expand-lg border-bottom sticky-top">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
            <span class="logo">P</span> PustakaDigital
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (!empty($cari)): ?>
                <!-- Ikon search (mobile + desktop): membuka panel cari & filter dalam modal -->
                <button type="button" class="catalog-search-icon" data-bs-toggle="modal" data-bs-target="#modalCari"
                    aria-label="Cari buku" title="Cari buku">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            <?php endif; ?>
            <button class="navbar-toggler <?= !empty($cari) ? 'd-lg-none' : '' ?>" type="button" data-bs-toggle="collapse"
                data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false"
                aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($aktif ?? '') === 'beranda' ? 'active' : '' ?>" href="<?= url('index.php') ?>"
                        <?= ($aktif ?? '') === 'beranda' ? 'aria-current="page"' : '' ?>>Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($aktif ?? '') === 'katalog' ? 'active' : '' ?>" href="<?= url('catalog.php') ?>"
                        <?= ($aktif ?? '') === 'katalog' ? 'aria-current="page"' : '' ?>>Katalog</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <?php if ($user): ?>
                        <div class="dropdown">
                            <button type="button" class="btn btn-akun dropdown-toggle" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <span class="avatar" aria-hidden="true">
                                    <?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
                                </span>
                                <span class="nama-akun"><?= e($user['nama']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?= dashboard_url() ?>">
                                        <i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= url('my-shelf.php') ?>">
                                        <i class="bi bi-bookmark me-2" aria-hidden="true"></i>Rak Saya
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item logout" href="<?= url('logout.php') ?>">
                                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('login.php') ?>" class="btn btn-ungu">Masuk</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>
