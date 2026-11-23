# SPEC-09 — Admin buku, sampul GD, dashboard admin

## Kontrak
- `admin/books.php`: daftar buku + tombol Nonaktifkan/Aktifkan (is_active) via POST + konfirmasiHapus (teks disesuaikan); tanpa hapus keras.
- `admin/book-form.php`: tambah/edit (judul, penulis/penerbit/genre select DB-driven, tahun, isbn, sinopsis, halaman, upload PDF, sampul kustom). Validasi PDF: ekstensi + MIME application/pdf, maks 50MB; sampul JPG/PNG/WEBP/GIF maks 5MB (getimagesize). Tanpa upload saat edit → berkas lama tetap. Buku baru tanpa sampul kustom → generate_default_cover(). Slug otomatis.
- `dashboard-admin.php`: statistik COUNT + buku terbaru + tautan kelola.

## Kriteria penerimaan
1. Upload PDF palsu (ekstensi .pdf isi teks) ditolak; PDF asli diterima → storage/ebooks/{id}.pdf + metadata terhitung.
2. Buku baru tanpa sampul → {id}-default.jpg terbentuk 600×800.
3. Nonaktif → hilang dari katalog & stream diblokir; koleksi/progres pengguna tetap utuh; Aktifkan mengembalikan.
4. ISBN duplikat ditolak; tahun/halaman numerik wajar.
