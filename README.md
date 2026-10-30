## 🔗 Demo

- **Lokal (Laragon):** <http://localhost/Perpustakaan-berbasis-web/>
- **Online:** <a href="https://perpustakaan-digital-git-verc-349420-abdullah-affandis-projects.vercel.app/">Vercel</a> — perlu variabel `DB_*` yang mengarah ke MySQL publik.

> Akun demo — **Admin**: `admin@pustaka.com` / `admin123` · **User**: `andi@email.com` / `andi123`

<img src="thumbnail/thumbnail.png" alt="Thumbnail PustakaDigital - Perpustakaan Berbasis Web" style="width: 100%;">

# Anggota Kelompok
<div align="center">

<img src="assets/svg/spotify/spotify__.svg" alt="Anggota Kelompok - Spotify K" style="width: 100%;">

</div>

# Tentang Proyek

**PustakaDigital** adalah perpustakaan digital berbasis web yang dikembangkan untuk menjawab kebutuhan akan akses koleksi bacaan yang lebih cepat, praktis, dan terorganisir. Seluruh koleksi pada sistem ini berupa **buku digital (e-book)** yang dibaca langsung dari browser, sehingga tidak ada koleksi fisik, peminjaman, maupun pengembalian buku cetak.

PustakaDigital hadir sebagai solusi dengan menyediakan katalog buku, fitur pencarian, halaman detail buku, serta penyimpanan buku ke rak pribadi. Sistem ini juga dilengkapi dengan **dashboard user** dan **dashboard admin** untuk mendukung pengelolaan koleksi yang lebih efisien.

---

## Latar Belakang

1. Perkembangan teknologi mendorong layanan perpustakaan beralih ke bentuk digital.
2. Perpustakaan konvensional terbatas oleh jam buka, lokasi, dan jumlah salinan buku cetak, sehingga koleksi tidak selalu bisa diakses kapan saja.
3. Proses peminjaman dan pengembalian buku fisik menambah langkah manual bagi pengguna maupun pengelola.
4. Pengguna membutuhkan bacaan yang cepat dicari dan bisa dilanjutkan kapan saja langsung dari browser.
5. Karena itu PustakaDigital dikembangkan sepenuhnya sebagai perpustakaan digital: koleksinya e-book, tanpa koleksi fisik, peminjaman, maupun pengembalian buku cetak.
6. Sistem menyediakan katalog, pencarian, detail buku, dan rak pribadi, serta dashboard user dan admin untuk mendukung pengelolaan koleksi.

---

## Tujuan

- Menyediakan akses koleksi e-book kapan saja, tanpa terikat jam buka atau kunjungan fisik.
- Mempermudah pengguna menemukan buku melalui pencarian judul/penulis dan filter kategori.
- Mempermudah pengguna menyimpan buku ke rak pribadi serta melanjutkan bacaan.
- Mempermudah admin mengelola data buku dan data pengguna dalam satu tempat.
- Menghilangkan proses manual khas perpustakaan fisik, seperti peminjaman, pengembalian, dan denda, karena seluruh koleksinya digital.
- Menyediakan sistem perpustakaan yang cepat, praktis, dan terstruktur.

---

## Fitur

### 🏠 Beranda
Halaman utama sebagai pintu masuk ke koleksi:
- Dua pintu masuk utama: **Katalog Buku** dan **Rak Saya**
- Pencarian langsung dari hero, kata kuncinya diteruskan ke katalog (`katalog.php?q=...`)
- Tautan kategori populer (`katalog.php?kategori=...`)

### 📚 Katalog Buku
Halaman untuk menemukan buku favorit dengan fitur:
- Pencarian berdasarkan judul atau penulis
- Filter kategori dari database (pill yang tersambung ke tabel `kategori`)
- Pengurutan koleksi (Terbaru, Judul A-Z)
- Kartu buku berisi judul, penulis, dan kategori
- Hasil pencarian dan filter bisa dibagikan lewat URL
- Data buku dibaca langsung dari tabel `ebook`

### 📖 Detail Buku
Halaman detail buku yang menampilkan:
- Sampul dan judul buku
- Nama penulis
- Deskripsi singkat
- Informasi kategori, tahun terbit, jumlah halaman, ISBN, dan format e-book
- Aksi: dropdown status (default **Tambah ke rak**, lalu Akan dibaca/Sedang dibaca/Telah dibaca/Tidak selesai) yang menyimpan ke rak dan warnanya mengikuti status tersimpan, plus **Baca Buku** (membuka berkas e-book)

