# SPEC-DB — Fondasi database, berkas, dan helper

## Kontrak
- `database/optl_pustaka.sql`: CREATE DATABASE `optl_pustaka` + 7 tabel 3NF + VIEW `v_books_catalog` + seed, ramah phpMyAdmin (InnoDB, utf8mb4, PK INT UNSIGNED AUTO_INCREMENT).
- Tabel/kolom persis §3 plan; `collections` & `reading_progress` UNIQUE(user_id, book_id); FK RESTRICT; `books.isbn` dipertahankan.
- `includes/config.php` (BASE_URL, DB, ASSET_VERSION, path storage/cover/font, `url()`, `asset()`), `db.php` (mysqli `q/rows/row/scalar/e` pola lama), `auth_guard.php`, `slug_helper.php` (`generate_unique_slug()`), `cover_helper.php` (`generate_default_cover()` GD 600×800 meniru `cover/bk001.jpg`), `book_helper.php` (peta status + dropdown).
- Berkas: 7 PDF lama + 4 PDF placeholder → `storage/ebooks/{id}.pdf`; 10 cover → `public/uploads/covers/{id}.jpg`; font Inter TTF dibundel.

## Kriteria penerimaan
1. Impor phpMyAdmin sukses; 7 tabel + 1 VIEW; seed 10 users/10 books/10 authors/10 genres/10 collections.
2. `password_verify('admin123', hash seed)` true; role admin/member; is_active=1.
3. `page_count` = jumlah halaman berkas nyata; checksum_sha256 & file_size cocok berkas.
4. `generate_unique_slug('Laskar Pelangi')` = `laskar-pelangi`; bentrok → `-2`.
5. `generate_default_cover()` menghasilkan JPG 600×800 bố cục referensi (header, garis, judul, penulis, genre, PDF).
6. Semua query lewat prepared statement; tidak ada interpolasi nilai ke SQL.
