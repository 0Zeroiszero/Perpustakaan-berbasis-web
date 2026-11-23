<?php
// Smoke test CLI: php tests/smoke.php
// Semua pemeriksaan nyata (DB, berkas, helper, HTTP). Keluar non-zero bila ada FAIL.
// Dipakai bertahap per SPEC; bagian baru ditambah seiring halaman dibangun.

$gagal = 0;
$lulus = 0;

function cek(string $nama, bool $ok, string $detail = ''): void
{
    global $gagal, $lulus;
    if ($ok) {
        $lulus++;
        echo "PASS  $nama\n";
    } else {
        $gagal++;
        echo "FAIL  $nama" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

$root = dirname(__DIR__);

// ---------- SPEC-DB: fondasi ----------
cek('includes/config.php ada', file_exists("$root/includes/config.php"));
cek('includes/db.php ada', file_exists("$root/includes/db.php"));
cek('includes/auth_guard.php ada', file_exists("$root/includes/auth_guard.php"));
cek('includes/slug_helper.php ada', file_exists("$root/includes/slug_helper.php"));
cek('includes/cover_helper.php ada', file_exists("$root/includes/cover_helper.php"));
cek('database/optl_pustaka.sql ada', file_exists("$root/database/optl_pustaka.sql"));
cek('font Inter Regular dibundel', file_exists("$root/public/assets/fonts/Inter-Regular.ttf"));
cek('font Inter Bold dibundel', file_exists("$root/public/assets/fonts/Inter-Bold.ttf"));

if (!file_exists("$root/includes/config.php")) {
    echo "\n$lulus lulus, $gagal gagal (fondasi belum ada)\n";
    exit(1);
}
require "$root/includes/config.php";

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (Throwable $e) {
    echo "FAIL  koneksi optl_pustaka — " . $e->getMessage() . "\n";
    echo "\n$lulus lulus, " . ($gagal + 1) . " gagal\n";
    exit(1);
}

$tables = [];
foreach ($db->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM) as $t) {
    $tables[$t[0]] = $t[1];
}
foreach (['users', 'publishers', 'genres', 'authors', 'books', 'collections', 'reading_progress'] as $t) {
    cek("tabel $t ada", isset($tables[$t]) && $tables[$t] === 'BASE TABLE');
}
cek('VIEW v_books_catalog ada', isset($tables['v_books_catalog']) && $tables['v_books_catalog'] === 'VIEW');

$hitung = fn(string $t): int => (int) $db->query("SELECT COUNT(*) FROM `$t`")->fetch_row()[0];
// Invarian, bukan angka seed mutlak: suite harus tetap green setelah demo/pakai normal.
cek('seed users >= 10', $hitung('users') >= 10);
cek('seed books >= 10', $hitung('books') >= 10);
cek('seed authors >= 10', $hitung('authors') >= 10);
cek('seed genres >= 10', $hitung('genres') >= 10);
cek('collections status selalu valid', (int) $db->query("SELECT COUNT(*) FROM collections WHERE status NOT IN ('to_read','reading','not_interested','finished')")->fetch_row()[0] === 0);
cek('seed >=5 publishers', $hitung('publishers') >= 5);

$admin = $db->query("SELECT password_hash, role, is_active FROM users WHERE email='admin@pustaka.com'")->fetch_assoc();
cek('password demo admin berlaku', $admin && password_verify('admin123', $admin['password_hash']));
cek('role admin konsisten', $admin && $admin['role'] === 'admin' && (int) $admin['is_active'] === 1);
$andi = $db->query("SELECT password_hash, role FROM users WHERE email='andi@email.com'")->fetch_assoc();
cek('password demo user berlaku', $andi && password_verify('andi123', $andi['password_hash']) && $andi['role'] === 'member');

$view = $db->query('SELECT COUNT(*) FROM v_books_catalog WHERE is_active=1')->fetch_row()[0];
cek('VIEW katalog >= 10 buku aktif', (int) $view >= 10);
$kolomSumber = $db->query("SHOW COLUMNS FROM collections LIKE 'status_source'")->fetch_assoc();
cek('collections.status_source ada', $kolomSumber && $kolomSumber['Default'] === 'system');
$kolom = $db->query('SELECT * FROM v_books_catalog LIMIT 1')->fetch_assoc();
cek('VIEW membawa nama penulis/genre/penerbit', $kolom && isset($kolom['author_name'], $kolom['genre_name'], $kolom['publisher_name']));

// UNIQUE(user,book) pada collections & reading_progress
foreach (['collections', 'reading_progress'] as $t) {
    $idx = $db->query("SHOW INDEX FROM `$t` WHERE Non_unique=0")->fetch_all(MYSQLI_ASSOC);
    $kolomUnik = [];
    foreach ($idx as $i) {
        $kolomUnik[$i['Key_name']][] = $i['Column_name'];
    }
    $ada = false;
    foreach ($kolomUnik as $k) {
        if (in_array('user_id', $k) && in_array('book_id', $k)) {
            $ada = true;
        }
    }
    cek("UNIQUE(user_id,book_id) di $t", $ada);
}

// Metadata berkas cocok dengan DB
$buku = $db->query('SELECT book_id, storage_path, file_size, checksum_sha256, page_count, cover_path FROM books')->fetch_all(MYSQLI_ASSOC);
foreach ($buku as $b) {
    $path = "$root/" . $b['storage_path'];
    cek("berkas buku {$b['book_id']} ada di storage", file_exists($path));
    if (file_exists($path)) {
        cek("checksum buku {$b['book_id']} cocok", hash_file('sha256', $path) === $b['checksum_sha256']);
        cek("file_size buku {$b['book_id']} cocok", filesize($path) === (int) $b['file_size']);
    }
    cek("sampul buku {$b['book_id']} ada", file_exists("$root/public/" . $b['cover_path']));
    cek("page_count buku {$b['book_id']} > 0", (int) $b['page_count'] > 0);
}
$progres = $db->query('SELECT rp.last_page, b.page_count FROM reading_progress rp JOIN books b ON b.book_id=rp.book_id')->fetch_all(MYSQLI_ASSOC);
$clampOk = true;
foreach ($progres as $p) {
    if ($p['last_page'] < 1 || $p['last_page'] > $p['page_count']) {
        $clampOk = false;
    }
}
cek('last_page seed dalam rentang page_count', $clampOk);

// Helper prepared statement & escape
require "$root/includes/db.php";
cek('helper row() bekerja', row('SELECT email FROM users WHERE user_id = ?', [1])['email'] === 'admin@pustaka.com');
cek('helper e() escape', e('<script>"x"</script>') === '&lt;script&gt;&quot;x&quot;&lt;/script&gt;');
cek('helper scalar() bekerja', scalar('SELECT COUNT(*) FROM books WHERE genre_id = ?', [1]) >= 1);

// Slug
require "$root/includes/slug_helper.php";
cek('slug dasar', generate_unique_slug('Laskar Pelangi', 'books') === 'laskar-pelangi-2');
cek('slug baru unik', generate_unique_slug('Buku Uji Coba Slug', 'books') === 'buku-uji-coba-slug');

// Sampul GD
require "$root/includes/cover_helper.php";
$cover = generate_default_cover(999, 'Judul Uji Coba Sampul Panjang Sekali', 'Penulis Uji', 'Teknologi');
cek('sampul default terbentuk', $cover !== null && file_exists("$root/public/uploads/covers/$cover"));
if ($cover && file_exists("$root/public/uploads/covers/$cover")) {
    $info = getimagesize("$root/public/uploads/covers/$cover");
    cek('sampul default 600x800 JPG', $info[0] === 600 && $info[1] === 800 && $info[2] === IMAGETYPE_JPEG);
    unlink("$root/public/uploads/covers/$cover");
}

// Storage di luar public
cek('storage di luar public', !file_exists("$root/public/storage") && is_dir("$root/storage/ebooks"));

// ---------- HTTP: halaman publik ----------
const SMOKE_BASE = 'http://localhost/Perpustakaan-berbasis-web/public';

function http(string $path, array $curl = [], ?string $jar = null): array
{
    $ch = curl_init(SMOKE_BASE . $path);
    $opsi = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
    ] + $curl;
    if ($jar !== null) {
        $opsi[CURLOPT_COOKIEFILE] = $jar;
        $opsi[CURLOPT_COOKIEJAR] = $jar;
    }
    curl_setopt_array($ch, $opsi);
    $res = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $ukuranHeader = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return [$status, substr($res, $ukuranHeader), substr($res, 0, $ukuranHeader)];
}