### 🗂️ Rak Saya
Koleksi buku digital pribadi pengguna:
- Menyimpan buku dari katalog atau halaman detail ke rak (tersimpan di tabel `reading_progress`)
- Progres bacaan tiap buku
- Lanjut baca atau hapus buku dari rak
- Tampilan kosong yang mengarahkan pengguna ke katalog

### 🏠 Dashboard User
Ringkasan aktivitas membaca pengguna, meliputi:
- **Rak Saya** – jumlah buku tersimpan
- **Sedang Dibaca** – buku berstatus `Sedang dibaca`/`Tidak selesai`
- **Selesai Dibaca** – buku berstatus `Telah dibaca`
- **Aktivitas Terbaru** – riwayat buku yang dibaca
- Seluruh angka dihitung dari tabel `reading_progress`

### 🛠️ Dashboard Admin
Panel khusus admin untuk mengelola sistem PustakaDigital:
- **Dashboard** – statistik buku/pengguna serta data buku terbaru
- **Kelola Buku** – CRUD buku: tambah, cari, ubah, dan hapus, termasuk ISBN, jumlah halaman, upload cover (folder `cover/`), dan file e-book PDF/EPUB/MOBI (folder `ebook/`)
- **Kelola Pengguna** – CRUD pengguna: nama, email, password, role (Admin/User), jenis kelamin, nomor ponsel, alamat, dan bio
- **Katalog** – melihat katalog buku
- **Keluar** – logout dari sistem

### 🔐 Login & Hak Akses
Halaman autentikasi memvalidasi email + password terhadap tabel `users`; password disimpan sebagai hash Bcrypt (`password_hash()`), bukan teks polos. Tidak ada pendaftaran, dan role mengikuti akun: login langsung diarahkan ke dashboard yang sesuai. Halaman user dan admin dilindungi session; akun User yang membuka halaman admin dilempar kembali ke dashboard user. Admin tidak dapat menghapus akunnya sendiri, dan akun Admin terakhir tidak dapat dihapus. Dropdown "Akun demo" di atas card login menampilkan email admin/user untuk mengisi form sekaligus.

---

## Teknologi

- PHP 8.4 + MySQL (mysqli, prepared statements)
- Session PHP untuk login/logout, `password_hash()` / `password_verify()`
- HTML, CSS, dan JavaScript vanilla (tanpa build step)
- Bootstrap 5.3 + Bootstrap Icons (CDN)
- Google Fonts: Inter

---

## Database

Aplikasi memakai database MySQL `db_pustaka` dengan enam tabel:

- `kategori` — kelompok buku (novel, fiksi fantasi, dst.)
- `author` — data penulis beserta email, negara asal, dan bio singkat
- `ebook` — data buku (judul, tahun terbit, jumlah halaman, ISBN, berkas, kategori)
- `users` — akun (`user_role`: `Admin`/`User`/`anggota`) beserta kontak dan alamat
- `ebook_author` — relasi buku–penulis (satu buku bisa banyak penulis)
- `reading_progress` — progres bacaan per pengguna dan buku, termasuk `status_baca`

Semua ID berbentuk string (`KAT01`, `AUT01`, `BK001`, `USR001`, `PRG001`) dan tiap tabel diisi 10 data dummy saat impor. Berkas sampul dan e-book dummy tersedia di `cover/` dan `ebook/` mengikuti kolom `COVER`/`FILE_BOOK`.

Impor `database/pustakadigital.sql` lewat phpMyAdmin atau `mysql -uroot < database/pustakadigital.sql`. Koneksi default memakai `root` tanpa password di `127.0.0.1`; di server lain isi environment variable `DB_HOST`, `DB_USER`, `DB_PASS`, dan `DB_NAME`.

---

## Catatan Aksesibilitas & UI

- Setiap kolom input punya `<label for>` yang terhubung; ikon dekoratif diberi `aria-hidden="true"`, dan tombol ikon punya nama aksesibel.
- Fokus keyboard selalu terlihat, dan animasi mengikuti `prefers-reduced-motion`.
- Ukuran huruf serta padding/margin menyesuaikan saat layar mengecil.
- Warna tema diatur lewat token di `assets/css/root.css`.

---

## Future Release

Fitur-fitur yang direncanakan untuk rilis mendatang:
- Reader e-book terintegrasi langsung di dalam aplikasi
- Pagination dan filter lanjutan pada tabel admin
- Reset password dan verifikasi email
