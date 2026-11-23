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

function http(string $path, array $curl = []): array
{
    $ch = curl_init(SMOKE_BASE . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
    ] + $curl);
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

echo "\n$lulus lulus, $gagal gagal\n";
exit($gagal > 0 ? 1 : 0);
