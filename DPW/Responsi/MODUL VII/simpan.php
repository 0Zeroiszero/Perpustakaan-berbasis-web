<?php
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Akses tidak valid.');
if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) die('Upload gagal.');

$mime = mime_content_type($_FILES['gambar']['tmp_name']);
if (!str_starts_with($mime, 'image/')) die('File harus gambar.');

$st = $conn->prepare('INSERT INTO berita (judul, paragraf, gambar) VALUES (?, ?, ?)');
$gambar = file_get_contents($_FILES['gambar']['tmp_name']);
$st->bind_param('sss', $_POST['judul'], $_POST['paragraf'], $gambar);
if (!$st->execute()) die('Gagal simpan: ' . $st->error);

header('Location: index.php');
