# RENCANA ROMBAK TOTAL — PustakaDigital (`optl/rombak-total`)

Tanggal: 2026-11-23 · Status: DISETUJUI lewat grilling ronde 1–2 · Eksekusi: solo (tanpa anggota lain), menunggu switch agent.
Salinan repo: langkah 0 menyalin berkas ini ke `docs/RENCANA-ROMBAK-TOTAL.md`.

## 1. Intent & batasan

Rombak total struktur folder, arsitektur, dan basis data proyek perpustakaan digital mengikuti **Bab 9 PDF `docs/Analisis-REA-Perpustakaan-Digital_1.pdf`** (Proposal Revisi Final: 7 tabel 3NF + VIEW), sambil **mempertahankan kontrak UI lama 100%** (token warna, komponen, label Indonesia, aksesibilitas) dengan penyesuaian seperlunya. Kode harus clean & terstruktur karena akan dipakai tanya jawab / live coding kuliah. Bukan produksi; migrasi URL penuh **tanpa redirect**.

### Keputusan pengguna (mengikat, menyimpang dari PDF bila disebut)
1. **mysqli prepared statements**, BUKAN PDO (tim belum belajar PDO). Pola helper lama `q()/rows()/row()/scalar()/e()` dipertahankan.
2. **Tanpa ketergantungan `.htaccess`** (tim belum belajar mod_rewrite). Slug tetap identitas publik, lewat query string: `book-detail.php?slug=…`. Tidak ada file `.htaccess` sama sekali di hasil akhir.
3. **URL lama migrasi penuh tanpa redirect** (belum produksi).
4. Nama DB baru **`optl_pustaka`** (impala phpMyAdmin); nama branch **`optl/rombak-total`** dari `vercel-php-demo`.
5. Aturan **NO COMMIT dihapus** (commit bertahap per SPEC di branch ini; tanpa push ke origin kecuali diminta).
6. Label UI Indonesia; `not_interested` = **"Tidak Tertarik"** (bukan "Tidak selesai"); warna tombol tetap.
7. Nama halaman PHP **Inggris** sesuai PDF Tabel 15; beranda + dashboard-user + dashboard-admin **dipertahankan** (tidak ada di tabel rute PDF).
8. Hapus keras buku/akun → **soft-delete `is_active`**; hapus keras hanya item rak; master tidak dihapus.
9. Layout **(a)**: `public/` sebagai DocumentRoot efektif (`localhost/Perpustakaan-berbasis-web/public/`), `storage/` + `includes/` di luar public.
10. Tiket SPEC **lokal** (`docs/specs/SPEC-*.md`); tanpa GitHub Issues; scaffolding `setup-matt-pocock-skills` dilewati.
11. **PDF-saja + PDF.js**; MODUL VII bukan rujukan; cover lama (`cover/bkNNN.jpg`) = referensi bentuk sampul.
12. Smoke test **menyeluruh** ("semua hal yang possible"), bukan sampel.

### Panggilan pelaksana (dicatat sebagai keputusan saya, boleh dikoreksi)
- Kolom `isbn` DIPERTAHANKAN di `books` (PDF menghilangkannya, tapi kontrak UI detail buku menampilkannya); format tampil sebagai konstanta "PDF".
- Cache aset: konstanta `ASSET_VERSION` → semua URL css/js memakai `?v=…` (pengganti no-store `.htaccess`/Vercel).
- Font GD: bundel `Inter-*.ttf` (lisensi OFL) di `public/assets/fonts/` agar sampul otomatis jalan di semua laptop tim.
- Seed penerbit: ±5 data dummy wajar; buku multi-penulis lama mengambil penulis pertama (skema final 1 buku = 1 penulis).
- `page_count` = jumlah halaman berkas PDF sungguhan (diverifikasi pypdf), bukan angka seed lama.
- Toolbar cari/urut/filter `my-shelf.php` tetap **UI-only** sesuai kontrak lama (tab status 4 nilai berfungsi server-side); logika filter tidak ditambah tanpa diminta.
- CSRF + flash + aturan cookie sesi lama dipertahankan di semua POST (termasuk form iframe progres).

## 2. Struktur folder target

