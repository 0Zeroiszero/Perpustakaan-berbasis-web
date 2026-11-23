<?php
// Sidebar dashboard bersama (user & admin). Pemanggil menyetel $aktif dan $admin (bool).
?>
<aside id="sidebar" class="sidebar">
    <a class="navbar-brand d-flex align-items-center gap-2 mb-4 px-2" href="<?= url('index.php') ?>">
        <span class="logo">P</span>
        PustakaDigital
    </a>
    <nav class="nav flex-column">
        <a class="nav-link <?= ($aktif ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= dashboard_url() ?>"
            <?= ($aktif ?? '') === 'dashboard' ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-grid me-2" aria-hidden="true"></i>
            Dashboard
        </a>
        <?php if (!empty($admin)): ?>
            <a class="nav-link <?= ($aktif ?? '') === 'books' ? 'active' : '' ?>" href="<?= url('admin/books.php') ?>">
                <i class="bi bi-journal-bookmark me-2" aria-hidden="true"></i>
                Kelola Buku
            </a>
            <a class="nav-link <?= ($aktif ?? '') === 'users' ? 'active' : '' ?>" href="<?= url('admin/users.php') ?>">
                <i class="bi bi-people me-2" aria-hidden="true"></i>
                Kelola Pengguna
            </a>
            <a class="nav-link <?= ($aktif ?? '') === 'masters' ? 'active' : '' ?>" href="<?= url('admin/publishers.php') ?>">
                <i class="bi bi-building me-2" aria-hidden="true"></i>
                Master Data
            </a>
        <?php endif; ?>
        <a class="nav-link <?= ($aktif ?? '') === 'katalog' ? 'active' : '' ?>" href="<?= url('catalog.php') ?>">
            <i class="bi bi-book me-2" aria-hidden="true"></i>
            Katalog
        </a>
        <?php if (empty($admin)): ?>
            <a class="nav-link <?= ($aktif ?? '') === 'rak' ? 'active' : '' ?>" href="<?= url('my-shelf.php') ?>"
                <?= ($aktif ?? '') === 'rak' ? 'aria-current="page"' : '' ?>>
                <i class="bi bi-bookmark me-2" aria-hidden="true"></i>
                Rak Saya
            </a>
        <?php endif; ?>
        <a class="nav-link" href="<?= url('index.php') ?>">
            <i class="bi bi-house me-2" aria-hidden="true"></i>
            Beranda
        </a>
        <a class="nav-link logout" href="<?= url('logout.php') ?>">
            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>
            Keluar
        </a>
    </nav>
</aside>
