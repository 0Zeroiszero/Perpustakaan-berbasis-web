<?php
// Footer bersama: tanpa informasi layanan fisik (koleksi digital, akses kapan saja).
?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5 col-md-6">
                <a href="<?= url('index.php') ?>" class="footer-brand">
                    <span class="footer-logo">P</span>
                    <span>PustakaDigital</span>
                </a>
                <p class="footer-description mt-3 mb-0">
                    Perpustakaan digital untuk menemukan, membaca, dan mengelola koleksi buku dengan mudah.
                </p>
            </div>
            <div class="col-6 col-lg-3 col-md-3">
                <h2 class="footer-title h6">Navigasi</h2>
                <a href="<?= url('index.php') ?>" class="footer-link">Beranda</a>
                <a href="<?= url('catalog.php') ?>" class="footer-link">Katalog Buku</a>
            </div>
            <div class="col-6 col-lg-4 col-md-3">
                <h2 class="footer-title h6">Informasi</h2>
                <span class="footer-info"><i class="bi bi-envelope me-2" aria-hidden="true"></i>info@pustakadigital.id</span>
                <div class="footer-social mt-3">
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="#" aria-label="YouTube"><i class="bi bi-youtube" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© Dibuat oleh Kelompok 5 menggunakan Bootstrap.</span>
            <span>Dibuat untuk kebutuhan pembelajaran.</span>
        </div>
    </div>
</footer>
