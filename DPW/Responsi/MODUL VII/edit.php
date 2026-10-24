<?php
require 'koneksi.php';

$id = (int)($_REQUEST['id'] ?? 0);
if (!$id) die('ID tidak valid.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
        die('Upload gambar baru gagal (harus diganti saat edit).');
    }
    $mime = mime_content_type($_FILES['gambar']['tmp_name']);
    if (!str_starts_with($mime, 'image/')) die('File harus gambar.');

    $st = $conn->prepare('UPDATE berita SET judul = ?, paragraf = ?, gambar = ? WHERE id = ?');
    $gambar = file_get_contents($_FILES['gambar']['tmp_name']);
    $st->bind_param('sssi', $_POST['judul'], $_POST['paragraf'], $gambar, $id);
    $st->execute();

    header('Location: index.php?id=' . $id);
    exit;
}

$st = $conn->prepare('SELECT judul, paragraf, gambar FROM berita WHERE id = ?');
$st->bind_param('i', $id);
$st->execute();
$b = $st->get_result()->fetch_assoc();
if (!$b) die('Berita tidak ditemukan.');

$fi = new finfo(FILEINFO_MIME_TYPE);
$mime = $fi->buffer($b['gambar']) ?: 'image/jpeg';
$src = 'data:' . $mime . ';base64,' . base64_encode($b['gambar']);
?>
<h1>Edit Berita</h1>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $id ?>">
    <p><label>Judul<br><input type="text" name="judul" value="<?= htmlspecialchars($b['judul']) ?>" required></label></p>
    <p><label>Paragraf<br><textarea name="paragraf" rows="5" required><?= htmlspecialchars($b['paragraf']) ?></textarea></label></p>
    <p>Berita saat ini:<br><img src="<?= $src ?>" alt="Gambar berita" width="200"><br>
    <label>Gambar baru<br><input type="file" name="gambar" accept="image/*" required></label></p>
    <button type="submit">Simpan Edit</button>
</form>
<a href="index.php">Kembali</a>