// SPEC-01: beranda & katalog
[$s, $b] = http('/index.php');
cek('index.php 200', $s === 200, "status $s");
cek('index satu h1', $s === 200 && substr_count($b, '<h1') === 1);
cek('index dua pintu masuk', str_contains($b, 'Daftar Sekarang') && str_contains($b, 'landing-book'));
cek('index tiga sampul', $s === 200 && substr_count($b, 'class="landing-book"') === 3);
cek('index tanpa URL route lama', $s === 200 && !str_contains($b, 'route/'));
[$s, $b] = http('/catalog.php');
cek('catalog.php 200', $s === 200, "status $s");
cek('catalog >= 10 kartu buku', substr_count($b, 'book-item') >= 10);
cek('catalog modal cari', str_contains($b, 'modalCari'));
cek('catalog tamu melihat Masuk', str_contains($b, '>Masuk</a>'));
cek('catalog pil genre DB', str_contains($b, 'Teknologi') && str_contains($b, 'Psikologi'));
[$s, $b] = http('/assets/css/root.css');
cek('root.css tersaji 200', $s === 200);
// Kontrak UI: root.css harus byte-identik dengan versi lama (hash dicatat sebelum cutover).
cek('root.css byte-identik dengan kontrak lama', hash('sha256', $b) === '8ab12bbf9baa0d2fe35e6e6512bc19d1f52c774120d11ea301a866b3f741c776');
[$s] = http('/uploads/covers/1.jpg');
cek('sampul publik 200', $s === 200);

// SPEC-02: detail buku & profil penulis
[$s, $b] = http('/book-detail.php?slug=laskar-pelangi');
cek('detail buku 200', $s === 200, "status $s");
cek('detail judul & info', $s === 200 && str_contains($b, 'Laskar Pelangi') && str_contains($b, 'Informasi Buku') && str_contains($b, 'ISBN'));
cek('detail satu h1', $s === 200 && substr_count($b, '<h1') === 1);
cek('detail tombol baca ke read.php', $s === 200 && str_contains($b, 'read.php?slug=laskar-pelangi'));
cek('detail dropdown 4 status', $s === 200 && str_contains($b, 'value="to_read"') && str_contains($b, 'value="not_interested"') && str_contains($b, 'Tidak Tertarik'));
[$s] = http('/book-detail.php?slug=tidak-ada');
cek('detail slug salah 404', $s === 404);
[$s] = http('/book-detail.php?slug=../../etc');
cek('detail slug traversal 404', $s === 404);
[$s, $b] = http('/author.php?slug=pramoedya-ananta-toer');
cek('author 200', $s === 200, "status $s");
cek('author bio & karya', $s === 200 && str_contains($b, 'Pramoedya Ananta Toer') && str_contains($b, 'Dasar-Dasar Basis Data'));
[$s] = http('/author.php?slug=tidak-ada');
cek('author slug salah 404', $s === 404);

// REVISI: penerbit clickable seperti penulis
[$s, $b] = http('/book-detail.php?slug=laskar-pelangi');
cek('detail menautkan publisher', $s === 200 && str_contains($b, 'publisher.php?slug=bentang-pustaka'));
[$s, $b] = http('/publisher.php?slug=bentang-pustaka');
cek('publisher 200 + daftar buku', $s === 200 && str_contains($b, 'Bentang Pustaka') && str_contains($b, 'Laskar Pelangi'));
[$s] = http('/publisher.php?slug=tidak-ada');
cek('publisher slug salah 404', $s === 404);

// ---------- SPEC-03: autentikasi (alur sesi nyata via cookie jar) ----------
function csrf_dari(string $body): string
{
    preg_match('/name="csrf" value="([^"]+)"/', $body, $m);
    return $m[1] ?? '';
}

function lokasi(string $headers): string
{
    preg_match('/^Location: (.*)$/mi', $headers, $m);
    return trim($m[1] ?? '');
}

$jar = sys_get_temp_dir() . '/smoke-optl-cookies.txt';
@unlink($jar);
$post = fn(array $fields): array => [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields)];

[$s, $b, $h] = http('/login.php', [], $jar);
cek('login 200 + akun demo', $s === 200 && str_contains($b, 'Akun demo') && str_contains($b, 'Selamat Datang'));
$token = csrf_dari($b);

[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'salah']), $jar);
cek('login salah -> gagal=1', $s === 302 && str_contains(lokasi($h), 'gagal=1'));

[$s, , $h] = http('/login.php', $post(['csrf' => '', 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar);
cek('POST tanpa CSRF -> 419', $s === 419);

[$s, , $h] = http('/login.php', [], $jar);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php?next=' . urlencode('http://evil.test/x'), $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar);
cek('login admin -> dashboard-admin', $s === 302 && str_contains(lokasi($h), 'dashboard-admin.php'));
cek('next eksternal ditolak', !str_contains(lokasi($h), 'evil.test'));
cek('cookie sesi HttpOnly + Lax', str_contains($h, 'HttpOnly') && str_contains($h, 'SameSite=Lax'));

[$s, , $h] = http('/login.php', [], $jar);
cek('login saat sesi valid -> dashboard', $s === 302 && str_contains(lokasi($h), 'dashboard-admin.php'));

[$s, , $h] = http('/logout.php', [], $jar);
cek('logout -> index', $s === 302 && str_contains(lokasi($h), 'index.php'));
[$s] = http('/login.php', [], $jar);
cek('sesi habis setelah logout', $s === 200);

// Registrasi akun uji, lalu pakai untuk uji next valid + nonaktif
[$s, $b, $h] = http('/register.php', [], $jar);
cek('register 200', $s === 200, "status $s");
$token = csrf_dari($b);
$uji = 'smoke-' . substr(md5((string) mt_rand()), 0, 6) . '@uji.test';
[$s, , $h] = http('/register.php', $post(['csrf' => $token, 'display_name' => 'Akun Uji', 'email' => $uji, 'password' => 'rahasia123']), $jar);
cek('register sukses -> login', $s === 302 && str_contains(lokasi($h), 'login.php'));
[$s, $b] = http('/login.php', [], $jar);
cek('flash registrasi tampil di login', $s === 200 && str_contains($b, 'Akun berhasil dibuat'));
[$s, $b, $h] = http('/register.php', [], $jar);
$token = csrf_dari($b);
[$s, , $h] = http('/register.php', $post(['csrf' => $token, 'display_name' => 'Duplikat', 'email' => $uji, 'password' => 'rahasia123']), $jar);
cek('register email duplikat ditolak', $s === 302 && str_contains(lokasi($h), 'gagal=email'));
[$s, $b, $h] = http('/register.php', [], $jar);
$token = csrf_dari($b);
[$s, , $h] = http('/register.php', $post(['csrf' => $token, 'display_name' => 'Pendek', 'email' => 'pendek-' . $uji, 'password' => '123']), $jar);
cek('register password <6 ditolak', $s === 302 && str_contains(lokasi($h), 'gagal=password'));

[$s, $b, $h] = http('/login.php', [], $jar);
$token = csrf_dari($b);
$next = BASE_URL . '/book-detail.php?slug=laskar-pelangi';
[$s, , $h] = http('/login.php?next=' . urlencode($next), $post(['csrf' => $token, 'email' => $uji, 'password' => 'rahasia123', 'next' => $next]), $jar);
cek('login member + next valid kembali ke buku', $s === 302 && lokasi($h) === $next);

// Nonaktifkan akun uji lewat DB, login harus ditolak dengan pesan jelas
http('/logout.php', [], $jar);
$db->query("UPDATE users SET is_active = 0 WHERE email = '" . $db->real_escape_string($uji) . "'");
[$s, $b, $h] = http('/login.php', [], $jar);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => $uji, 'password' => 'rahasia123']), $jar);
cek('akun nonaktif ditolak saat login', $s === 302 && str_contains(lokasi($h), 'nonaktif=1'));
$db->query("DELETE FROM users WHERE email = '" . $db->real_escape_string($uji) . "'");
@unlink($jar);