```
perpustakaan-berbasis-web/
├── public/                      ← BASE_URL = /Perpustakaan-berbasis-web/public
│   ├── index.php                beranda (hero, 2 pintu masuk: Katalog & Rak Saya)
│   ├── catalog.php              katalog (?q=, ?genre_id=, ?sort=)
│   ├── book-detail.php          ?slug=  (detail + dropdown status + Baca Buku)
│   ├── author.php               ?slug=  (profil penulis + karyanya)
│   ├── my-shelf.php             ?status= (4 tab; toolbar+modal filter UI-only)
│   ├── read.php                 ?slug=  (reader PDF.js + iframe auto-save)
│   ├── stream.php               ?slug=  (aliran PDF; 403 tanpa sesi)
│   ├── login.php (?next=) · register.php · logout.php · profile.php
│   ├── dashboard-user.php · dashboard-admin.php
│   ├── admin/  books.php · book-form.php · publishers.php · authors.php · users.php
│   ├── actions/ collection-save.php · collection-delete.php · progress-save.php
│   ├── assets/ css/ js/ svg/ image/ fonts/   (pindahan assets lama + Inter ttf)
│   └── uploads/covers/          {book_id}.jpg (kustom) · {book_id}-default.jpg (GD)
├── storage/ebooks/{book_id}.pdf ← di luar public, tanpa URL publik
├── includes/ config.php · db.php · auth_guard.php · slug_helper.php · cover_helper.php
├── database/optl_pustaka.sql    (CREATE DATABASE + 7 tabel + VIEW + seed; ramah phpMyAdmin)
├── docs/ RENCANA-ROMBAK-TOTAL.md · specs/SPEC-DB.md … SPEC-10.md
├── tests/ smoke.php · viewport.py
├── AGENTS.md · README.md        (ditulis ulang)
└── .erd/desain_database.md      (diganti ERD 7 tabel baru)
```

Dihapus saat cutover: `api/`, `route/`, `index.html`, `cover/`, `ebook/`, `assets/` lama (setelah dipindah), `vercel.json`, `.vercel/`, `.env.local`, `.htaccess`. Dipertahankan: `docs/`, `DPW/`, `thumbnail/`, `.github/`, `.agents/`, `.opencode/`, `.erd/`.

Halaman admin/login/profil menangani POST-nya sendiri (GET/POST) sesuai tabel rute PDF; `actions/` hanya untuk tulisan sisi pengguna. Semua POST: `cek_csrf()` → proses → redirect → flash.

## 3. Skema `optl_pustaka` (7 tabel 3NF + VIEW)

InnoDB, utf8mb4, PK `INT UNSIGNED AUTO_INCREMENT`, snake_case jamak, boolean `is_*`.

- **users**: user_id PK; email UQ; password_hash; display_name; role ENUM('admin','member'); is_active TINYINT(1) DEFAULT 1; created_at. (Hash Bcrypt seed lama dipindah apa adanya → password demo tetap berlaku.)
- **publishers**: publisher_id PK; name; city; created_at.
- **genres**: genre_id PK; name UQ; description. (Dari 10 `kategori` lama.)
- **authors**: author_id PK; name; slug UQ; country; bio; created_at. (nama_depan+nama_belakang digabung; email lama dibuang.)
- **books**: book_id PK; author_id FK; publisher_id FK; genre_id FK; title; slug UQ; description; published_year; **isbn VARCHAR(20) NULL UQ**; storage_path UQ; file_size; checksum_sha256; page_count; cover_path; is_active DEFAULT 1; created_at.
- **collections**: collection_id PK; user_id FK; book_id FK; status ENUM('to_read','reading','not_interested','finished'); created_at; **UNIQUE(user_id, book_id)**.
- **reading_progress**: progress_id PK; user_id FK; book_id FK; last_page INT UNSIGNED; updated_at; **UNIQUE(user_id, book_id)**. Tanpa kolom persentase.
- **VIEW v_books_catalog**: books ⋈ authors ⋈ publishers ⋈ genres (membawa slug penulis/genre, nama, is_active) → katalog/detail/profil penulis cukup `SELECT *` (0 JOIN di PHP).

FK `RESTRICT` (tidak ada hapus keras buku/akun/master). Label status di UI: to_read="Akan dibaca", reading="Sedang dibaca", finished="Telah dibaca", not_interested="Tidak Tertarik".

