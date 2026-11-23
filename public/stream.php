<?php
// Aliran berkas PDF dari storage/ (di luar public). 403 tanpa sesi, 404 buku tak dikenal/nonaktif.
require __DIR__ . '/../includes/auth_guard.php';
header('Cache-Control: no-store');

if (!akun()) {
    http_response_code(403);
    exit('Forbidden: masuk dulu untuk membaca buku ini.');
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$buku = preg_match('/^[a-z0-9-]{1,220}$/', $slug)
    ? row('SELECT book_id, storage_path FROM v_books_catalog WHERE slug = ? AND is_active = 1', [$slug])
    : null;

if (!$buku) {
    http_response_code(404);
    exit('Buku tidak ditemukan.');
}

$path = realpath(__DIR__ . '/../' . $buku['storage_path']);
$awal = realpath(STORAGE_DIR);

// Nama berkas hanya {id}.pdf dari DB; realpath memastikan tidak keluar dari storage.
if ($path === false || $awal === false || !str_starts_with($path, $awal . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit('Berkas tidak ditemukan.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . (int) $buku['book_id'] . '.pdf"');
readfile($path);
