# SPEC-10 — Status pengguna (admin)

## Kontrak
- `admin/users.php`: daftar akun + tombol Aktif/Nonaktif (is_active) POST + konfirmasi; tanpa hapus keras, tanpa edit profil orang lain.
- Guard: admin tidak dapat menonaktifkan dirinya sendiri; admin aktif terakhir tidak dapat dinonaktifkan.

## Kriteria penerimaan
1. Nonaktif → akun ditolak saat login dengan pesan jelas; sesi berjalan akun itu berakhir (akun() membaca is_active).
2. Nonaktifkan diri sendiri ditolak; nonaktifkan admin terakhir ditolak.
3. Member membuka halaman → dilempar dashboard-user.
