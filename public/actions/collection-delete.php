<?php
// Hapus buku dari Rak Saya milik sesi sendiri (satu-satunya hapus keras yang diizinkan).
require __DIR__ . '/../../includes/auth_guard.php';
wajib_login();
cek_csrf();

$book_id = (int) ($_POST['book_id'] ?? 0);
$stmt = q('DELETE FROM collections WHERE user_id = ? AND book_id = ?', [akun()['id'], $book_id]);

if ($stmt->affected_rows > 0) {
    flash('Buku dihapus dari rak.');
} else {
    flash('Buku itu tidak ada di rakmu.', 'danger');
}

header('Location: ' . url_kembali(url('my-shelf.php')));
exit;
