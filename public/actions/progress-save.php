<?php
// Terima auto-save last_page dari iframe progressFrame di read.php (POST, bukan AJAX).
require __DIR__ . '/../../includes/auth_guard.php';
header('Cache-Control: no-store');

if (!akun()) {
    http_response_code(403);
    exit('Forbidden.');
}
cek_csrf();

$book_id = (int) ($_POST['book_id'] ?? 0);
$last_page = (int) ($_POST['last_page'] ?? 1);

$buku = row('SELECT page_count FROM books WHERE book_id = ? AND is_active = 1', [$book_id]);
if (!$buku) {
    http_response_code(404);
    exit('Buku tidak ditemukan.');
}

// Clamp ke rentang berkas nyata: tidak mungkin melebihi jumlah halaman.
$last_page = max(1, min($last_page, (int) $buku['page_count']));

q(
    'INSERT INTO reading_progress (user_id, book_id, last_page) VALUES (?, ?, ?) AS baru
     ON DUPLICATE KEY UPDATE last_page = baru.last_page',
    [akun()['id'], $book_id, $last_page]
);

echo 'OK';