// ---------- SPEC-04: profil mandiri ----------
[$s, , $h] = http('/profile.php');
cek('profil tamu -> login?next', $s === 302 && str_contains(lokasi($h), 'login.php?next=') && str_contains(lokasi($h), 'profile.php'));

$jar2 = sys_get_temp_dir() . '/smoke-optl-cookies2.txt';
@unlink($jar2);
[$s, $b] = http('/register.php', [], $jar2);
$token = csrf_dari($b);
$uji2 = 'profil-' . substr(md5((string) mt_rand()), 0, 6) . '@uji.test';
http('/register.php', $post(['csrf' => $token, 'display_name' => 'Profil Uji', 'email' => $uji2, 'password' => 'rahasia123']), $jar2);
[$s, $b] = http('/login.php', [], $jar2);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => $uji2, 'password' => 'rahasia123']), $jar2);

[$s, $b] = http('/profile.php', [], $jar2);
cek('profil 200', $s === 200 && str_contains($b, 'Profil Saya'));
$token = csrf_dari($b);
[$s, , $h] = http('/profile.php', $post(['csrf' => $token, 'display_name' => 'Nama Baru Uji', 'email' => $uji2, 'password' => '']), $jar2);
cek('profil ubah nama -> redirect', $s === 302 && str_contains(lokasi($h), 'profile.php'));
[$s, $b] = http('/profile.php', [], $jar2);
cek('profil nama baru tersimpan', $s === 200 && str_contains($b, 'Nama Baru Uji'));
$token = csrf_dari($b);
[$s, , $h] = http('/profile.php', $post(['csrf' => $token, 'display_name' => 'Nama Baru Uji', 'email' => 'admin@pustaka.com', 'password' => '']), $jar2);
cek('profil email akun lain ditolak', $s === 302 && str_contains(lokasi($h), 'gagal=email'));
[$s, $b] = http('/profile.php', [], $jar2);
$token = csrf_dari($b);
[$s, , $h] = http('/profile.php', $post(['csrf' => $token, 'display_name' => 'Nama Baru Uji', 'email' => $uji2, 'password' => '123']), $jar2);
cek('profil password pendek ditolak', $s === 302 && str_contains(lokasi($h), 'gagal=password'));
[$s, $b] = http('/profile.php', [], $jar2);
$token = csrf_dari($b);
http('/profile.php', $post(['csrf' => $token, 'display_name' => 'Nama Baru Uji', 'email' => $uji2, 'password' => 'baru12345']), $jar2);
http('/logout.php', [], $jar2);
[$s, $b] = http('/login.php', [], $jar2);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => $uji2, 'password' => 'baru12345']), $jar2);
cek('password baru berlaku', $s === 302 && str_contains(lokasi($h), 'dashboard-user.php'));
[$s, $b] = http('/profile.php', [], $jar2);
$token = csrf_dari($b);
http('/profile.php', $post(['csrf' => $token, 'user_id' => 1, 'display_name' => 'Hacked', 'email' => $uji2, 'password' => '']), $jar2);
cek('parameter user_id eksternal diabaikan', $db->query("SELECT display_name FROM users WHERE user_id = 1")->fetch_row()[0] === 'Admin Pustaka');
$db->query("DELETE FROM users WHERE email = '" . $db->real_escape_string($uji2) . "'");
@unlink($jar2);

// ---------- SPEC-05: reader & streaming aman ----------
[$s, , $h] = http('/stream.php?slug=laskar-pelangi');
cek('stream tamu 403', $s === 403);
[$s, , $h] = http('/read.php?slug=laskar-pelangi');
cek('read tamu -> login?next', $s === 302 && str_contains(lokasi($h), 'login.php?next='));

$jar3 = sys_get_temp_dir() . '/smoke-optl-cookies3.txt';
@unlink($jar3);
[$s, $b] = http('/login.php', [], $jar3);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar3);

[$s, $b, $h] = http('/stream.php?slug=laskar-pelangi', [], $jar3);
cek('stream member 200 PDF', $s === 200 && str_contains($h, 'application/pdf') && str_starts_with($b, '%PDF'));
[$s] = http('/stream.php?slug=tidak-ada', [], $jar3);
cek('stream slug salah 404', $s === 404);
[$s] = http('/stream.php?slug=../../etc', [], $jar3);
cek('stream traversal 404', $s === 404);
[$s, $b] = http('/read.php?slug=laskar-pelangi', [], $jar3);
cek('read member 200 + PDF.js + iframe', $s === 200 && str_contains($b, 'pdf.min.js') && str_contains($b, 'progressFrame') && str_contains($b, 'stream.php?slug=laskar-pelangi'));
[$s] = http('/read.php?slug=tidak-ada', [], $jar3);
cek('read slug salah 404', $s === 404);

// Buku nonaktif: stream diblokir & hilang dari katalog
$db->query('UPDATE books SET is_active = 0 WHERE book_id = 10');
[$s] = http('/stream.php?slug=prinsip-desain-ui-ux', [], $jar3);
cek('stream buku nonaktif 404', $s === 404);
[$s, $b] = http('/catalog.php');
cek('buku nonaktif hilang dari katalog', $s === 200 && !str_contains($b, 'Prinsip Desain UI/UX'));
$db->query('UPDATE books SET is_active = 1 WHERE book_id = 10');

// storage & direktori sensitif tidak punya URL publik: probe URL ABSOLUT di luar public/
// (DocumentRoot Laragon = www; probe relatif public/ akan 404 palsu tanpa menguji deny).
foreach (['storage/ebooks/1.pdf', 'database/optl_pustaka.sql', 'includes/config.php', 'tests/smoke.php'] as $p) {
    $ch = curl_init('http://localhost/Perpustakaan-berbasis-web/' . $p);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    curl_exec($ch);
    $sD = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    cek("direktori sensitif diblokir: $p", $sD === 403 || $sD === 404, "status $sD");
}
@unlink($jar3);

// ---------- SPEC-06: auto-save last_page ----------
// Fixture milik admin (user 1 buku 9) supaya uji kepemilikan tidak bergantung seed yang bisa berubah saat demo.
$db->query('INSERT INTO reading_progress (user_id, book_id, last_page) VALUES (1, 9, 3) AS b ON DUPLICATE KEY UPDATE last_page = b.last_page');
$db->query('INSERT INTO collections (user_id, book_id, status) VALUES (1, 9, \'reading\') AS b ON DUPLICATE KEY UPDATE status = b.status');
$jar4 = sys_get_temp_dir() . '/smoke-optl-cookies4.txt';
@unlink($jar4);
[$s, $b] = http('/login.php', [], $jar4);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar4);
[$s, $b] = http('/read.php?slug=laskar-pelangi', [], $jar4);
$token = csrf_dari($b);

[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 999]), $jar4);
cek('progress clamp atas', $s === 200 && (int) $db->query('SELECT last_page FROM reading_progress WHERE user_id=2 AND book_id=3')->fetch_row()[0] === 12);
[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 0]), $jar4);
cek('progress clamp bawah', $s === 200 && (int) $db->query('SELECT last_page FROM reading_progress WHERE user_id=2 AND book_id=3')->fetch_row()[0] === 1);
[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 5]), $jar4);
cek('progress upsert satu baris', $s === 200 && (int) $db->query('SELECT COUNT(*) FROM reading_progress WHERE user_id=2 AND book_id=3')->fetch_row()[0] === 1 && (int) $db->query('SELECT last_page FROM reading_progress WHERE user_id=2 AND book_id=3')->fetch_row()[0] === 5);
[$s] = http('/actions/progress-save.php', $post(['csrf' => '', 'book_id' => 3, 'last_page' => 5]), $jar4);
cek('progress tanpa CSRF 419', $s === 419);
[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 999, 'last_page' => 5]), $jar4);
cek('progress buku tak dikenal 404', $s === 404);
[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 5]));
cek('progress tamu ditolak', $s === 302 || $s === 403);
cek('progress milik orang lain tak tersentuh', (int) $db->query('SELECT last_page FROM reading_progress WHERE user_id=1 AND book_id=9')->fetch_row()[0] === 3);
// last_page bertahan setelah reload halaman reader
[$s, $b] = http('/read.php?slug=laskar-pelangi', [], $jar4);
cek('last_page bertahan setelah reload', $s === 200 && str_contains($b, 'id="lastPage" value="5"'));
// bersihkan baris uji (seed tidak punya progres andi buku 3)
$db->query('DELETE FROM reading_progress WHERE user_id=2 AND book_id=3');
$db->query("DELETE FROM collections WHERE user_id=2 AND book_id=3 AND status_source='system'");
@unlink($jar4);

