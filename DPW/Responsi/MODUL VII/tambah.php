<?php require 'koneksi.php'; ?>
<h1>Tambah Berita</h1>
<form method="post" action="simpan.php" enctype="multipart/form-data">
    <p><label>Judul<br><input type="text" name="judul" required></label></p>
    <p><label>Paragraf<br><textarea name="paragraf" rows="5" required></textarea></label></p>
    <p><label>Gambar<br><input type="file" name="gambar" accept="image/*" required></label></p>
    <button type="submit">Simpan</button>
</form>
<a href="index.php">Kembali</a>
