<?php
// Konfigurasi tunggal aplikasi: URL dasar, database, path berkas, versi aset.
// Proyek lokal Laragon; tidak ada deploy Vercel lagi.

const BASE_URL = '/Perpustakaan-berbasis-web/public';
const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'optl_pustaka';

// Naikkan bila css/js berubah supaya browser tidak memakai cache lama (?v=...).
const ASSET_VERSION = '4';

const STORAGE_DIR = __DIR__ . '/../storage/ebooks';
const COVER_DIR = __DIR__ . '/../public/uploads/covers';
const FONT_DIR = __DIR__ . '/../public/assets/fonts';

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . ASSET_VERSION;
}