// ---------- SPEC-07: Rak Saya, aksi koleksi, dashboard user ----------
[$s, , $h] = http('/my-shelf.php');
cek('rak tamu -> login', $s === 302 && str_contains(lokasi($h), 'login.php'));

$jar5 = sys_get_temp_dir() . '/smoke-optl-cookies5.txt';
@unlink($jar5);
[$s, $b] = http('/login.php', [], $jar5);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar5);

[$s, $b] = http('/my-shelf.php', [], $jar5);
$token = csrf_dari($b);
// Fixture rak sendiri (buku 1 & 4) supaya uji tidak bergantung seed yang bisa berubah saat demo.
http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 1, 'status' => 'to_read']), $jar5);
http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 4, 'status' => 'to_read']), $jar5);
[$s, $b] = http('/catalog.php', [], $jar5);
cek('flash simpan rak tampil di katalog', $s === 200 && str_contains($b, 'Buku disimpan ke Rak Saya'));
[$s, $b] = http('/my-shelf.php', [], $jar5);
cek('rak 200 + fixture', $s === 200 && str_contains($b, 'Dasar-Dasar Basis Data') && str_contains($b, 'Fisika Dasar Modern'));
cek('rak tab 4 status', $s === 200 && str_contains($b, 'status=to_read') && str_contains($b, 'status=reading') && str_contains($b, 'status=finished') && str_contains($b, 'status=not_interested'));
[$s, $b] = http('/my-shelf.php?status=to_read', [], $jar5);
cek('rak tab to_read berisi fixture', $s === 200 && str_contains($b, 'Fisika Dasar Modern'));
[$s, $b] = http('/my-shelf.php?status=reading', [], $jar5);
cek('rak tab reading menyaring fixture', $s === 200 && !str_contains($b, 'Fisika Dasar Modern'));
[$s, $b] = http('/my-shelf.php', [], $jar5);
$token = csrf_dari($b);

[$s, , $h] = http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 2, 'status' => 'to_read']), $jar5);
cek('simpan rak -> redirect', $s === 302);
cek('simpan rak tersimpan', $db->query("SELECT status FROM collections WHERE user_id=2 AND book_id=2")->fetch_row()[0] === 'to_read');
[$s, $b] = http('/my-shelf.php?status=to_read', [], $jar5);
cek('rak tab to_read berisi buku baru', $s === 200 && str_contains($b, 'Belajar Java OOP'));
$token = csrf_dari($b);
http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 2, 'status' => 'reading']), $jar5);
cek('ubah status satu baris', (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=2 AND book_id=2')->fetch_row()[0] === 1 && $db->query("SELECT status FROM collections WHERE user_id=2 AND book_id=2")->fetch_row()[0] === 'reading');
[$s, $b] = http('/my-shelf.php', [], $jar5);
$token = csrf_dari($b);
[$s, , $h] = http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 2, 'status' => 'bogus']), $jar5);
cek('status tak valid ditolak', $s === 302 && $db->query("SELECT status FROM collections WHERE user_id=2 AND book_id=2")->fetch_row()[0] === 'reading');
[$s] = http('/actions/collection-save.php', $post(['csrf' => '', 'book_id' => 2, 'status' => 'reading']), $jar5);
cek('simpan rak tanpa CSRF 419', $s === 419);

