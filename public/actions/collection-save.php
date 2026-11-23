<?php
// Simpan/ubah status buku di Rak Saya (POST dari dropdown katalog/detail/author).
require __DIR__ . '/../../includes/book_helper.php';
wajib_login();
cek_csrf();

$book_id = (int) ($_POST['book_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$kembali = url_kembali(url('catalog.php'));

if (!isset(STATUS_LABEL[$status]) || !row('SELECT book_id FROM books WHERE book_id = ? AND is_active = 1', [$book_id])) {
    flash('Status atau buku tidak valid.', 'danger');
    header('Location: ' . $kembali);
    exit;
}

q(
    'INSERT INTO collections (user_id, book_id, status, status_source) VALUES (?, ?, ?, \'user\') AS baru
     ON DUPLICATE KEY UPDATE status = baru.status, status_source = \'user\'',
    [akun()['id'], $book_id, $status]
);

flash('Buku disimpan ke Rak Saya: ' . STATUS_LABEL[$status] . '.');
header('Location: ' . $kembali);
exit;
