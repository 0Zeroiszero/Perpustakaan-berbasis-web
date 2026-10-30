<?php
require __DIR__ . '/_auth.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rak-saya.php');
    exit;
}

cek_csrf();

$user_id = (string) akun()['id'];
$book_id = trim((string) ($_POST['book_id'] ?? ''));
$book_id = preg_match('/^[A-Za-z0-9]{1,10}$/', $book_id) ? $book_id : '';
$aksi = $_POST['aksi'] ?? '';

$status_valid = ['Akan dibaca', 'Sedang dibaca', 'Telah dibaca', 'Tidak selesai'];
$status = in_array($_POST['status'] ?? '', $status_valid, true) ? $_POST['status'] : 'Akan dibaca';

if ($aksi === 'simpan') {
    $buku = row("SELECT judul FROM ebook WHERE id_book = ?", [$book_id]);

    if (!$buku) {
        flash('Buku tidak ditemukan.', 'danger');
    } else {
        $lama = row(
            "SELECT progress_percent FROM reading_progress WHERE user_id_user = ? AND book_id_book = ?",
            [$user_id, $book_id]
        );

        // Progres mengikuti status supaya badge dan progress bar di rak tidak bertentangan.
        $progres = $lama ? (float) $lama['progress_percent'] : 0;
        if ($status === 'Akan dibaca') {
            $progres = 0;
        } elseif ($status === 'Telah dibaca') {
            $progres = 100;
        } elseif ($status === 'Sedang dibaca') {
            $progres = $progres <= 0 ? 1 : min($progres, 99);
        } elseif ($status === 'Tidak selesai') {
            $progres = min($progres, 99);
        }

        $hari_ini = $status === 'Akan dibaca' ? null : date('Y-m-d');
        $mulai = $status === 'Akan dibaca' ? null : date('Y-m-d');
        $selesai = $status === 'Telah dibaca' ? date('Y-m-d') : null;

        if ($lama) {
            q(
                "UPDATE reading_progress
                 SET status_baca = ?, progress_percent = ?, last_read = ?,
                     start_date = COALESCE(start_date, ?), end_date = ?
                 WHERE user_id_user = ? AND book_id_book = ?",
                [$status, $progres, $hari_ini, $mulai, $selesai, $user_id, $book_id]
            );
            flash('Status buku "' . $buku['judul'] . '" diubah menjadi "' . $status . '".');
        } else {
            // shortcut: pad 3 digit seperti seed; > 999 baris merusak pad → order by DESC tak lagi akurat id terakhir.
            $terakhir = row("SELECT id_progress FROM reading_progress ORDER BY id_progress DESC LIMIT 1");
            $id_progress = 'PRG' . str_pad((int) substr((string) ($terakhir['id_progress'] ?? ''), 3) + 1, 3, '0', STR_PAD_LEFT);
            q(
                "INSERT INTO reading_progress
                     (id_progress, user_id_user, book_id_book, progress_percent, last_read, start_date, end_date, status_baca)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$id_progress, $user_id, $book_id, $progres, $hari_ini, $mulai, $selesai, $status]
            );
            flash('Buku "' . $buku['judul'] . '" disimpan ke rak dengan status "' . $status . '".');
        }
    }
} elseif ($aksi === 'hapus') {
    $buku = row("SELECT judul FROM ebook WHERE id_book = ?", [$book_id]);
    q("DELETE FROM reading_progress WHERE user_id_user = ? AND book_id_book = ?", [$user_id, $book_id]);
    flash('Buku "' . ($buku['judul'] ?? '') . '" dihapus dari rak.');
} else {
    flash('Aksi tidak dikenali.', 'danger');
}

header('Location: rak-saya.php');
exit;
