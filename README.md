## 🔗 Demo

- **Lokal (Laragon):** <http://localhost/Perpustakaan-berbasis-web/public/>

> Akun demo — **Admin**: `admin@pustaka.com` / `admin123` · **User**: `andi@email.com` / `andi123`

<img src="thumbnail/thumbnail.png" alt="Thumbnail PustakaDigital - Perpustakaan Berbasis Web" style="width: 100%;">

# Anggota Kelompok
<div align="center">

<img src="public/assets/svg/spotify/spotify__.svg" alt="Anggota Kelompok - Spotify K" style="width: 100%;">

</div>

# Tentang Proyek

**PustakaDigital** adalah perpustakaan digital berbasis web yang dikembangkan untuk menjawab kebutuhan akan akses koleksi bacaan yang lebih cepat, praktis, dan terorganisir. Seluruh koleksi pada sistem ini berupa **buku digital (e-book PDF)** yang dibaca langsung dari browser, sehingga tidak ada koleksi fisik, peminjaman, maupun pengembalian buku cetak.

PustakaDigital hadir sebagai solusi dengan menyediakan katalog buku, pencarian, halaman detail buku, profil penulis, pembaca PDF inline, serta penyimpanan buku ke rak pribadi dengan progres baca otomatis. Sistem ini juga dilengkapi **dashboard user** dan **dashboard admin** untuk mendukung pengelolaan koleksi yang lebih efisien.

---

## Latar Belakang

1. Perkembangan teknologi mendorong layanan perpustakaan beralih ke bentuk digital.
2. Perpustakaan konvensional terbatas oleh jam buka, lokasi, dan jumlah salinan buku cetak, sehingga koleksi tidak selalu bisa diakses kapan saja.
3. Proses peminjaman dan pengembalian buku fisik menambah langkah manual bagi pengguna maupun pengelola.
4. Pengguna membutuhkan bacaan yang cepat dicari dan bisa dilanjutkan kapan saja langsung dari browser.
5. Karena itu PustakaDigital dikembangkan sepenuhnya sebagai perpustakaan digital: koleksinya e-book, tanpa koleksi fisik, peminjaman, maupun pengembalian buku cetak.
6. Sistem menyediakan katalog, pencarian, detail buku, rak pribadi, serta dashboard user dan admin untuk mendukung pengelolaan koleksi.

---

## Tujuan

- Menyediakan akses koleksi e-book kapan saja, tanpa terikat jam buka atau kunjungan fisik.
- Mempermudah pengguna menemukan buku melalui pencarian judul/penulis dan filter kategori.
- Mempermudah pengguna menyimpan buku ke rak pribadi serta melanjutkan bacaan dari halaman terakhir.
- Mempermudah admin mengelola data buku, penerbit, penulis, dan status akun dalam satu tempat.
- Menghilangkan proses manual khas perpustakaan fisik, seperti peminjaman, pengembalian, dan denda, karena seluruh koleksinya digital.
- Menyediakan sistem perpustakaan yang cepat, praktis, dan terstruktur.

---

## Fitur

### 🏠 Beranda (Landing)
Halaman tamu sebagai pintu masuk koleksi:
- **Tiga sampul koleksi terbaru** (grid 3 kolom) dan form registrasi singkat di sampingnya
- Informasi utama muat satu layar desktop; footer tetap ada dan muncul setelah scroll
- Pencarian langsung ke katalog (`catalog.php?q=...`)
- User yang sudah login diarahkan langsung ke katalog

### 📚 Katalog Buku
- Pencarian judul/penulis, filter kategori, dan urutan diproses **server-side PHP** (form GET + tombol Terapkan)
- Modal cari dari navbar/topbar, pil genre dari database, hasil bisa dibagikan lewat URL
- Kartu buku berisi judul, penulis, kategori, dan dropdown status rak
- Setelah login, katalog memakai shell sidebar dashboard (tanpa navbar publik)

### 📖 Detail Buku & Profil Penulis
- Sampul, judul, penulis (tautan ke profil penulis), sinopsis
- Informasi kategori, **penerbit (nama + kota, tautan ke profil penerbit)**, tahun terbit, jumlah halaman, ISBN, dan format (PDF)
- Dropdown status rak (Akan dibaca / Sedang dibaca / Telah dibaca / Tidak Tertarik) yang langsung menyimpan
- **Baca Buku** membuka pembaca inline; jika status buku sudah **Telah dibaca**, tombol berubah menjadi **Baca Ulang** dan meminta konfirmasi reset progres
- Profil penulis publik (`author.php?slug=...`) dan profil penerbit (`publisher.php?slug=...`) menampilkan identitas serta daftar buku

### 📖 Pembaca PDF Inline
- PDF.js merender buku langsung di browser (`read.php?slug=...`)
- Toolbar: kembali ke detail, halaman sebelumnya/berikutnya, input halaman, **Fit Halaman** / **Fit Lebar** (tanpa persentase)
- Kanvas dirender mengikuti `devicePixelRatio`, jadi teks tetap tajam di layar HP/Retina, termasuk saat Fit Halaman
- Progres `last_page` tersimpan otomatis tanpa tombol dan tanpa reload (form tersembunyi ke iframe)
- Saat pertama dibaca dan user belum punya status, sistem menandai **Sedang dibaca**; jika user sudah memilih status, sistem tidak mengubahnya
- Baris yang dikelola sistem otomatis **Telah dibaca** saat halaman terakhir tercapai; kembali ke halaman sebelumnya tetap 100% sampai user mengganti status
- Berkas PDF dialirkan lewat `stream.php` yang menolak akses tanpa login (403)

