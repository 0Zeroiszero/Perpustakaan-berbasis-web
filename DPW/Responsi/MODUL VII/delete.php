<?php
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Akses tidak valid.');
$id = (int)($_POST['id'] ?? 0);
if (!$id) die('ID tidak valid.');

$st = $conn->prepare('DELETE FROM berita WHERE id = ?');
$st->bind_param('i', $id);
$st->execute();

header('Location: index.php');