[$s, $b] = http('/my-shelf.php', [], $jar5);
$token = csrf_dari($b);
http('/actions/collection-delete.php', $post(['csrf' => $token, 'book_id' => 9]), $jar5);
cek('hapus rak orang lain ditolak', (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=1 AND book_id=9')->fetch_row()[0] === 1);
[$s, , $h] = http('/actions/collection-delete.php', $post(['csrf' => $token, 'book_id' => 2]), $jar5);
cek('hapus rak sendiri sukses', $s === 302 && (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=2 AND book_id=2')->fetch_row()[0] === 0);
[$s] = http('/actions/collection-delete.php', $post(['csrf' => '', 'book_id' => 1]), $jar5);
cek('hapus rak tanpa CSRF 419', $s === 419);
cek('fixture rak admin utuh setelah uji hapus', (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=1 AND book_id=9')->fetch_row()[0] === 1);

// REVISI: pencarian/filter/sort Rak Saya server-side
[$s, $b] = http('/my-shelf.php?q=basis', [], $jar5);
cek('rak q PHP menyaring', $s === 200 && str_contains($b, 'Dasar-Dasar Basis Data') && !str_contains($b, 'Fisika Dasar Modern'));
[$s, $b] = http('/my-shelf.php?q=zzz', [], $jar5);
cek('rak q kosong hasil', $s === 200 && !str_contains($b, 'Dasar-Dasar Basis Data') && str_contains($b, 'Tidak ada buku yang cocok'));
[$s, $b] = http('/my-shelf.php?kategori=Novel', [], $jar5);
cek('rak kategori PHP menyaring', $s === 200 && !str_contains($b, 'Dasar-Dasar Basis Data') && str_contains($b, 'Tidak ada buku yang cocok'));
[$s, $b] = http('/my-shelf.php?kategori=Teknologi', [], $jar5);
cek('rak kategori Teknologi tampil', $s === 200 && str_contains($b, 'Dasar-Dasar Basis Data'));
[$s, $b] = http('/my-shelf.php?q=basis&status=to_read', [], $jar5);
cek('rak q + status kombinasi', $s === 200 && str_contains($b, 'Dasar-Dasar Basis Data') && str_contains($b, 'Akan dibaca'));
[$s, $b] = http('/my-shelf.php?sort=judul-az', [], $jar5);
cek('rak sort PHP 200', $s === 200 && str_contains($b, 'buku-rak'));

$nRak = (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=2')->fetch_row()[0];
$nSelesai = (int) $db->query("SELECT COUNT(*) FROM collections WHERE user_id=2 AND status='finished'")->fetch_row()[0];
[$s, $b] = http('/dashboard-user.php', [], $jar5);
cek('dashboard user 200 + statistik nyata', $s === 200 && str_contains($b, 'id="statRakSaya">' . $nRak . '<') && str_contains($b, 'id="statSelesaiDibaca">' . $nSelesai . '<') && str_contains($b, 'Total Koleksi'));
// bersih-bersih fixture & kembalikan status seed andi buku 1
$db->query("UPDATE collections SET status='finished' WHERE user_id=2 AND book_id=1");
$db->query('DELETE FROM collections WHERE user_id=2 AND book_id=4');
$db->query('DELETE FROM collections WHERE user_id=1 AND book_id=9');
$db->query('DELETE FROM reading_progress WHERE user_id=1 AND book_id=9');

// REVISI: Baca Ulang pada detail + reset progres
[$s, $b] = http('/book-detail.php?slug=dasar-dasar-basis-data', [], $jar5);
cek('detail finished menampilkan Baca Ulang', $s === 200 && str_contains($b, 'Baca Ulang') && !str_contains($b, '>Baca Buku<'));
[$s, $b] = http('/my-shelf.php?status=finished', [], $jar5);
cek('kartu selesai memakai tombol baca ulang', $s === 200 && str_contains($b, 'onclick="bacaUlang(this)"'));
$token = csrf_dari($b);
[$s, , $h] = http('/actions/progress-reset.php', $post(['csrf' => $token, 'book_id' => 1]), $jar5);
$kol = $db->query('SELECT status, status_source FROM collections WHERE user_id=2 AND book_id=1')->fetch_assoc();
$prog = $db->query('SELECT last_page FROM reading_progress WHERE user_id=2 AND book_id=1')->fetch_assoc();
cek('baca ulang reset ke halaman 1 + reading system', $s === 302 && $kol['status'] === 'reading' && $kol['status_source'] === 'system' && (int) $prog['last_page'] === 1);
$db->query("UPDATE collections SET status='finished', status_source='user' WHERE user_id=2 AND book_id=1");
$db->query('UPDATE reading_progress SET last_page=1 WHERE user_id=2 AND book_id=1');
@unlink($jar5);

// ---------- SPEC-08: master penerbit & penulis (admin) ----------
[$s, , $h] = http('/admin/publishers.php');
cek('master tamu -> login', $s === 302 && str_contains(lokasi($h), 'login.php'));

$jar6 = sys_get_temp_dir() . '/smoke-optl-cookies6.txt';
@unlink($jar6);
[$s, $b] = http('/login.php', [], $jar6);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar6);
[$s, , $h] = http('/admin/publishers.php', [], $jar6);
cek('master member -> dashboard-user', $s === 302 && str_contains(lokasi($h), 'dashboard-user.php'));
http('/logout.php', [], $jar6);
[$s, $b] = http('/login.php', [], $jar6);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar6);

[$s, $b] = http('/admin/publishers.php', [], $jar6);
cek('publishers 200', $s === 200 && str_contains($b, 'Penerbit'));
cek('publishers tanpa tombol hapus', $s === 200 && !str_contains($b, 'Hapus'));
cek('publisher datalist kota', $s === 200 && str_contains($b, 'list="daftarKota"') && str_contains($b, '<option value="Yogyakarta">'));
$token = csrf_dari($b);
[$s, , $h] = http('/admin/publishers.php', $post(['csrf' => $token, 'publisher_id' => 0, 'name' => 'Penerbit Uji', 'city' => 'Kota Uji']), $jar6);
cek('tambah penerbit -> redirect', $s === 302);
$idP = (int) $db->query("SELECT publisher_id FROM publishers WHERE name='Penerbit Uji'")->fetch_row()[0];
cek('penerbit baru ada', $idP > 0);
cek('penerbit baru slug otomatis', $idP > 0 && $db->query("SELECT slug FROM publishers WHERE publisher_id=$idP")->fetch_row()[0] === 'penerbit-uji');
[$s, $b] = http('/admin/publishers.php', [], $jar6);
cek('penerbit baru tampil', $s === 200 && str_contains($b, 'Penerbit Uji'));
$token = csrf_dari($b);
http('/admin/publishers.php', $post(['csrf' => $token, 'publisher_id' => $idP, 'name' => 'Penerbit Uji Edit', 'city' => 'Kota Baru']), $jar6);
cek('edit penerbit tersimpan', $db->query("SELECT name FROM publishers WHERE publisher_id=$idP")->fetch_row()[0] === 'Penerbit Uji Edit');
[$s, $b] = http('/admin/publishers.php', [], $jar6);
$token = csrf_dari($b);
[$s, , $h] = http('/admin/publishers.php', $post(['csrf' => $token, 'publisher_id' => 0, 'name' => '', 'city' => 'x']), $jar6);
cek('penerbit nama kosong ditolak', $s === 302 && (int) $db->query("SELECT COUNT(*) FROM publishers WHERE name=''")->fetch_row()[0] === 0);
[$s] = http('/admin/publishers.php', $post(['csrf' => '', 'publisher_id' => 0, 'name' => 'X', 'city' => 'x']), $jar6);
cek('master tanpa CSRF 419', $s === 419);

[$s, $b] = http('/admin/authors.php', [], $jar6);
cek('authors 200', $s === 200 && str_contains($b, 'Penulis'));
cek('author datalist negara', $s === 200 && str_contains($b, 'list="daftarNegara"') && str_contains($b, '<option value="Indonesia">'));
$token = csrf_dari($b);
http('/admin/authors.php', $post(['csrf' => $token, 'author_id' => 0, 'name' => 'Penulis Uji', 'country' => 'Indonesia', 'bio' => 'Bio uji.']), $jar6);
$idA = (int) $db->query("SELECT author_id FROM authors WHERE name='Penulis Uji'")->fetch_row()[0];
cek('penulis baru slug otomatis', $idA > 0 && $db->query("SELECT slug FROM authors WHERE author_id=$idA")->fetch_row()[0] === 'penulis-uji');
[$s, $b] = http('/admin/authors.php', [], $jar6);
$token = csrf_dari($b);
http('/admin/authors.php', $post(['csrf' => $token, 'author_id' => 0, 'name' => 'Penulis Uji', 'country' => 'Indonesia', 'bio' => '']), $jar6);
cek('slug bentrok berakhiran angka', $db->query("SELECT slug FROM authors WHERE name='Penulis Uji' ORDER BY author_id DESC LIMIT 1")->fetch_row()[0] === 'penulis-uji-2');
[$s, $b] = http('/admin/authors.php', [], $jar6);
$token = csrf_dari($b);
http('/admin/authors.php', $post(['csrf' => $token, 'author_id' => $idA, 'name' => 'Penulis Uji Nama', 'country' => 'Indonesia', 'bio' => 'Bio uji.']), $jar6);
cek('edit penulis slug diperbarui', $db->query("SELECT slug FROM authors WHERE author_id=$idA")->fetch_row()[0] === 'penulis-uji-nama');
$db->query("DELETE FROM authors WHERE name LIKE 'Penulis Uji%'");
$db->query("DELETE FROM publishers WHERE name LIKE 'Penerbit Uji%'");
@unlink($jar6);

// ---------- SPEC-09: admin buku, sampul GD, dashboard admin ----------
[$s, , $h] = http('/admin/books.php');
cek('admin buku tamu -> login', $s === 302 && str_contains(lokasi($h), 'login.php'));

$jar7 = sys_get_temp_dir() . '/smoke-optl-cookies7.txt';
@unlink($jar7);
[$s, $b] = http('/login.php', [], $jar7);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar7);
[$s, , $h] = http('/admin/books.php', [], $jar7);
cek('admin buku member -> dashboard-user', $s === 302 && str_contains(lokasi($h), 'dashboard-user.php'));
http('/logout.php', [], $jar7);
[$s, $b] = http('/login.php', [], $jar7);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar7);

[$s, $b] = http('/admin/books.php', [], $jar7);
cek('admin buku 200 + >=10 baris', $s === 200 && substr_count($b, 'baris-buku') >= 10);
cek('admin buku tombol nonaktifkan', $s === 200 && str_contains($b, 'Nonaktifkan'));
$token = csrf_dari($b);
[$s, , $h] = http('/admin/books.php', $post(['csrf' => $token, 'aksi' => 'status', 'id' => 10, 'ke' => 0]), $jar7);
cek('nonaktifkan buku via POST', $s === 302 && (int) $db->query('SELECT is_active FROM books WHERE book_id=10')->fetch_row()[0] === 0);
[$s, $b] = http('/admin/books.php', [], $jar7);
$token = csrf_dari($b);
http('/admin/books.php', $post(['csrf' => $token, 'aksi' => 'status', 'id' => 10, 'ke' => 1]), $jar7);
cek('aktifkan kembali', (int) $db->query('SELECT is_active FROM books WHERE book_id=10')->fetch_row()[0] === 1);
[$s] = http('/admin/books.php', $post(['csrf' => '', 'aksi' => 'status', 'id' => 10, 'ke' => 0]), $jar7);
cek('status buku tanpa CSRF 419', $s === 419);

[$s, $b] = http('/admin/books.php', [], $jar7);
cek('modal buku 200 + select master', $s === 200 && str_contains($b, 'Tambah Buku') && str_contains($b, 'name="author_id"') && str_contains($b, 'name="publisher_id"') && str_contains($b, 'name="genre_id"'));
$token = csrf_dari($b);

// buku baru tanpa PDF ditolak
[$s, , $h] = http('/admin/books.php', $post(['csrf' => $token, 'book_id' => 0, 'title' => 'Buku Uji Admin', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1, 'published_year' => 2024, 'isbn' => '', 'description' => 'Uji.']), $jar7);
cek('buku baru tanpa PDF ditolak', $s === 302 && (int) $db->query("SELECT COUNT(*) FROM books WHERE title='Buku Uji Admin'")->fetch_row()[0] === 0);

// PDF palsu (isi teks) ditolak
[$s, $b] = http('/admin/books.php', [], $jar7);
$token = csrf_dari($b);
$palsu = sys_get_temp_dir() . '/palsu.pdf';
file_put_contents($palsu, 'ini bukan pdf');
[$s, , $h] = http('/admin/books.php', [CURLOPT_POST => true, CURLOPT_POSTFIELDS => [
    'csrf' => $token, 'book_id' => 0, 'title' => 'Buku Uji Admin', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1,
    'published_year' => 2024, 'isbn' => '', 'description' => 'Uji.', 'file_book' => new CURLFile($palsu, 'application/pdf', 'palsu.pdf'),
]], $jar7);
cek('PDF palsu ditolak', $s === 302 && (int) $db->query("SELECT COUNT(*) FROM books WHERE title='Buku Uji Admin'")->fetch_row()[0] === 0);

// PDF asli diterima + sampul GD otomatis
[$s, $b] = http('/admin/books.php', [], $jar7);
$token = csrf_dari($b);
[$s, , $h] = http('/admin/books.php', [CURLOPT_POST => true, CURLOPT_POSTFIELDS => [
    'csrf' => $token, 'book_id' => 0, 'title' => 'Buku Uji Admin', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1,
    'published_year' => 2024, 'isbn' => '978-000-0000-00-0', 'description' => 'Uji.', 'file_book' => new CURLFile("$root/storage/ebooks/3.pdf", 'application/pdf', 'uji.pdf'),
]], $jar7);
$idB = (int) $db->query("SELECT book_id FROM books WHERE title='Buku Uji Admin'")->fetch_row()[0];
cek('buku baru dengan PDF terbuat', $s === 302 && $idB > 0);
if ($idB) {
    $rowB = $db->query("SELECT * FROM books WHERE book_id=$idB")->fetch_assoc();
    cek('berkas baru di storage', file_exists("$root/storage/ebooks/$idB.pdf"));
    cek('checksum baru cocok', hash_file('sha256', "$root/storage/ebooks/$idB.pdf") === $rowB['checksum_sha256']);
    cek('page_count dari berkas', (int) $rowB['page_count'] === 12);
    cek('sampul GD otomatis', file_exists("$root/public/uploads/covers/$idB-default.jpg"));
    cek('slug otomatis', $rowB['slug'] === 'buku-uji-admin');
    // edit tanpa upload mempertahankan berkas
    [$s, $b] = http('/admin/books.php?edit=' . $idB, [], $jar7);
    $token = csrf_dari($b);
    http('/admin/books.php', $post(['csrf' => $token, 'book_id' => $idB, 'title' => 'Buku Uji Admin Edit', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1, 'published_year' => 2024, 'isbn' => '978-000-0000-00-0', 'description' => 'Uji.']), $jar7);
    $rowB2 = $db->query("SELECT * FROM books WHERE book_id=$idB")->fetch_assoc();
    cek('edit tanpa upload berkas tetap', $rowB2['storage_path'] === $rowB['storage_path'] && $rowB2['cover_path'] === $rowB['cover_path'] && $rowB2['title'] === 'Buku Uji Admin Edit');
    // ISBN duplikat ditolak
    [$s, $b] = http('/admin/books.php', [], $jar7);
    $token = csrf_dari($b);
    [$s, , $h] = http('/admin/books.php', $post(['csrf' => $token, 'book_id' => 0, 'title' => 'Buku ISBN Duplikat', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1, 'published_year' => 2024, 'isbn' => '978-602-0001-01-1', 'description' => '']), $jar7);
    cek('ISBN duplikat ditolak', $s === 302 && (int) $db->query("SELECT COUNT(*) FROM books WHERE title='Buku ISBN Duplikat'")->fetch_row()[0] === 0);
    // bersih-bersih fixture
    $db->query("DELETE FROM books WHERE book_id=$idB");
    @unlink("$root/storage/ebooks/$idB.pdf");
    @unlink("$root/public/uploads/covers/$idB-default.jpg");
}
@unlink($palsu);

[$s, $b] = http('/dashboard-admin.php', [], $jar7);
cek('dashboard admin 200 + statistik', $s === 200 && str_contains($b, 'Total Buku') && str_contains($b, 'Total Pengguna'));
@unlink($jar7);

// ---------- SPEC-10: status pengguna (admin) ----------
$jar8 = sys_get_temp_dir() . '/smoke-optl-cookies8.txt';
@unlink($jar8);
[$s, $b] = http('/login.php', [], $jar8);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar8);
[$s, , $h] = http('/admin/users.php', [], $jar8);
cek('users member -> dashboard-user', $s === 302 && str_contains(lokasi($h), 'dashboard-user.php'));
http('/logout.php', [], $jar8);
[$s, $b] = http('/login.php', [], $jar8);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar8);

[$s, $b] = http('/admin/users.php', [], $jar8);
cek('users 200 + >=10 baris', $s === 200 && substr_count($b, 'baris-user') >= 10);
cek('users tanpa hapus keras', $s === 200 && !str_contains($b, '>Hapus<'));
$token = csrf_dari($b);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'toggle', 'id' => 1, 'ke' => 0]), $jar8);
cek('admin tidak bisa nonaktifkan diri', $s === 302 && (int) $db->query('SELECT is_active FROM users WHERE user_id=1')->fetch_row()[0] === 1);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'toggle', 'id' => 2, 'ke' => 0]), $jar8);
cek('nonaktifkan member sukses', $s === 302 && (int) $db->query('SELECT is_active FROM users WHERE user_id=2')->fetch_row()[0] === 0);

