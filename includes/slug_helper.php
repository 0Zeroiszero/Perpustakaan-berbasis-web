<?php
// Slug ramah manusia untuk identitas publik (books & authors).
require_once __DIR__ . '/db.php';

// Nama tabel hanya boleh dari kode internal (books/authors), bukan input user.
function generate_unique_slug(string $teks, string $tabel, ?int $kecuali_id = null): string
{
    $dasar = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($teks))), '-');
    if ($dasar === '') {
        $dasar = 'item';
    }

    $pk = substr($tabel, 0, -1) . '_id'; // books -> book_id, authors -> author_id
    $slug = $dasar;
    $i = 2;

    while (true) {
        $sql = "SELECT COUNT(*) FROM `$tabel` WHERE slug = ?";
        $params = [$slug];
        if ($kecuali_id !== null) {
            $sql .= " AND `$pk` <> ?";
            $params[] = $kecuali_id;
        }
        if ((int) scalar($sql, $params) === 0) {
            return $slug;
        }
        $slug = $dasar . '-' . $i++;
    }
}
