<?php
// Baca Ulang: reset progres ke halaman 1 dan status Sedang dibaca (aksi user).
require __DIR__ . '/../../includes/auth_guard.php';
wajib_login();
cek_csrf();

$book_id = (int) ($_POST['book_id'] ?? 0);
$buku = row('SELECT slug FROM books WHERE book_id = ? AND is_active = 1', [$book_id]);

if (!$buku) {
    flash('Buku tidak ditemukan.', 'danger');
    header('Location: ' . url('my-shelf.php'));
    exit;
}

$user_id = (int) akun()['id'];

q(
    'INSERT INTO reading_progress (user_id, book_id, last_page) VALUES (?, ?, 1) AS baru
     ON DUPLICATE KEY UPDATE last_page = 1',
    [$user_id, $book_id]
);

// Reset disengaja: kembalikan ke pengelolaan sistem agar auto-finished bekerja lagi.
q(
    'INSERT INTO collections (user_id, book_id, status, status_source) VALUES (?, ?, \'reading\', \'system\') AS baru
     ON DUPLICATE KEY UPDATE status = \'reading\', status_source = \'system\'',
    [$user_id, $book_id]
);

header('Location: ' . url('read.php?slug=' . urlencode($buku['slug'])));
exit;
