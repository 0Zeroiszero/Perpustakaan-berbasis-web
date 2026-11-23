<?php
// Sampul bawaan via GD, meniru bentuk cover referensi (cover/bk001.jpg lama):
// latar biru solid, header PUSTAKADIGITAL + garis, judul putih besar,
// penulis biru muda, genre kiri-bawah dan "PDF" kanan-bawah. 600x800 (3:4).
require_once __DIR__ . '/config.php';

function generate_default_cover(int $book_id, string $judul, string $penulis, string $genre): ?string
{
    $fontReguler = FONT_DIR . '/Inter-Regular.ttf';
    $fontTebal = FONT_DIR . '/Inter-Bold.ttf';
    if (!file_exists($fontReguler) || !file_exists($fontTebal)) {
        return null;
    }

    $img = imagecreatetruecolor(600, 800);
    $biru = imagecolorallocate($img, 2, 106, 161);   // #026AA1 sampled dari referensi
    $putih = imagecolorallocate($img, 255, 255, 255);
    $muda = imagecolorallocate($img, 206, 228, 252); // #CEE4FC sampled dari referensi
    imagefill($img, 0, 0, $biru);

    // Header merek + garis tipis
    imagettftext($img, 22, 0, 48, 62, $putih, $fontTebal, 'PUSTAKADIGITAL');
    imagefilledrectangle($img, 48, 80, 552, 82, $putih);

    // Judul: bungkus per kata, maksimal 3 baris agar tidak menimpa baris penulis.
    $baris = [];
    $kini = '';
    foreach (explode(' ', $judul) as $kata) {
        $uji = $kini === '' ? $kata : "$kini $kata";
        $box = imagettfbbox(44, 0, $fontTebal, $uji);
        if ($box[2] - $box[0] > 504 && $kini !== '') {
            $baris[] = $kini;
            $kini = $kata;
        } else {
            $kini = $uji;
        }
    }
    $baris[] = $kini;
    $baris = array_slice($baris, 0, 3);

    // Baris terakhir dipangkas dengan elipsis bila tetap terlalu lebar.
    $akhir = array_pop($baris);
    while ($akhir !== '' && (imagettfbbox(44, 0, $fontTebal, $akhir . '…')[2] > 504)) {
        $akhir = mb_substr($akhir, 0, -1);
    }
    $baris[] = $akhir;

    $y = 210;
    foreach ($baris as $barisJudul) {
        imagettftext($img, 44, 0, 48, $y, $putih, $fontTebal, $barisJudul);
        $y += 58;
    }

    // Penulis & genre dipangkas agar muat dalam lebar sampul (600 - 2*48 = 504px).
    $penulis = mb_strimwidth($penulis, 0, 42, '…');
    $genre = mb_strimwidth($genre, 0, 20, '…');

    imagettftext($img, 24, 0, 48, 390, $muda, $fontReguler, $penulis);

    // Genre kiri-bawah, format kanan-bawah
    imagettftext($img, 20, 0, 48, 728, $putih, $fontReguler, $genre);
    $box = imagettfbbox(20, 0, $fontReguler, 'PDF');
    imagettftext($img, 20, 0, 552 - ($box[2] - $box[0]), 728, $putih, $fontReguler, 'PDF');

    $nama = $book_id . '-default.jpg';
    $ok = imagejpeg($img, COVER_DIR . '/' . $nama, 90);

    return $ok ? $nama : null;
}