// akun nonaktif: sesi berjalan berakhir & login ditolak
[$s, , $h] = http('/login.php', [], $jar8);
$token = csrf_dari($b);
http('/logout.php', [], $jar8);
[$s, $b] = http('/login.php', [], $jar8);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'andi@email.com', 'password' => 'andi123']), $jar8);
cek('login akun nonaktif ditolak', $s === 302 && str_contains(lokasi($h), 'nonaktif=1'));

// aktifkan kembali lewat admin
[$s, $b] = http('/login.php', [], $jar8);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar8);
[$s, $b] = http('/admin/users.php', [], $jar8);
$token = csrf_dari($b);
http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'toggle', 'id' => 2, 'ke' => 1]), $jar8);
cek('aktifkan member kembali', (int) $db->query('SELECT is_active FROM users WHERE user_id=2')->fetch_row()[0] === 1);

// guard admin terakhir: dengan dua admin, menonaktifkan admin lain boleh; diri sendiri tetap tidak
$db->query("UPDATE users SET role='admin' WHERE user_id=3");
[$s, $b] = http('/admin/users.php', [], $jar8);
$token = csrf_dari($b);
http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'toggle', 'id' => 3, 'ke' => 0]), $jar8);
cek('nonaktifkan admin lain selama masih ada admin aktif', (int) $db->query('SELECT is_active FROM users WHERE user_id=3')->fetch_row()[0] === 0);
[$s, $b] = http('/admin/users.php', [], $jar8);
$token = csrf_dari($b);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'toggle', 'id' => 1, 'ke' => 0]), $jar8);
cek('admin aktif terakhir (diri) tetap dilindungi', (int) $db->query('SELECT is_active FROM users WHERE user_id=1')->fetch_row()[0] === 1);
[$s] = http('/admin/users.php', $post(['csrf' => '', 'aksi' => 'toggle', 'id' => 2, 'ke' => 0]), $jar8);
cek('users tanpa CSRF 419', $s === 419);
$db->query("UPDATE users SET role='member', is_active=1 WHERE user_id=3");
@unlink($jar8);

// ---------- FIX PASS: cek tambahan hasil review final ----------
// CSRF pada sesi yang belum punya token (mis. hanya pernah GET stream.php) harus ditolak.
$jar9 = sys_get_temp_dir() . '/smoke-optl-cookies9.txt';
@unlink($jar9);
http('/stream.php?slug=laskar-pelangi', [], $jar9);
[$s] = http('/login.php', $post(['csrf' => '', 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar9);
cek('CSRF sesi tanpa token ditolak', $s === 419);
@unlink($jar9);

// Input array tidak boleh fatal (bocor stack trace).
[$s] = http('/login.php?email[]=x');
cek('login email array GET tidak fatal', $s === 200);
$jar10 = sys_get_temp_dir() . '/smoke-optl-cookies10.txt';
@unlink($jar10);
[$s, $b] = http('/login.php', [], $jar10);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => ['x'], 'password' => 'y']), $jar10);
cek('login email array POST tidak fatal', $s === 302 && str_contains(lokasi($h), 'gagal=1'));

