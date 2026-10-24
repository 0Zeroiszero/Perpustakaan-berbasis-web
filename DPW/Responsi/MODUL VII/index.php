<?php
require 'koneksi.php';

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $st = $conn->prepare('SELECT judul, paragraf, gambar FROM berita WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $b = $st->get_result()->fetch_assoc();
    if (!$b) die('Berita tidak ditemukan.');

    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->buffer($b['gambar']) ?: 'image/jpeg';
    $src = 'data:' . $mime . ';base64,' . base64_encode($b['gambar']);
    $judul = htmlspecialchars($b['judul']);
    echo '<h1>', $judul, '</h1>',
         '<img src="', $src, '" alt="', $judul, '"><br>',
         '<p>', nl2br(htmlspecialchars($b['paragraf'])), '</p>',
         '<a href="edit.php?id=', $id, '">Edit</a> ',
         '<form method="post" action="delete.php" style="display:inline">',
         '<input type="hidden" name="id" value="', $id, '">',
         '<button type="submit">Hapus</button></form> ',
         '<a href="index.php">Kembali</a>',
         ' | <a href="tambah.php">Tambah Berita</a>';
} else {
    echo '<h1>Daftar Berita</h1><a href="tambah.php">Tambah Berita</a><ul>';
    $r = $conn->query('SELECT id, judul FROM berita ORDER BY id DESC');
    if ($r->num_rows === 0) echo '<li>Belum ada berita</li>';
    while ($row = $r->fetch_assoc()) {
        echo '<li><a href="index.php?id=', (int)$row['id'], '">',
             htmlspecialchars($row['judul']), '</a></li>';
    }
    echo '</ul>';
}