### Migrasi seed (10 baris/tabel lama → skema baru)
- 10 authors, 10 genres, ±5 publishers dummy, 10 books (penulis pertama untuk BK001/BK003; genre dari KAT lama; isbn lama; `page_count`/`file_size`/`checksum_sha256` dihitung dari berkas nyata).
- 10 users: display_name gabungan, role Admin→admin / anggota→member, is_active=1; kolom kontak/alamat/jenis kelamin/username dibuang.
- 10 `reading_progress` lama dipecah: status → `collections`; `last_page` → `reading_progress` (di-clamp ke page_count; baris tanpa posisi tidak membuat progres). PRG010 (admin) ikut.
- Berkas: 7 PDF lama → `storage/ebooks/{id}.pdf`; 4 EPUB/MOBI (bk003/005/008/009) → **PDF placeholder buatan** (halaman judul + teks dummy, jumlah halaman wajar); 10 cover lama → `public/uploads/covers/{id}.jpg`; yatim bk011 dibuang.
- Akun demo tetap: `admin@pustaka.com`/`admin123`, `andi@email.com`/`andi123`.

## 4. URL & perilaku kunci (tanpa .htaccess)

| Rute | Akses | Perilaku |
|---|---|---|
| `index.php` | publik | beranda kontrak lama; cari → `catalog.php?q=` |
| `catalog.php?q=&genre_id=&sort=` | publik | modal cari navbar, pil genre, kartu + dropdown status 4 nilai |
| `book-detail.php?slug=` | publik | info: Penulis, Kategori, Tahun, Halaman, ISBN, Format(PDF); Baca Buku → gerbang login |
| `author.php?slug=` | publik | bio, negara, daftar karya |
| `read.php?slug=` | login | PDF.js (CDN, pola sama Bootstrap); toolbar merek; auto-save `last_page` via form tersembunyi → iframe `progressFrame` → `actions/progress-save.php` (tanpa AJAX, tanpa reload) |
| `stream.php?slug=` | login | 403 tanpa sesi / buku nonaktif; `readfile()` dari `storage_path`; nama berkas hanya angka ID (aman traversal) |
| `my-shelf.php?status=` | login | 4 tab; hapus item = POST + konfirmasiHapus |
| `login.php?next=` | publik | gagal → `?gagal=1&email=`; sukses → next tervalidasi lokal / dashboard per role; akun nonaktif ditolak dengan pesan jelas |
| `register.php` | publik | email unik, password ≥6, is_active=1 |
| `profile.php` | login | hanya display_name/email/password milik sesi (WHERE user_id sesi) |
| `dashboard-user.php` / `dashboard-admin.php` | login / admin | statistik COUNT dari tabel baru, tanpa Chart.js |
| `admin/books.php`, `book-form.php` | admin | daftar + nonaktifkan; form tambah/edit + upload PDF (validasi ekstensi + MIME application/pdf) + sampul kustom / GD default |
| `admin/publishers.php`, `admin/authors.php` | admin | master tambah/edit; slug otomatis `generate_unique_slug()` (bentrok → akhiran angka) |
| `admin/users.php` | admin | akt/nonaktif; tidak bisa menonaktifkan diri sendiri / admin aktif terakhir |
| `actions/collection-save.php` / `collection-delete.php` / `progress-save.php` | login POST | milik sesi sendiri; UNIQUE(user,book) ditegakkan DB |

Sesi: cookie HttpOnly, SameSite=Lax, Secure saat HTTPS, `use_strict_mode`. Navbar/footer, `.btn-ungu`/`.btn-pinjam`/`.btn-baca`/`.btn-rak-*`, `.grid-buku`, ring fokus ungu, reduced-motion, hirarki heading, target 44px, `clamp()` — semua dipindah utuh dari `root.css`/`script.js` lama. `konfirmasiHapus()` tetap satu modal promise (Batal default, Esc = Batal) untuk nonaktifkan buku/akun + hapus item rak.

## 5. Urutan eksekusi (commit per SPEC)