// next tidak boleh lolos keluar BASE_URL lewat /../ atau mendarat di logout.
[$s, $b] = http('/login.php', [], $jar10);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123', 'next' => BASE_URL . '/../evil']), $jar10);
$l = lokasi($h);
cek('next /../ ditolak', $s === 302 && !str_contains($l, '/../') && (str_starts_with($l, BASE_URL . '/') || str_contains($l, 'dashboard')));
http('/logout.php', [], $jar10);
[$s, $b] = http('/login.php', [], $jar10);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123', 'next' => BASE_URL . '/logout.php']), $jar10);
cek('next logout.php ditolak', $s === 302 && !str_contains(lokasi($h), 'logout.php'));

// Nama genre berapostrof harus aman di konteks JS (data-nama, bukan onclick interpolasi).
$db->query("INSERT INTO genres (name, description) VALUES ('Children''s', '')");
[$s, $b] = http('/catalog.php');
cek('pil genre aman konteks JS', $s === 200 && str_contains($b, 'name="kategori" value="Children&#039;s"'));
$db->query("DELETE FROM genres WHERE name='Children''s'");

// Cover kustom valid/invalid + ISBN duplikat DENGAN PDF valid (uji guard ISBN sungguhan).
$jar11 = sys_get_temp_dir() . '/smoke-optl-cookies11.txt';
@unlink($jar11);
[$s, $b] = http('/login.php', [], $jar11);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar11);
[$s, $b] = http('/admin/books.php', [], $jar11);
$token = csrf_dari($b);
http('/admin/books.php', [CURLOPT_POST => true, CURLOPT_POSTFIELDS => [
    'csrf' => $token, 'book_id' => 0, 'title' => 'Buku Cover Uji', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1,
    'published_year' => 2024, 'isbn' => '', 'description' => '',
    'file_book' => new CURLFile("$root/storage/ebooks/3.pdf", 'application/pdf', 'uji.pdf'),
    'cover' => new CURLFile("$root/public/uploads/covers/1.jpg", 'image/jpeg', 'cover.jpg'),
]], $jar11);
$idC = (int) $db->query("SELECT book_id FROM books WHERE title='Buku Cover Uji'")->fetch_row()[0];
cek('cover kustom terpakai', $idC > 0 && $db->query("SELECT cover_path FROM books WHERE book_id=$idC")->fetch_row()[0] === "uploads/covers/$idC.jpg");
if ($idC) {
    $db->query("DELETE FROM books WHERE book_id=$idC");
    @unlink("$root/storage/ebooks/$idC.pdf");
    @unlink("$root/public/uploads/covers/$idC.jpg");
}
[$s, $b] = http('/admin/books.php', [], $jar11);
$token = csrf_dari($b);
$coverPalsu = sys_get_temp_dir() . '/cover-palsu.jpg';
file_put_contents($coverPalsu, 'bukan gambar');
// Bersihkan sisa run lama supaya cek idempoten.
$db->query("DELETE FROM books WHERE title='Buku Cover Palsu'");
http('/admin/books.php', [CURLOPT_POST => true, CURLOPT_POSTFIELDS => [
    'csrf' => $token, 'book_id' => 0, 'title' => 'Buku Cover Palsu', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1,
    'published_year' => 2024, 'isbn' => '', 'description' => '',
    'file_book' => new CURLFile("$root/storage/ebooks/3.pdf", 'application/pdf', 'uji.pdf'),
    'cover' => new CURLFile($coverPalsu, 'image/jpeg', 'cover.jpg'),
]], $jar11);
cek('cover palsu ditolak', (int) $db->query("SELECT COUNT(*) FROM books WHERE title='Buku Cover Palsu'")->fetch_row()[0] === 0);
[$s, $b] = http('/admin/books.php', [], $jar11);
$token = csrf_dari($b);
http('/admin/books.php', [CURLOPT_POST => true, CURLOPT_POSTFIELDS => [
    'csrf' => $token, 'book_id' => 0, 'title' => 'Buku ISBN Dup Pdf', 'author_id' => 1, 'publisher_id' => 1, 'genre_id' => 1,
    'published_year' => 2024, 'isbn' => '978-602-0001-01-1', 'description' => '',
    'file_book' => new CURLFile("$root/storage/ebooks/3.pdf", 'application/pdf', 'uji.pdf'),
]], $jar11);
cek('ISBN duplikat dengan PDF valid ditolak', (int) $db->query("SELECT COUNT(*) FROM books WHERE title='Buku ISBN Dup Pdf'")->fetch_row()[0] === 0);
@unlink($coverPalsu);
@unlink($jar10);
@unlink($jar11);

// ---------- REVISI: lifecycle status (system vs user) ----------
$db->query("DELETE FROM users WHERE email='lifecycle@uji.test'");
$uid = 0;
$st = $db->prepare('INSERT INTO users (email, password_hash, display_name, role, is_active) VALUES (?, ?, ?, ?, 1)');
$hash = password_hash('rahasia123', PASSWORD_DEFAULT);
$mail = 'lifecycle@uji.test';
$nama = 'Lifecycle Uji';
$role = 'member';
$st->bind_param('ssss', $mail, $hash, $nama, $role);
$st->execute();
$uid = (int) $db->insert_id;

$jar12 = sys_get_temp_dir() . '/smoke-optl-cookies12.txt';
@unlink($jar12);
[$s, $b] = http('/login.php', [], $jar12);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => $mail, 'password' => 'rahasia123']), $jar12);

[$s] = http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 1]), $jar12);
$kol = $db->query("SELECT status, status_source FROM collections WHERE user_id=$uid AND book_id=3")->fetch_assoc();
cek('baca pertama -> reading system', $s === 200 && $kol && $kol['status'] === 'reading' && $kol['status_source'] === 'system');

http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 12]), $jar12);
$kol = $db->query("SELECT status, status_source FROM collections WHERE user_id=$uid AND book_id=3")->fetch_assoc();
cek('halaman terakhir system -> finished', $kol['status'] === 'finished' && $kol['status_source'] === 'system');

http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 5]), $jar12);
$kol = $db->query("SELECT status FROM collections WHERE user_id=$uid AND book_id=3")->fetch_assoc();
$prog = $db->query("SELECT last_page FROM reading_progress WHERE user_id=$uid AND book_id=3")->fetch_assoc();
cek('kembali halaman tidak membatalkan finished', $kol['status'] === 'finished' && (int) $prog['last_page'] === 5);
[$s, $b] = http('/my-shelf.php?status=finished', [], $jar12);
cek('rak finished tetap 100%', $s === 200 && str_contains($b, 'Selesai Dibaca'));

http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 3, 'status' => 'reading']), $jar12);
$kol = $db->query("SELECT status, status_source FROM collections WHERE user_id=$uid AND book_id=3")->fetch_assoc();
cek('reset status user -> reading user', $kol['status'] === 'reading' && $kol['status_source'] === 'user');
http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 3, 'last_page' => 12]), $jar12);
$kol = $db->query("SELECT status FROM collections WHERE user_id=$uid AND book_id=3")->fetch_assoc();
cek('status user tidak diubah sistem', $kol['status'] === 'reading');

http('/actions/collection-save.php', $post(['csrf' => $token, 'book_id' => 4, 'status' => 'to_read']), $jar12);
http('/actions/progress-save.php', $post(['csrf' => $token, 'book_id' => 4, 'last_page' => 1]), $jar12);
$kol = $db->query("SELECT status, status_source FROM collections WHERE user_id=$uid AND book_id=4")->fetch_assoc();
cek('status user sejak awal tidak berubah', $kol['status'] === 'to_read' && $kol['status_source'] === 'user');

$db->query("DELETE FROM collections WHERE user_id=$uid");
$db->query("DELETE FROM reading_progress WHERE user_id=$uid");
$db->query("DELETE FROM users WHERE user_id=$uid");
@unlink($jar12);

// ---------- REVISI: pencarian/filter PHP server-side ----------
[$s, $b] = http('/catalog.php?q=laskar');
cek('katalog q PHP menyaring', $s === 200 && str_contains($b, 'Laskar Pelangi') && !str_contains($b, 'Dasar-Dasar Basis Data'));
[$s, $b] = http('/catalog.php?kategori=Novel');
cek('katalog kategori PHP menyaring', $s === 200 && str_contains($b, 'Laskar Pelangi') && !str_contains($b, 'Dasar-Dasar Basis Data'));
[$s, $b] = http('/catalog.php?q=tidakada');
cek('katalog q kosong hasil', $s === 200 && !str_contains($b, 'book-item') && str_contains($b, 'Belum ada buku'));

