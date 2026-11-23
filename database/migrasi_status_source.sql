-- Migrasi untuk database `optl_pustaka` yang sudah ada SEBELUM revisi status lifecycle.
-- Clone baru cukup impor database/optl_pustaka.sql (kolom sudah ada), jangan jalankan file ini.
ALTER TABLE collections
    ADD COLUMN status_source ENUM('system','user') NOT NULL DEFAULT 'system' AFTER status;

UPDATE collections SET status_source = 'user';
