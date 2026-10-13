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

1. Perkembangan teknologi mendorong perpustakaan untuk beralih ke sistem digital.
2. Perpustakaan konvensional memiliki keterbatasan dalam hal akses dan pencarian koleksi.
3. Pengguna membutuhkan akses buku yang lebih cepat dan mudah.
4. Dibutuhkan sistem perpustakaan digital berbasis web yang praktis dan terorganisir.
5. PustakaDigital dikembangkan untuk menyediakan katalog, pencarian, detail buku, dan rak buku pribadi.
6. Sistem juga menyediakan dashboard user dan admin untuk mendukung pengelolaan koleksi.

---

## Tujuan

- Mempermudah pengguna dalam mencari informasi buku yang tersedia di perpustakaan digital.
- Mempermudah pengguna menyimpan buku dan melanjutkan bacaan kapan saja.
- Mempermudah admin dalam mengelola data buku dan data pengguna.
- Mengurangi penggunaan proses manual dalam pengelolaan koleksi.
- Menyediakan sistem perpustakaan yang lebih cepat, praktis, dan terstruktur.

---

## Fitur

### 🏠 Beranda
Halaman utama sebagai pintu masuk ke koleksi:
- Dua pintu masuk utama: **Katalog Buku** dan **Rak Saya**
- Pencarian langsung dari hero, kata kuncinya diteruskan ke katalog (`katalog.html?q=...`)
- Tautan kategori populer (`katalog.html?kategori=...`)

### 📚 Katalog Buku
Halaman untuk menemukan buku favorit dengan fitur:
- Pencarian berdasarkan judul atau penulis
- Filter kategori: Semua, Pengembangan Diri, Teknologi, Novel, Sejarah
- Pengurutan koleksi (Terbaru, Judul A-Z, Rating Tertinggi)
- Kartu buku berisi judul, penulis, rating, kategori, dan status ketersediaan
- Hasil pencarian dan filter bisa dibagikan lewat URL

### 📖 Detail Buku
Halaman detail buku yang menampilkan:
- Sampul dan judul buku
- Nama penulis
- Rating buku
- Deskripsi singkat
- Informasi penerbit, tahun terbit, dan jumlah halaman
- Aksi: **Simpan ke Rak** dan **Baca Buku** (membuka berkas e-book PDF)

### 🗂️ Rak Saya
Koleksi buku digital pribadi pengguna:
- Progres bacaan tiap buku
- Lanjut baca atau hapus buku dari rak
- Tampilan kosong yang mengarahkan pengguna ke katalog

### 🏠 Dashboard User
Ringkasan aktivitas membaca pengguna, meliputi:
- **Rak Saya** – jumlah buku tersimpan
- **Sedang Dibaca** – buku dengan progres bacaan
- **Selesai Dibaca** – daftar buku yang telah selesai
- **Aktivitas Terbaru** – log aktivitas pengguna

### 🛠️ Dashboard Admin
Panel khusus admin untuk mengelola sistem PustakaDigital:
- **Dashboard** – ringkasan aktivitas sistem
- **Kelola Buku** – tambah, ubah, dan hapus data buku
- **Kelola Pengguna** – manajemen akun pengguna
- **Katalog** – melihat katalog buku
- **Keluar** – logout dari sistem

### 🔐 Login & Registrasi
Halaman autentikasi untuk masuk ke dalam sistem, dengan validasi konfirmasi password dan pilihan peran (User/Admin).

---

## Teknologi

- HTML, CSS, dan JavaScript vanilla (tanpa build step)
- Bootstrap 5.3 + Bootstrap Icons (CDN)
- Google Fonts: Inter

---

## Struktur Proyek

```
.
├── index.html              # Beranda
├── assets/
│   ├── css/                # root.css = gaya bersama, sisanya gaya per halaman
│   ├── js/                 # script.js = perilaku bersama, sisanya per halaman
│   ├── image/              # sampul buku
│   ├── book/               # berkas e-book (PDF)
│   └── svg/                # aset SVG
└── route/                  # katalog, detail buku, rak saya, login, daftar, dashboard
```

---

## Menjalankan Proyek

Buka `index.html` langsung di browser, atau jalankan server statis dari root proyek:

```bash
python -m http.server 8000
```

Lalu buka `http://localhost:8000`.

---

## Catatan Aksesibilitas & UI

- Setiap kolom input punya `<label for>` yang terhubung; ikon dekoratif diberi `aria-hidden="true"`, dan tombol ikon punya nama aksesibel.
- Fokus keyboard selalu terlihat, dan animasi mengikuti `prefers-reduced-motion`.
- Ukuran huruf serta padding/margin menyesuaikan saat layar mengecil.
- Warna tema diatur lewat token di `assets/css/root.css`.

---

## Future Release

Fitur-fitur yang direncanakan untuk rilis mendatang:
- Integrasi backend dan basis data (data buku, pengguna, dan rak saat ini masih dummy dan hilang saat halaman dimuat ulang)
- Reader e-book terintegrasi langsung di dalam aplikasi
- Autentikasi sungguhan beserta manajemen sesi
