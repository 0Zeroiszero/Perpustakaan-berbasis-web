# SPEC-05 — Pembaca PDF.js & streaming aman

## Kontrak
- `public/read.php?slug=` (login): PDF.js CDN (pola Bootstrap), toolbar merek (kembali ke detail, prev/next, input halaman, zoom), canvas render; buku nonaktif → 404.
- `public/stream.php?slug=` (login): 403 tanpa sesi; validasi slug + is_active; `readfile()` storage_path dengan Content-Type application/pdf + Content-Length; nama berkas hanya `{id}.pdf`.

## Kriteria penerimaan
1. Tamu stream.php → 403; member → 200 application/pdf; buku nonaktif → 404/403.
2. `storage/ebooks/1.pdf` tidak terakses lewat URL publik mana pun.
3. `?slug=../../` atau slug aneh → 404, tidak ada path traversal.
4. PDF.js merender PDF asli & placeholder; kontrol halaman berfungsi.
