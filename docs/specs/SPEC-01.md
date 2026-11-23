# SPEC-01 — Beranda & katalog publik

## Kontrak
- `public/index.php`: beranda kontrak lama (hero, tepat 2 pintu masuk Katalog & Rak Saya, cari → `catalog.php?q=`, tautan genre populer). Navbar akun untuk sesi valid.
- `public/catalog.php?q=&genre_id=&sort=`: modal cari navbar (`#modalCari`, fokus via shown.bs.modal, Enter submit), pil genre DB-driven, urutan, `.grid-buku` col-6 col-lg-3, kartu + dropdown status 4 nilai (POST actions/collection-save.php).
- Aset lama dipindah utuh ke `public/assets/` (root.css byte-identik); partial head/navbar/footer bersama.

## Kriteria penerimaan
1. Tamu & member: 200; tepat satu h1; hirarki heading berurutan.
2. `?q=` menyaring judul/penulis; `?genre_id=` memfilter; hasil bisa dibagikan lewat URL.
3. Dropdown status menampilkan label+warna sesuai collections milik sesi; tamu melihat "Tambah ke rak".
4. 320/375/768/1200px: tanpa overflow horizontal; grid 2 kolom ponsel / 4 laptop.
5. Tidak ada AJAX; pencarian modal tanpa tombol Cari.
