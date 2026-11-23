# SPEC-04 — Profil mandiri

## Kontrak
- `public/profile.php` (login): tampil + edit display_name, email, kata sandi milik `$_SESSION['user_id']` saja (WHERE user_id sesi; tanpa parameter user_id eksternal).
- Password baru opsional (≥6 bila diisi); email unik kecuali milik sendiri; flash + redirect.

## Kriteria penerimaan
1. Member mengubah nama/email sendiri → tersimpan; navbar ikut berubah.
2. Email milik akun lain ditolak; password kosong = tidak diganti.
3. Tidak ada cara mengubah akun orang lain lewat form ini.
