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

// Clamp ke rentang berkas nyata. page_count 0 (PDF ber-object-stream yang tak terhitung
// regex) memakai total_pages dari PDF.js sebagai batas sanity, bukan batas keamanan.
$batas = (int) $buku['page_count'];
if ($batas < 1) {
    $batas = max(1, min(100000, (int) ($_POST['total_pages'] ?? 1)));
}
$last_page = max(1, min($last_page, $batas));

$user_id = (int) akun()['id'];
$koleksi = row(
    'SELECT status, status_source FROM collections WHERE user_id = ? AND book_id = ?',
    [$user_id, $book_id]
);

// Status awal hanya dibuat sistem bila user belum punya status sama sekali.
// Request pertama TIDAK langsung finished agar klik pertama Baca Buku = "Sedang dibaca".
if (!$koleksi) {
    q(
        'INSERT INTO collections (user_id, book_id, status, status_source) VALUES (?, ?, \'reading\', \'system\')',
        [$user_id, $book_id]
    );
} elseif ($koleksi['status_source'] === 'system' && $last_page >= $batas && $koleksi['status'] !== 'finished') {
    // Baris yang masih dikelola sistem otomatis selesai saat halaman terakhir tercapai.
    q(
        'UPDATE collections SET status = \'finished\' WHERE user_id = ? AND book_id = ? AND status_source = \'system\'',
        [$user_id, $book_id]
    );
}

q(
    'INSERT INTO reading_progress (user_id, book_id, last_page) VALUES (?, ?, ?) AS baru
     ON DUPLICATE KEY UPDATE last_page = baru.last_page',
    [$user_id, $book_id, $last_page]
);

echo 'OK';