0. Salin plan ini ke `docs/RENCANA-ROMBAK-TOTAL.md`; buat branch `optl/rombak-total` dari `vercel-php-demo`; tulis `docs/specs/SPEC-*.md` (kontrak I/O + kriteria penerimaan dari PDF 9.8/9.11).
1. **SPEC-DB**: `database/optl_pustaka.sql` + migrasi berkas/seed; `includes/config.php` (BASE_URL, DB, ASSET_VERSION), `db.php` (mysqli helper lama), `auth_guard.php`, `slug_helper.php`, `cover_helper.php` (GD meniru layout cover referensi: latar biru solid, header PUSTAKADIGITAL + garis, judul putih besar, penulis redup, kategori kiri-bawah, "PDF" kanan-bawah, 600×800). Import via phpMyAdmin diverifikasi.
2. **SPEC-01** beranda + katalog publik (index.php, catalog.php) + aset/css/js pindahan.
3. **SPEC-02** book-detail.php + author.php.
4. **SPEC-03** login/register/logout + guard + cookie sesi.
5. **SPEC-04** profile.php.
6. **SPEC-05** read.php + stream.php (PDF.js, 403).
7. **SPEC-06** auto-save last_page (iframe) + clamp.
8. **SPEC-07** my-shelf.php + actions/collection-* + dashboard-user.
9. **SPEC-08** admin/publishers.php + admin/authors.php.
10. **SPEC-09** admin/books.php + book-form.php + sampul GD + dashboard-admin.
11. **SPEC-10** admin/users.php (status, guard diri/admin terakhir).
12. **Cutover**: hapus pohon lama & artefak Vercel; tulis ulang AGENTS.md (arsitektur baru, keputusan mysqli/tanpa-.htaccess/label, NO COMMIT dihapus), README (tanpa bagian Struktur Proyek/Menjalankan; latar belakang & tujuan tetap non-fisik), `.erd/desain_database.md` (ERD 7 tabel).
13. **Verifikasi penuh** (§6) → commit akhir.

## 6. Verifikasi menyeluruh (tests/smoke.php + tests/viewport.py)

- **Halaman × peran**: setiap halaman diuji sebagai tamu/member/admin → 200 / redirect login / redirect dashboard sesuai matriks; satu `<h1>`, hirarki heading, tanpa overflow horizontal di 320/375/768/1200 (CDP `Emulation.setDeviceMetricsOverride`, klien websockets Python).
- **Auth**: login benar/salah, akun nonaktif ditolak, `?next=` balik ke buku, cookie flags, strict mode, register email duplikat ditolak, password <6 ditolak, logout menghancurkan sesi.
- **Guard tulis**: semua POST tanpa CSRF ditolak; collection-save 4 status + ganti status + UNIQUE; collection-delete hanya milik sendiri (coba milik orang lain → ditolak); progress-save clamp > page_count; admin nonaktifkan diri sendiri & admin terakhir ditolak; user biasa membuka admin/* → dilempar.
- **Streaming**: stream.php tamu = 403; member = 200 application/pdf; buku nonaktif = 403/404; `storage/ebooks/…` tidak bisa diakses lewat URL publik; path traversal (`slug=../../`) aman.
- **Reader**: PDF.js merender PDF asli & placeholder; pindah halaman mengisi iframe tanpa reload (cek network: tidak ada fetch/XHR selain iframe); `last_page` tersimpan & bertahan setelah reload.
- **Admin**: tambah/edit buku (upload PDF valid & invalid ditolak, sampul kustom valid/invalid, GD default terbentuk meniru referensi), slug bentrok → akhiran angka, master penerbit/penulis tambah/edit, nonaktifkan buku hilang dari katalog & streaming.
- **Data**: demo login berlaku; seed 10 buku/10 user/10 collections; page_count = halaman berkas nyata (pypdf); checksum cocok; VIEW 0 JOIN (grep JOIN di halaman katalog/detail/author = 0).
- **UI/aksesibilitas**: ring fokus terlihat, reduced-motion mematikan transisi, sidebar mobile visibility:hidden, label for cocok, ikon aria-hidden, kontras token tak berubah, tombol status berwarna sesuai state, modal konfirmasi fokus Batal.
- **Keamanan statis**: grep tidak ada query string interpolasi ke SQL (semua lewat helper prepared), tidak ada password polos, `htmlspecialchars` di semua output data, tidak ada AJAX selain iframe.
- **Regresi kontrak**: palet `:root` identik byte-per-byte dengan root.css lama; `.grid-buku` 2/4 kolom; dropdown status 4 opsi persis; footer tanpa info fisik; beranda 2 pintu masuk.

## 7. Risiko & catatan live coding

- PDF.js & Bootstrap & Inter via CDN → butuh internet saat demo (sama seperti kondisi lama).
- Penyimpangan dari PDF yang WAJIB bisa dijelaskan saat tanya jawab: mysqli (bukan PDO), query-string slug (bukan rewrite .htaccess), ISBN dipertahankan, beranda+dashboard dipertahankan, label "Tidak Tertarik", tanpa redirect URL lama.
- Placeholder PDF = konten dummy legal; koleksi produksi nanti harus sumber legal (PDF 8.1 #8).
- Filesystem tulis (upload) hanya persisten di lokal/Laragon — sesuai batasan proyek, tanpa object storage.
