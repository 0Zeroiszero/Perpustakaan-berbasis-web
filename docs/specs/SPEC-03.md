# SPEC-03 — Autentikasi (login, register, logout, guard)

## Kontrak
- `login.php`: email+password `password_verify`; gagal → `?gagal=1&email=` (invalid-feedback, email di-escape); sukses → `?next=` tervalidasi lokal atau dashboard per role; akun is_active=0 ditolak dengan pesan jelas; dropdown "Akun demo" inline handler.
- `register.php`: email unik, password ≥6, display_name wajib; sukses → login dengan flash.
- `logout.php`: POST/GET menghancurkan sesi (cookie dihapus), redirect index.
- Guard: `wajib_login()` redirect `login.php?next=<url sekarang>`; `wajib_admin()` member → dashboard-user; cookie HttpOnly/SameSite=Lax/Secure-HTTPS/strict mode; CSRF di semua POST.

## Kriteria penerimaan
1. Login demo admin & user sukses ke dashboard masing-masing; password salah → gagal=1.
2. `next` eksternal (http://evil) ditolak → dashboard.
3. Register email duplikat ditolak; password <6 ditolak; akun baru bisa login.
4. POST tanpa csrf → 419; sesi cookie flags benar.
5. Halaman login saat sesi valid → redirect dashboard (tidak menampilkan form).