### 🗂️ Rak Saya
- Tab empat status bacaan; menyimpan buku dari katalog/detail/profil penulis/penerbit
- **Pencarian judul/penulis, filter kategori, status, dan urutan (Terbaru, A–Z, Progres Baca) diproses server-side PHP**
- Progres bacaan tiap buku diturunkan dari halaman terakhir
- **Baca Ulang** pada buku selesai menampilkan konfirmasi; setelah disetujui progres direset ke halaman 1 dan status menjadi “Sedang dibaca”
- Hapus buku dari rak dengan konfirmasi; tampilan kosong mengarahkan pengguna ke katalog

### 👤 Akun & Profil
- Registrasi terbuka, login email + password (hash Bcrypt), logout
- Profil mandiri: ubah nama tampilan, email, dan kata sandi
- Akun yang dinonaktifkan admin ditolak saat login dengan pesan jelas

### 🏠 Dashboard User
Ringkasan aktivitas membaca: jumlah rak, sedang dibaca, selesai dibaca, total koleksi, daftar sedang dibaca, dan aktivitas terbaru — seluruhnya dihitung dari tabel.

### 🛠️ Dashboard Admin
- Statistik buku/pengguna/penerbit/penulis serta buku terbaru
- **Kelola Buku** — daftar + modal tambah/edit (upload PDF, sampul kustom atau sampul otomatis GD), nonaktifkan/aktifkan buku
- **Kelola Pengguna** — daftar + modal tambah/edit akun, reset password acak sekali-tampil dengan tombol Salin, aktif/nonaktif akun
- **Kelola Publisher** dan **Kelola Penulis** — master data lewat modal tambah/edit; pencarian server-side; saran negara/kota dari `includes/data_helper.php` (datalist, tetap bisa diketik bebas)
- Admin tidak dapat menonaktifkan dirinya sendiri, menurunkan role sendiri, atau menghilangkan admin aktif terakhir

### 🔐 Hak Akses
Publik: beranda, katalog, detail buku, profil penulis, login, registrasi. Wajib login: rak saya, pembaca, streaming, profil, dashboard user. Wajib admin: dashboard admin dan halaman admin/*. Semua aksi tulis memakai POST + CSRF + redirect + flash.

---

## Teknologi

- PHP 8.4+ native + MySQL (mysqli, prepared statements)
- Session PHP untuk login/logout, `password_hash()` / `password_verify()`
- HTML, CSS, dan JavaScript vanilla (tanpa build step, tanpa AJAX)
- Bootstrap 5.3 + Bootstrap Icons (CDN)
- PDF.js 3.11.174 (CDN) untuk pembaca inline
- PHP GD untuk sampul bawaan otomatis
- Google Fonts: Inter

---

## Database

Aplikasi memakai database MySQL **`optl_pustaka`** dengan tujuh tabel 3NF + satu VIEW:

- `users` — akun (email unik, hash Bcrypt, role admin/member, is_active)
- `publishers` — master penerbit (nama, slug unik, kota)
- `genres` — master kategori/genre buku
- `authors` — master penulis (nama, slug unik, negara, bio)
- `books` — katalog buku (slug unik, ISBN, berkas PDF + metadata, is_active)
- `collections` — Rak Saya: empat status, sumber status (`system`/`user`), unik per (user, book)
- `reading_progress` — halaman terakhir per (user, book)
- `v_books_catalog` — VIEW gabungan katalog sehingga halaman publik cukup `SELECT *` (0 JOIN)

Impor `database/optl_pustaka.sql` lewat phpMyAdmin atau `mysql -uroot < database/optl_pustaka.sql`. Untuk database lama yang sudah ada sebelum revisi status lifecycle/publisher slug, jalankan `database/migrasi_status_source.sql` dan `database/migrasi_publisher_slug.sql` sekali. Berkas PDF dan sampul dummy sudah ikut di repo (`storage/ebooks/`, `public/uploads/covers/`), jadi tidak ada langkah migrasi tambahan pada clone baru. Skrip `database/migrasi_berkas.py` hanya dokumentasi historis: ia butuh folder lama `ebook/`+`cover/` dan `pypdf`, dan hanya dipakai sekali saat mengevaluasi skema awal. Koneksi default `root` tanpa password di `127.0.0.1` (lihat `includes/config.php`).

---

## Catatan Aksesibilitas & UI

- Setiap kolom input punya `<label for>` yang terhubung; ikon dekoratif diberi `aria-hidden="true"`, dan tombol ikon punya nama aksesibel.
- Fokus keyboard selalu terlihat, dan animasi mengikuti `prefers-reduced-motion`.
- Ukuran huruf serta padding/margin menyesuaikan saat layar mengecil (teruji sampai 320px).
- Warna tema diatur lewat token di `public/assets/css/root.css`; kontras teks memenuhi WCAG AA.
- Topbar mobile: subjudul disembunyikan, judul satu baris, profil berupa avatar bulat. Dropdown tetap di dalam viewport.
- Judul kartu dibatasi dua baris dengan elipsis; judul lengkap tersedia lewat tooltip.