$jar13 = sys_get_temp_dir() . '/smoke-optl-cookies13.txt';
@unlink($jar13);
[$s, $b] = http('/login.php', [], $jar13);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar13);
[$s, $b] = http('/admin/books.php?q=laskar', [], $jar13);
cek('admin buku q PHP menyaring', $s === 200 && substr_count($b, 'baris-buku') === 1 && str_contains($b, 'Laskar Pelangi'));
cek('modal buku konsisten', $s === 200 && str_contains($b, 'id="modalBuku"') && str_contains($b, 'name="file_book"') && !str_contains($b, 'book-form.php'));
[$s, $b] = http('/admin/users.php?q=andi', [], $jar13);
cek('admin users q PHP menyaring', $s === 200 && substr_count($b, 'baris-user') === 1 && str_contains($b, 'Andi Saputra'));
[$s, $b] = http('/admin/users.php?edit=2', [], $jar13);
cek('modal pengguna + reset password', $s === 200 && str_contains($b, 'id="modalUser"') && str_contains($b, 'value="reset_password"'));
[$s, $b] = http('/admin/publishers.php?q=gramedia', [], $jar13);
cek('admin publishers q PHP menyaring', $s === 200 && str_contains($b, 'Gramedia Pustaka Utama') && !str_contains($b, 'Bentang Pustaka'));
cek('modal publisher', $s === 200 && str_contains($b, 'id="modalPublisher"'));
[$s, $b] = http('/admin/authors.php?q=andrea', [], $jar13);
cek('admin authors q PHP menyaring', $s === 200 && str_contains($b, 'Andrea Hirata') && !str_contains($b, 'Pramoedya Ananta Toer'));
cek('modal penulis', $s === 200 && str_contains($b, 'id="modalAuthor"'));

// ---------- REVISI: shell login, admin Rak Saya, detail publisher ----------
[$s, $b, $h] = http('/index.php', [], $jar13);
cek('user login dari landing diarahkan ke katalog', $s === 302 && str_contains(lokasi($h), 'catalog.php'));
[$s, $b] = http('/catalog.php');
cek('guest catalog memakai navbar + Beranda', $s === 200 && str_contains($b, 'id="navMenu"') && str_contains($b, '>Beranda</a>') && !str_contains($b, 'id="sidebar"'));
cek('navbar tamu tanpa Dashboard', !str_contains($b, '>Dashboard</a>'));
[$s, $b] = http('/catalog.php', [], $jar13);
cek('catalog login memakai shell sidebar', $s === 200 && str_contains($b, 'id="sidebar"') && str_contains($b, 'main-with-sidebar') && str_contains($b, 'Kelola Publisher') && !str_contains($b, 'id="navMenu"'));
[$s, $b] = http('/book-detail.php?slug=laskar-pelangi', [], $jar13);
cek('detail login memakai shell', $s === 200 && str_contains($b, 'id="sidebar"') && str_contains($b, 'main-with-sidebar'));
cek('detail menampilkan penerbit', $s === 200 && str_contains($b, 'Penerbit') && str_contains($b, 'Bentang Pustaka'));
[$s, $b] = http('/read.php?slug=laskar-pelangi', [], $jar13);
cek('reader login memakai shell', $s === 200 && str_contains($b, 'id="sidebar"') && str_contains($b, 'Fit Halaman') && str_contains($b, 'Fit Lebar') && !str_contains($b, 'zoomSelect'));
[$s, $b] = http('/my-shelf.php', [], $jar13);
cek('admin Rak Saya memakai sidebar admin', $s === 200 && str_contains($b, 'id="sidebar"') && str_contains($b, 'Kelola Publisher') && str_contains($b, 'Kelola Penulis'));
[$s, $b] = http('/admin/users.php', [], $jar13);
cek('sidebar tanpa Beranda + nav publisher/penulis', $s === 200 && !str_contains($b, '>Beranda</a>') && str_contains($b, 'Kelola Publisher') && str_contains($b, 'Kelola Penulis'));
foreach (['/dashboard-admin.php', '/my-shelf.php', '/profile.php', '/admin/books.php'] as $halamanShell) {
    [$s, $b] = http($halamanShell, [], $jar13);
    cek("shell konsisten $halamanShell", $s === 200 && str_contains($b, 'id="sidebar"') && str_contains($b, 'btn-akun') && !str_contains($b, 'id="navMenu"'));
}
@unlink($jar13);

// ---------- REVISI: admin edit pengguna + reset password sekali-tampil ----------
$jar14 = sys_get_temp_dir() . '/smoke-optl-cookies14.txt';
@unlink($jar14);
$db->query("DELETE FROM users WHERE email='edit-target@uji.test'");
[$s, $b] = http('/login.php', [], $jar14);
$token = csrf_dari($b);
http('/login.php', $post(['csrf' => $token, 'email' => 'admin@pustaka.com', 'password' => 'admin123']), $jar14);
[$s, $b] = http('/admin/users.php', [], $jar14);
$token = csrf_dari($b);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'save', 'id' => 0,
    'display_name' => 'Edit Target', 'email' => 'edit-target@uji.test', 'password' => 'awal12345', 'role' => 'member', 'is_active' => '1']), $jar14);
$uidT = (int) $db->query("SELECT user_id FROM users WHERE email='edit-target@uji.test'")->fetch_row()[0];
cek('admin tambah pengguna via modal', $s === 302 && $uidT > 0);

[$s, $b] = http('/admin/users.php?edit=' . $uidT, [], $jar14);
cek('modal edit pengguna terisi', $s === 200 && str_contains($b, 'id="modalUser"') && str_contains($b, 'value="Edit Target"'));
$token = csrf_dari($b);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'save', 'id' => $uidT,
    'display_name' => 'Edit Target Baru', 'email' => 'edit-target@uji.test', 'role' => 'member', 'is_active' => '1']), $jar14);
cek('admin edit pengguna tersimpan', $db->query("SELECT display_name FROM users WHERE user_id=$uidT")->fetch_row()[0] === 'Edit Target Baru');

[$s, $b] = http('/admin/users.php?edit=' . $uidT, [], $jar14);
$token = csrf_dari($b);
[$s, , $h] = http('/admin/users.php', $post(['csrf' => $token, 'aksi' => 'reset_password', 'id' => $uidT]), $jar14);
[$s, $b] = http('/admin/users.php?edit=' . $uidT, [], $jar14);
$passwordBaru = '';
if (preg_match('/id="passwordBaru"[^>]*value="([^"]+)"/', $b, $m)) {
    $passwordBaru = $m[1];
}
cek('reset password tampil sekali + tombol salin', $s === 200 && $passwordBaru !== '' && str_contains($b, 'salinPassword'));
$hashNow = $db->query("SELECT password_hash FROM users WHERE user_id=$uidT")->fetch_row()[0];
cek('reset password mengganti hash', !password_verify('awal12345', $hashNow) && password_verify($passwordBaru, $hashNow));

[$s, $b] = http('/admin/users.php?edit=' . $uidT, [], $jar14);
cek('password sementara hilang setelah reload', $s === 200 && !str_contains($b, 'id="passwordBaru"'));

$jar15 = sys_get_temp_dir() . '/smoke-optl-cookies15.txt';
@unlink($jar15);
[$s, $b] = http('/login.php', [], $jar15);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'edit-target@uji.test', 'password' => 'awal12345']), $jar15);
cek('password lama ditolak setelah reset', $s === 302 && str_contains(lokasi($h), 'gagal=1'));
[$s, $b] = http('/login.php', [], $jar15);
$token = csrf_dari($b);
[$s, , $h] = http('/login.php', $post(['csrf' => $token, 'email' => 'edit-target@uji.test', 'password' => $passwordBaru]), $jar15);
cek('password hasil reset berlaku', $s === 302 && str_contains(lokasi($h), 'dashboard-user.php'));

$db->query("DELETE FROM users WHERE user_id=$uidT");
@unlink($jar14);
@unlink($jar15);

echo "\n$lulus lulus, $gagal gagal\n";
exit($gagal > 0 ? 1 : 0);
