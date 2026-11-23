# SPEC-08 — Master penerbit & penulis (admin)

## Kontrak
- `admin/publishers.php`: daftar + tambah/edit (name, city); POST sendiri + CSRF + redirect + flash.
- `admin/authors.php`: daftar + tambah/edit (name, slug otomatis, country, bio); slug via generate_unique_slug().
- Tanpa hapus baris master (tombol hapus tidak ada).

## Kriteria penerimaan
1. Admin menambah penerbit/penulis → muncul di daftar & opsi form buku.
2. Slug bentrok otomatis berakhiran angka; slug tidak bisa diisi manual.
3. Member membuka admin/* → dilempar dashboard-user; tanpa CSRF → 419.
4. Nama kosong ditolak dengan invalid-feedback.
