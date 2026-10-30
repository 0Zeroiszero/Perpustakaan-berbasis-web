<?php
// Koneksi MySQL. Di Laragon cukup default; di server lain isi lewat environment variable.
const DB_HOST = 'DB_HOST';
const DB_USER = 'DB_USER';
const DB_PASS = 'DB_PASS';
const DB_NAME = 'DB_NAME';

$host = getenv(DB_HOST) ?: '127.0.0.1';
$user = getenv(DB_USER) ?: 'root';
$pass = getenv(DB_PASS) ?: '';
$nama = getenv(DB_NAME) ?: 'db_pustaka';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $pass, $nama);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Impor database/pustakadigital.sql lalu cek konfigurasi DB_* di api/_db.php.');
}

// Semua query wajib lewat sini supaya nilai user selalu jadi parameter, bukan bagian SQL.
function q(string $sql, array $params = []): mysqli_stmt
{
    global $koneksi;

    $stmt = $koneksi->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();

    return $stmt;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
}

function row(string $sql, array $params = []): ?array
{
    return q($sql, $params)->get_result()->fetch_assoc() ?: null;
}

function scalar(string $sql, array $params = []): int|float
{
    $hasil = q($sql, $params)->get_result()->fetch_row();
    return $hasil ? $hasil[0] : 0;
}

function e(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}
