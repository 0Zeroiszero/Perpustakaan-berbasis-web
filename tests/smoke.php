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
cek('seed 10 users', $hitung('users') === 10);
cek('seed 10 books', $hitung('books') === 10);
cek('seed 10 authors', $hitung('authors') === 10);
cek('seed 10 genres', $hitung('genres') === 10);
cek('seed 10 collections', $hitung('collections') === 10);
cek('seed >=5 publishers', $hitung('publishers') >= 5);

$admin = $db->query("SELECT password_hash, role, is_active FROM users WHERE email='admin@pustaka.com'")->fetch_assoc();
cek('password demo admin berlaku', $admin && password_verify('admin123', $admin['password_hash']));
cek('role admin konsisten', $admin && $admin['role'] === 'admin' && (int) $admin['is_active'] === 1);
$andi = $db->query("SELECT password_hash, role FROM users WHERE email='andi@email.com'")->fetch_assoc();
cek('password demo user berlaku', $andi && password_verify('andi123', $andi['password_hash']) && $andi['role'] === 'member');

$view = $db->query('SELECT COUNT(*) FROM v_books_catalog WHERE is_active=1')->fetch_row()[0];
cek('VIEW katalog 10 buku aktif', (int) $view === 10);
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
cek('index dua pintu masuk', str_contains($b, 'Katalog Buku') && str_contains($b, 'Rak Saya'));
cek('index tanpa URL route lama', $s === 200 && !str_contains($b, 'route/'));
[$s, $b] = http('/catalog.php');
cek('catalog.php 200', $s === 200, "status $s");
cek('catalog 10 kartu buku', substr_count($b, 'book-item') === 10);
cek('catalog modal cari', str_contains($b, 'modalCari'));
cek('catalog tamu melihat Masuk', str_contains($b, '>Masuk</a>'));
cek('catalog pil genre DB', str_contains($b, 'Teknologi') && str_contains($b, 'Psikologi'));
[$s, $b] = http('/assets/css/root.css');
cek('root.css tersaji 200', $s === 200);
cek('root.css byte-identik dengan kontrak lama', $b === file_get_contents("$root/assets/css/root.css"));
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

// storage tidak punya URL publik (deny .htaccess; DocumentRoot Laragon = www)
$ch = curl_init('http://localhost/Perpustakaan-berbasis-web/storage/ebooks/1.pdf');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
curl_exec($ch);
$sStorage = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
cek('storage diblokir dari URL (403/404)', $sStorage === 403 || $sStorage === 404, "status $sStorage");
foreach (['/database/optl_pustaka.sql', '/includes/config.php', '/tests/smoke.php'] as $p) {
    [$s] = http($p);
    cek("direktori sensitif diblokir: $p", $s === 403 || $s === 404, "status $s");
}
@unlink($jar3);

// ---------- SPEC-06: auto-save last_page ----------
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
cek('progress milik orang lain tak tersentuh', (int) $db->query('SELECT last_page FROM reading_progress WHERE user_id=1 AND book_id=8')->fetch_row()[0] === 12);
// bersihkan baris uji (seed tidak punya progres andi buku 3)
$db->query('DELETE FROM reading_progress WHERE user_id=2 AND book_id=3');
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
cek('rak 200 + isi seed', $s === 200 && str_contains($b, 'Rak Saya') && str_contains($b, 'Dasar-Dasar Basis Data'));
cek('rak tab 4 status', $s === 200 && str_contains($b, 'status=to_read') && str_contains($b, 'status=reading') && str_contains($b, 'status=finished') && str_contains($b, 'status=not_interested'));
[$s, $b] = http('/my-shelf.php?status=reading', [], $jar5);
cek('rak tab reading kosong untuk andi', $s === 200 && !str_contains($b, 'Dasar-Dasar Basis Data') && str_contains($b, 'masih kosong'));
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
http('/actions/collection-delete.php', $post(['csrf' => $token, 'book_id' => 8]), $jar5);
cek('hapus rak orang lain ditolak', (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=1 AND book_id=8')->fetch_row()[0] === 1);
[$s, , $h] = http('/actions/collection-delete.php', $post(['csrf' => $token, 'book_id' => 2]), $jar5);
cek('hapus rak sendiri sukses', $s === 302 && (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=2 AND book_id=2')->fetch_row()[0] === 0);
[$s] = http('/actions/collection-delete.php', $post(['csrf' => '', 'book_id' => 1]), $jar5);
cek('hapus rak tanpa CSRF 419', $s === 419);
cek('seed rak andi utuh', (int) $db->query('SELECT COUNT(*) FROM collections WHERE user_id=2 AND book_id=1')->fetch_row()[0] === 1);

[$s, $b] = http('/dashboard-user.php', [], $jar5);
cek('dashboard user 200 + statistik nyata', $s === 200 && str_contains($b, 'id="statRakSaya">1<') && str_contains($b, 'id="statSelesaiDibaca">1<') && str_contains($b, 'Total Koleksi'));
@unlink($jar5);

echo "\n$lulus lulus, $gagal gagal\n";
exit($gagal > 0 ? 1 : 0);
