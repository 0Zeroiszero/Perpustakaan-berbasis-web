<?php
// Koneksi MySQL mysqli. Semua query wajib lewat helper di bawah supaya
// nilai dari user selalu jadi parameter prepared statement, bukan bagian SQL.
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Impor database/optl_pustaka.sql lewat phpMyAdmin lalu cek includes/config.php.');
}

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
