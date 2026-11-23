# SPEC-07 — Rak Saya & dashboard user

## Kontrak
- `public/my-shelf.php?status=` (login): 4 tab status (to_read/reading/not_interested/finished) server-side; kartu badge + CTA sesuai kontrak (selesai: btn-ungu "Buku Selanjutnya" + outline "Baca Ulang"; sedang: "Lanjut Baca"; lain: "Baca Buku"); toolbar + modal filter tetap UI-only; hapus item → actions/collection-delete.php + konfirmasiHapus.
- `actions/collection-save.php`: simpan/ubah status (4 nilai valid), UNIQUE(user,book), milik sesi.
- `actions/collection-delete.php`: hanya milik sesi (WHERE user_id sesi).
- `dashboard-user.php`: statistik COUNT (rak, sedang dibaca, selesai) + aktivitas terbaru dari collections/reading_progress.

## Kriteria penerimaan
1. Simpan dari katalog/detail muncul di rak tab yang benar; ganti status memindah tab.
2. Hapus item orang lain ditolak; hapus sendiri sukses + flash.
3. Status tak valid ditolak; duplikat tidak membuat baris ganda.
4. Angka dashboard = hasil COUNT tabel, bukan dummy.
