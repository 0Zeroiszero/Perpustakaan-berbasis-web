<?php
// Topbar untuk halaman yang memakai shell sidebar (setelah login).
// Pemanggil menyetel $judulTopbar, $subTopbar, dan opsional $cari (ikon modal cari).
$user = akun();
?>
<header class="topbar p-3">
    <div class="d-flex align-items-center gap-3">
        <button type="button" id="mobileMenu" class="btn btn-light mobile-menu" aria-label="Buka menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div>
            <b class="topbar-title"><?= e($judulTopbar ?? '') ?></b>
            <div class="small text-secondary topbar-sub"><?= e($subTopbar ?? '') ?></div>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (!empty($cari)): ?>
                <button type="button" class="catalog-search-icon" data-bs-toggle="modal" data-bs-target="#modalCari"
                    aria-label="Cari buku" title="Cari buku">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            <?php endif; ?>
            <div class="dropdown">
                <button type="button" class="btn btn-akun dropdown-toggle" data-bs-toggle="dropdown"
                    aria-expanded="false" aria-label="Menu akun <?= e($user['nama']) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?></span>
                    <span class="nama-akun"><?= e($user['nama']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="dropdown-header">
                            <b class="d-block text-dark"><?= e($user['nama']) ?></b>
                            <span class="small text-secondary"><?= e(ucfirst($user['role'])) ?></span>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= url('profile.php') ?>">
                            <i class="bi bi-person me-2" aria-hidden="true"></i>Profil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= dashboard_url() ?>">
                            <i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard
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
        </div>
    </div>
</header>
