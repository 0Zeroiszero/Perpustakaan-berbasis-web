# SPEC-06 — Auto-save last_page (iframe, tanpa AJAX)

## Kontrak
- `read.php`: form tersembunyi `last_page` + `csrf` bertarget iframe `progressFrame` → `actions/progress-save.php` (POST, login, CSRF).
- Handler: INSERT … ON DUPLICATE KEY UPDATE last_page (UNIQUE user+book); clamp 1..page_count; milik sesi sendiri.
- Tanpa tombol, tanpa reload, tanpa fetch/XHR.

## Kriteria penerimaan
1. Pindah halaman PDF.js mengirim form ke iframe (network: hanya request iframe).
2. last_page tersimpan & bertahan setelah reload read.php (PDF.js buka di halaman itu).
3. Nilai > page_count di-clamp; nilai <1 di-clamp; POST tanpa CSRF → 419.
