<?php
// Peta status rak (nilai DB => label UI Indonesia) dan renderer dropdown status
// yang dipakai bersama oleh katalog, detail buku, dan rak saya.
require_once __DIR__ . '/auth_guard.php';

const STATUS_LABEL = [
    'to_read' => 'Akan dibaca',
    'reading' => 'Sedang dibaca',
    'finished' => 'Telah dibaca',
    'not_interested' => 'Tidak Tertarik',
];

const STATUS_BTN = [
    'to_read' => 'btn-pinjam',
    'reading' => 'btn-rak-sedang',
    'finished' => 'btn-rak-selesai',
    'not_interested' => 'btn-rak-gagal',
];

function status_label(?string $status): string
{
    return STATUS_LABEL[$status] ?? 'Tambah ke rak';
}

function status_btn(?string $status): string
{
    return STATUS_BTN[$status] ?? 'btn-rak-netral';
}

// Status rak satu buku milik akun yang sedang login (null bila tamu/belum ada).
function rak_status(int $book_id): ?string
{
    $user = akun();
    if (!$user) {
        return null;
    }

    $r = row('SELECT status FROM collections WHERE user_id = ? AND book_id = ?', [$user['id'], $book_id]);
    return $r['status'] ?? null;
}

// Peta book_id => status untuk satu halaman penuh (hindari query per kartu).
function rak_semua(): array
{
    $user = akun();
    if (!$user) {
        return [];
    }

    $peta = [];
    foreach (rows('SELECT book_id, status FROM collections WHERE user_id = ?', [$user['id']]) as $r) {
        $peta[(int) $r['book_id']] = $r['status'];
    }
    return $peta;
}

// Dropdown Bootstrap: memilih opsi langsung POST ke actions/collection-save.php.
function dropdown_status(int $book_id, ?string $status): string
{
    $html = '<form method="post" action="' . url('actions/collection-save.php') . '" class="dropdown">'
        . csrf_field()
        . '<input type="hidden" name="book_id" value="' . $book_id . '">'
        . '<button type="button" class="btn ' . status_btn($status) . ' w-100 dropdown-toggle"'
        . ' data-bs-toggle="dropdown" aria-expanded="false"><span>' . e(status_label($status)) . '</span></button>'
        . '<ul class="dropdown-menu w-100">';

    foreach (STATUS_LABEL as $nilai => $label) {
        $html .= '<li><button type="submit" class="dropdown-item" name="status" value="' . $nilai . '">'
            . e($label) . '</button></li>';
    }

    return $html . '</ul></form>';
}
