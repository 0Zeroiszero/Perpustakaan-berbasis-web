-- optl_pustaka — skema final 7 tabel 3NF + VIEW v_books_catalog (Proposal Revisi Final).
-- Impor lewat phpMyAdmin atau mysql CLI. Mesin InnoDB, charset utf8mb4.
-- Penyimpangan dari PDF yang disetujui tim: mysqli (bukan PDO), slug lewat query string
-- (tanpa .htaccess), kolom isbn dipertahankan untuk kontrak UI detail buku.

CREATE DATABASE IF NOT EXISTS optl_pustaka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE optl_pustaka;

DROP VIEW IF EXISTS v_books_catalog;
DROP TABLE IF EXISTS reading_progress, collections, books, authors, genres, publishers, users;

CREATE TABLE users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE publishers (
    publisher_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publisher_id),
    UNIQUE KEY uq_publishers_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE genres (
    genre_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (genre_id),
    UNIQUE KEY uq_genres_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE authors (
    author_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    country VARCHAR(60) NOT NULL DEFAULT '',
    bio TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (author_id),
    UNIQUE KEY uq_authors_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE books (
    book_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    author_id INT UNSIGNED NOT NULL,
    publisher_id INT UNSIGNED NOT NULL,
    genre_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    description TEXT NULL,
    published_year SMALLINT UNSIGNED NULL,
    isbn VARCHAR(20) NULL,
    storage_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    checksum_sha256 CHAR(64) NOT NULL DEFAULT '',
    page_count INT UNSIGNED NOT NULL DEFAULT 0,
    cover_path VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (book_id),
    UNIQUE KEY uq_books_slug (slug),
    UNIQUE KEY uq_books_isbn (isbn),
    UNIQUE KEY uq_books_storage (storage_path),
    KEY idx_books_author (author_id),
    KEY idx_books_publisher (publisher_id),
    KEY idx_books_genre (genre_id),
    CONSTRAINT fk_books_author FOREIGN KEY (author_id) REFERENCES authors (author_id),
    CONSTRAINT fk_books_publisher FOREIGN KEY (publisher_id) REFERENCES publishers (publisher_id),
    CONSTRAINT fk_books_genre FOREIGN KEY (genre_id) REFERENCES genres (genre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collections (
    collection_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    status ENUM('to_read','reading','not_interested','finished') NOT NULL DEFAULT 'to_read',
    status_source ENUM('system','user') NOT NULL DEFAULT 'system',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id),
    UNIQUE KEY uq_collections_user_book (user_id, book_id),
    CONSTRAINT fk_collections_user FOREIGN KEY (user_id) REFERENCES users (user_id),
    CONSTRAINT fk_collections_book FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reading_progress (
    progress_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    last_page INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (progress_id),
    UNIQUE KEY uq_progress_user_book (user_id, book_id),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users (user_id),
    CONSTRAINT fk_progress_book FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Katalog, detail buku, dan profil penulis cukup SELECT * dari VIEW ini (0 JOIN di PHP).
CREATE OR REPLACE VIEW v_books_catalog AS
SELECT b.book_id, b.title, b.slug, b.description, b.published_year, b.isbn,
       b.storage_path, b.file_size, b.checksum_sha256, b.page_count, b.cover_path,
       b.is_active, b.created_at,
       a.name AS author_name, a.slug AS author_slug, a.country AS author_country, a.bio AS author_bio,
       g.genre_id, g.name AS genre_name,
       p.name AS publisher_name, p.slug AS publisher_slug, p.city AS publisher_city
FROM books b
JOIN authors a ON a.author_id = b.author_id
JOIN genres g ON g.genre_id = b.genre_id
JOIN publishers p ON p.publisher_id = b.publisher_id;

-- ---------- Seed (migrasi dari db_pustaka lama; password demo tetap berlaku) ----------

INSERT INTO users (user_id, email, password_hash, display_name, role, is_active) VALUES
(1, 'admin@pustaka.com', '$2y$12$iYAY01OO6d2pObrZqD3n7On877KKS3GUny.dXh56oY0IMNOMcI78u', 'Admin Pustaka', 'admin', 1),
(2, 'andi@email.com', '$2y$12$4ehiq2FWUuarvd77mspoEu/kTV.mvBXwnasn7CG9uv2Dygf5bz0C6', 'Andi Saputra', 'member', 1),
(3, 'siti@email.com', '$2y$12$6WhyoqOA7ay0sCJKjrU39uhzWen82.v0TELnj.z5QOLjfSusZ6WKS', 'Siti Aulia', 'member', 1),
(4, 'budi@email.com', '$2y$12$y7wr9yXIOLrdIf6knoOELuqhxDv5ehgDsp2C.MgGChnEQKEqiBbTG', 'Budi Pratama', 'member', 1),
(5, 'dewi@email.com', '$2y$12$rbacQmpwbembQet/1WjWFOvhmdQ3yU5AW4q2s3OzFF7BI8HF7caG6', 'Dewi Lestari', 'member', 1),
(6, 'rizky@email.com', '$2y$12$jXtrzOTmymAwN7eCXrfzmuJ4Ma9YluTypLDgjqThAjWCOzSMlzXOa', 'Rizky Ramadhan', 'member', 1),
(7, 'putri@email.com', '$2y$12$3xGNveYswXfDimAt/a05pOW7TtdMXby5aYZ11bryMJzGH54KL3iIS', 'Putri Maharani', 'member', 1),
(8, 'fajar@email.com', '$2y$12$S2.SrBkO35cpXQNSIeAuVeoaaumufRJUQ2rUm3zy0A2cRoFspcA6y', 'Fajar Hidayat', 'member', 1),
(9, 'nadia@email.com', '$2y$12$9bWAJouCy1BztcN0LCocF.zy1UGtOR9RT4uZzFpMI2RTUGCBjf2Lm', 'Nadia Permata', 'member', 1),
(10, 'dimas@email.com', '$2y$12$Z7Y4bSL6IjauudX.jjai9.h2aRglmDo.M..KSwWRq90cL3kxJ3od2', 'Dimas Firmansyah', 'member', 1);

INSERT INTO publishers (publisher_id, name, slug, city) VALUES
(1, 'Gramedia Pustaka Utama', 'gramedia-pustaka-utama', 'Jakarta'),
(2, 'Bentang Pustaka', 'bentang-pustaka', 'Yogyakarta'),
(3, 'Mizan Publishing', 'mizan-publishing', 'Bandung'),
(4, 'Erlangga', 'erlangga', 'Jakarta'),
(5, 'Pustaka Digital Press', 'pustaka-digital-press', 'Pekanbaru');

INSERT INTO genres (genre_id, name, description) VALUES
(1, 'Teknologi', 'Buku seputar IT, komputer, dan pemrograman.'),
(2, 'Novel', 'Buku fiksi, roman, dan cerita panjang.'),
(3, 'Sains', 'Buku ilmu pengetahuan alam dan fisika.'),
(4, 'Sejarah', 'Buku peristiwa masa lalu dan budaya.'),
(5, 'Bisnis', 'Buku manajemen, keuangan, dan kewirausahaan.'),
(6, 'Pendidikan', 'Buku pendukung pembelajaran dan kurikulum.'),
(7, 'Psikologi', 'Buku tentang perilaku dan kejiwaan manusia.'),
(8, 'Agama', 'Buku keagamaan, spiritual, dan moral.'),
(9, 'Desain', 'Buku tentang desain grafis, UI, dan UX.'),
(10, 'Komik', 'Karya cerita bergambar berbagai genre.');

INSERT INTO authors (author_id, name, slug, country, bio) VALUES
(1, 'Pramoedya Ananta Toer', 'pramoedya-ananta-toer', 'Indonesia', 'Sastrawan legendaris penulis Tetralogi Buru.'),
(2, 'Andrea Hirata', 'andrea-hirata', 'Indonesia', 'Penulis novel Laskar Pelangi.'),
(3, 'J.K. Rowling', 'j-k-rowling', 'Inggris', 'Penulis seri Harry Potter.'),
(4, 'George R.R. Martin', 'george-r-r-martin', 'Amerika', 'Penulis A Song of Ice and Fire.'),
(5, 'Tere Liye', 'tere-liye', 'Indonesia', 'Penulis produktif novel populer.'),
(6, 'Dee Lestari', 'dee-lestari', 'Indonesia', 'Penulis seri Supernova.'),
(7, 'Ahmad Tohari', 'ahmad-tohari', 'Indonesia', 'Sastrawan Ronggeng Dukuh Paruk.'),
(8, 'Haruki Murakami', 'haruki-murakami', 'Jepang', 'Novelis surrealis ternama.'),
(9, 'Raditya Dika', 'raditya-dika', 'Indonesia', 'Penulis dan komika.'),
(10, 'Sapardi Djoko Damono', 'sapardi-djoko-damono', 'Indonesia', 'Penyair dan maestro sastra.');

-- page_count/file_size/checksum dari berkas nyata (database/migrasi_berkas.py).
-- Satu buku satu penulis (batasan skema final): buku multi-penulis lama mengambil penulis pertama.
INSERT INTO books (book_id, author_id, publisher_id, genre_id, title, slug, description, published_year, isbn, storage_path, file_size, checksum_sha256, page_count, cover_path, is_active) VALUES
(1, 1, 5, 1, 'Dasar-Dasar Basis Data', 'dasar-dasar-basis-data', 'Pengantar konsep basis data, ERD, dan normalisasi.', 2021, '978-602-0001-01-1', 'storage/ebooks/1.pdf', 709, 'a60748074c8c40299fc4a73ec7f29048d4225bdc00942580b6dc9acd7fd78201', 1, 'uploads/covers/1.jpg', 1),
(2, 3, 4, 1, 'Belajar Java OOP', 'belajar-java-oop', 'Panduan belajar Java dari dasar hingga OOP.', 2020, '978-602-0001-02-8', 'storage/ebooks/2.pdf', 679, 'a8373e7c32ec6f6c9c41ddf224cb2773acf938d4957632973799fc1f0612d9ca', 1, 'uploads/covers/2.jpg', 1),
(3, 4, 2, 2, 'Laskar Pelangi', 'laskar-pelangi', 'Kisah perjuangan sepuluh anak di Belitung.', 2005, '978-979-3062-79-2', 'storage/ebooks/3.pdf', 5704, '8d05c821f1ab4f41880a7c9e385cda3eb632e8898e8400cb3587255d3b6872a9', 12, 'uploads/covers/3.jpg', 1),
(4, 5, 4, 3, 'Fisika Dasar Modern', 'fisika-dasar-modern', 'Pembahasan konsep fisika modern secara ringkas.', 2019, '978-602-0001-04-2', 'storage/ebooks/4.pdf', 679, 'b328c6300f036e4eda744ce5bb7357f2a280fb14278c7772a6c7253fad4a3e06', 1, 'uploads/covers/4.jpg', 1),
(5, 6, 2, 4, 'Sejarah Nusantara', 'sejarah-nusantara', 'Rangkuman sejarah kerajaan di Nusantara.', 2018, '978-602-0001-05-9', 'storage/ebooks/5.pdf', 5740, '9f1c3eac374eaed2005aa6e8d750270ece6f8b28f5b73115508cc48ac1d26bc5', 12, 'uploads/covers/5.jpg', 1),
(6, 7, 1, 5, 'Strategi Bisnis Digital', 'strategi-bisnis-digital', 'Langkah praktis membangun usaha berbasis digital.', 2022, '978-602-0001-06-6', 'storage/ebooks/6.pdf', 686, '3692b229a3fbb5981bb29582ff1d4f7a3d430bd6d33c8a6c0d9293374dcf36af', 1, 'uploads/covers/6.jpg', 1),
(7, 8, 1, 2, 'Harry Potter 1', 'harry-potter-1', 'Kisah penyihir muda bernama Harry Potter.', 1997, '978-602-0001-07-3', 'storage/ebooks/7.pdf', 680, '4f37637b1f45ad8d3de6f051fa50b0f1216a7977aa91ffdae1af054bc7e4996b', 1, 'uploads/covers/7.jpg', 1),
(8, 9, 4, 6, 'Metode Pembelajaran Aktif', 'metode-pembelajaran-aktif', 'Kumpulan metode belajar mengajar interaktif.', 2021, '978-602-0001-08-0', 'storage/ebooks/8.pdf', 5836, '6428022505327831eca60df89cebe1a1b23caefa58bedf2adc7aa889f7804ff4', 12, 'uploads/covers/8.jpg', 1),
(9, 10, 2, 7, 'Psikologi Remaja', 'psikologi-remaja', 'Ulasan tahap perkembangan psikologis remaja.', 2016, '978-602-0001-09-7', 'storage/ebooks/9.pdf', 5728, 'eba55a99a1d38660cd988177aa1c6453048b9688e795c108a0dec960cf2d7066', 12, 'uploads/covers/9.jpg', 1),
(10, 1, 5, 9, 'Prinsip Desain UI/UX', 'prinsip-desain-ui-ux', 'Prinsip dasar merancang antarmuka yang ramah pengguna.', 2023, '978-602-0001-10-3', 'storage/ebooks/10.pdf', 692, '63c49238562aa5a1c7969534010fc96e0f6b51b4f47edba024e2c05d57661346', 1, 'uploads/covers/10.jpg', 1);

-- Rak Saya: status lama dipetakan ke empat nilai final (Tidak selesai -> not_interested).
INSERT INTO collections (user_id, book_id, status, status_source, created_at) VALUES
(2, 1, 'finished', 'user', '2024-01-01 00:00:00'),
(3, 3, 'reading', 'user', '2024-01-15 00:00:00'),
(4, 9, 'to_read', 'user', '2024-02-01 00:00:00'),
(5, 7, 'not_interested', 'user', '2024-02-10 00:00:00'),
(6, 6, 'finished', 'user', '2024-03-01 00:00:00'),
(7, 2, 'reading', 'user', '2024-03-20 00:00:00'),
(8, 5, 'to_read', 'user', '2024-04-01 00:00:00'),
(9, 4, 'not_interested', 'user', '2024-04-15 00:00:00'),
(10, 10, 'finished', 'user', '2024-05-01 00:00:00'),
(1, 8, 'reading', 'user', '2024-05-10 00:00:00');

-- last_page di-clamp ke page_count berkas nyata (seed lama punya posisi melebihi halaman).
INSERT INTO reading_progress (user_id, book_id, last_page, updated_at) VALUES
(2, 1, 1, '2024-01-10 00:00:00'),
(3, 3, 12, '2024-01-20 00:00:00'),
(5, 7, 1, '2024-02-12 00:00:00'),
(6, 6, 1, '2024-03-15 00:00:00'),
(7, 2, 1, '2024-03-25 00:00:00'),
(9, 4, 1, '2024-04-16 00:00:00'),
(10, 10, 1, '2024-05-05 00:00:00'),
(1, 8, 12, '2024-05-12 00:00:00');
