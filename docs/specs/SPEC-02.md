# SPEC-02 — Detail buku & profil penulis

## Kontrak
- `public/book-detail.php?slug=`: sampul, judul, badge genre, sinopsis, `<dl class="row row-cols-2 row-cols-md-3 g-3">` berisi Penulis, Kategori, Tahun, Halaman, ISBN, Format(PDF); aksi Baca Buku (`.btn-baca`, sebelum dropdown, `d-grid gap-2 d-sm-flex`) → `read.php?slug=`; dropdown status.
- `public/author.php?slug=`: bio, negara asal, daftar karya (grid buku).
- Slug tidak dikenal → 404 ramah; buku nonaktif → 404.

## Kriteria penerimaan
1. 0 JOIN di PHP (SELECT dari v_books_catalog).
2. Tamu 200; Baca Buku tetap terlihat, gerbang login terjadi di read.php.
3. Slug salah → 404; tidak ada bocor data buku nonaktif.
4. Layout dl 2 kolom ponsel / 3 kolom ≥768px; tanpa overflow 320px.
