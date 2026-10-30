-- ============================================================
-- Konversi Oracle -> MySQL / MariaDB
-- Kompatibel phpMyAdmin 4.x/5.x, MySQL 5.7/8.x, MariaDB 10.x
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `pustaka`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `pustaka`;

-- ============================================================
-- DROP (urutan: child -> parent)
-- ============================================================
DROP TABLE IF EXISTS `READING_PROGRESS`;
DROP TABLE IF EXISTS `EBOOK_AUTHOR`;
DROP TABLE IF EXISTS `EBOOK`;
DROP TABLE IF EXISTS `USERS`;
DROP TABLE IF EXISTS `KATEGORI`;
DROP TABLE IF EXISTS `AUTHOR`;

-- ============================================================
-- TABEL MASTER
-- ============================================================
CREATE TABLE `AUTHOR` (
  `ID_AUTHOR`      VARCHAR(10)  NOT NULL,
  `NAMA_DEPAN`     VARCHAR(50)  NOT NULL,
  `NAMA_BELAKANG`  VARCHAR(50)  DEFAULT NULL,
  `EMAIL`          VARCHAR(100) DEFAULT NULL,
  `NEGARA_ASAL`    VARCHAR(50)  DEFAULT NULL,
  `BIO`            VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`ID_AUTHOR`),
  UNIQUE KEY `UQ_AUTHORS_EMAIL` (`EMAIL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `KATEGORI` (
  `ID_KATEGORI`    VARCHAR(10)  NOT NULL,
  `NAMA_KATEGORI`  VARCHAR(100) NOT NULL,
  `DESKRIPSI`      VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`ID_KATEGORI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `USERS` (
  `ID_USER`        VARCHAR(10)  NOT NULL,
  `NAMA_DEPAN`     VARCHAR(50)  NOT NULL,
  `NAMA_BELAKANG`  VARCHAR(50)  DEFAULT NULL,
  `JENIS_KELAMIN`  CHAR(1)      DEFAULT NULL,
  `EMAIL`          VARCHAR(100) DEFAULT NULL,
  `USERNAME`       VARCHAR(60)  DEFAULT NULL,
  `PASSWORD`       VARCHAR(255) NOT NULL,
  `USER_ROLE`      VARCHAR(20)  DEFAULT NULL,
  `NOMOR_PONSEL`   VARCHAR(20)  DEFAULT NULL,
  `ALAMAT`         VARCHAR(256) DEFAULT NULL,
  `BIO`            VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`ID_USER`),
  UNIQUE KEY `USERS_EMAIL_UN` (`EMAIL`),
  UNIQUE KEY `USERS_USERNAME_UN` (`USERNAME`),
  CONSTRAINT `USERS_GENDER_CK`
    CHECK (`JENIS_KELAMIN` IN ('L','P') OR `JENIS_KELAMIN` IS NULL),
  CONSTRAINT `USERS_ROLE_CK`
    CHECK (`USER_ROLE` IN ('Admin','User','admin','anggota') OR `USER_ROLE` IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL TRANSAKSI
-- ============================================================
CREATE TABLE `EBOOK` (
  `ID_BOOK`               VARCHAR(10)  NOT NULL,
  `JUDUL`                 VARCHAR(255) NOT NULL,
  `TAHUN_TERBIT`          SMALLINT     DEFAULT NULL,
  `JUMLAH_HALAMAN`        INT          DEFAULT NULL,
  `ISBN`                  VARCHAR(20)  DEFAULT NULL,
  `SINOPSIS`              VARCHAR(2000) DEFAULT NULL,
  `COVER`                 VARCHAR(255) DEFAULT NULL,
  `FILE_BOOK`             VARCHAR(255) DEFAULT NULL,
  `FORMAT_FILE`           VARCHAR(10)  DEFAULT NULL,
  `KATEGORI_ID_KATEGORI`  VARCHAR(10)  NOT NULL,
  PRIMARY KEY (`ID_BOOK`),
  UNIQUE KEY `BOOK_ISBN_UN` (`ISBN`),
  KEY `IDX_EBOOK_KATEGORI` (`KATEGORI_ID_KATEGORI`),
  CONSTRAINT `BOOK_KATEGORI_FK`
    FOREIGN KEY (`KATEGORI_ID_KATEGORI`) REFERENCES `KATEGORI` (`ID_KATEGORI`),
  CONSTRAINT `EBOOK_TAHUN_CK`
    CHECK (`TAHUN_TERBIT` BETWEEN 1000 AND 2100),
  CONSTRAINT `EBOOK_FORMAT_CK`
    CHECK (`FORMAT_FILE` IN ('PDF','EPUB','MOBI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `EBOOK_AUTHOR` (
  `BOOK_ID_BOOK`       VARCHAR(10) NOT NULL,
  `AUTHOR_ID_AUTHOR`   VARCHAR(10) NOT NULL,
  PRIMARY KEY (`BOOK_ID_BOOK`, `AUTHOR_ID_AUTHOR`),
  KEY `IDX_EA_AUTHOR` (`AUTHOR_ID_AUTHOR`),
  CONSTRAINT `BOOK_AUTHOR_BOOK_FK`
    FOREIGN KEY (`BOOK_ID_BOOK`) REFERENCES `EBOOK` (`ID_BOOK`),
  CONSTRAINT `BOOK_AUTHOR_AUTHOR_FK`
    FOREIGN KEY (`AUTHOR_ID_AUTHOR`) REFERENCES `AUTHOR` (`ID_AUTHOR`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `READING_PROGRESS` (
  `ID_PROGRESS`       VARCHAR(10)   NOT NULL,
  `USER_ID_USER`      VARCHAR(10)   NOT NULL,
  `BOOK_ID_BOOK`      VARCHAR(10)   NOT NULL,
  `PROGRESS_PERCENT`  DECIMAL(5,2)  DEFAULT NULL,
  `LAST_READ`         DATE          DEFAULT NULL,
  `LAST_POSITION`     INT           DEFAULT NULL,
  `START_DATE`        DATE          DEFAULT NULL,
  `END_DATE`          DATE          DEFAULT NULL,
  `STATUS_BACA`       VARCHAR(20)   DEFAULT 'Akan dibaca',
  PRIMARY KEY (`ID_PROGRESS`),
  KEY `IDX_RP_USER` (`USER_ID_USER`),
  KEY `IDX_RP_BOOK` (`BOOK_ID_BOOK`),
  CONSTRAINT `READING_PROGRESS_BOOK_FK`
    FOREIGN KEY (`BOOK_ID_BOOK`) REFERENCES `EBOOK` (`ID_BOOK`),
  CONSTRAINT `READING_PROGRESS_USER_FK`
    FOREIGN KEY (`USER_ID_USER`) REFERENCES `USERS` (`ID_USER`),
  CONSTRAINT `READING_PROGRESS_STATUS_CK`
    CHECK (`STATUS_BACA` IN ('Akan dibaca','Sedang dibaca','Telah dibaca','Tidak selesai'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA: AUTHOR
-- ============================================================
INSERT INTO `AUTHOR` (`ID_AUTHOR`,`NAMA_DEPAN`,`NAMA_BELAKANG`,`EMAIL`,`NEGARA_ASAL`,`BIO`) VALUES
('AUT01','Pramoedya','Ananta Toer','pram@pustaka.id','Indonesia','Sastrawan legendaris penulis Tetralogi Buru.'),
('AUT02','Andrea','Hirata','andrea@pustaka.id','Indonesia','Penulis novel Laskar Pelangi.'),
('AUT03','J.K.','Rowling','jk@pustaka.id','Inggris','Penulis seri Harry Potter.'),
('AUT04','George','R.R. Martin','grrm@pustaka.id','Amerika','Penulis A Song of Ice and Fire.'),
('AUT05','Tere','Liye','tere@pustaka.id','Indonesia','Penulis produktif novel populer.'),
('AUT06','Dee','Lestari','dee@pustaka.id','Indonesia','Penulis seri Supernova.'),
('AUT07','Ahmad','Tohari','ahmad@pustaka.id','Indonesia','Sastrawan Ronggeng Dukuh Paruk.'),
('AUT08','Haruki','Murakami','haruki@pustaka.id','Jepang','Novelis surrealis ternama.'),
('AUT09','Raditya','Dika','raditya@pustaka.id','Indonesia','Penulis dan komika.'),
('AUT10','Sapardi','Djoko Damono','sapardi@pustaka.id','Indonesia','Penyair dan maestro sastra.');

-- ============================================================
-- DATA: KATEGORI
-- ============================================================
INSERT INTO `KATEGORI` (`ID_KATEGORI`,`NAMA_KATEGORI`,`DESKRIPSI`) VALUES
('KAT01','Teknologi','Buku seputar IT, komputer, dan pemrograman.'),
('KAT02','Novel','Buku fiksi, roman, dan cerita panjang.'),
('KAT03','Sains','Buku ilmu pengetahuan alam dan fisika.'),
('KAT04','Sejarah','Buku peristiwa masa lalu dan budaya.'),
('KAT05','Bisnis','Buku manajemen, keuangan, dan kewirausahaan.'),
('KAT06','Pendidikan','Buku pendukung pembelajaran dan kurikulum.'),
('KAT07','Psikologi','Buku tentang perilaku dan kejiwaan manusia.'),
('KAT08','Agama','Buku keagamaan, spiritual, dan moral.'),
('KAT09','Desain','Buku tentang desain grafis, UI, dan UX.'),
('KAT10','Komik','Karya cerita bergambar berbagai genre.');

-- ============================================================
-- DATA: USERS
-- ============================================================
INSERT INTO `USERS` (`ID_USER`,`NAMA_DEPAN`,`NAMA_BELAKANG`,`JENIS_KELAMIN`,`EMAIL`,`USERNAME`,`PASSWORD`,`USER_ROLE`,`NOMOR_PONSEL`,`ALAMAT`,`BIO`) VALUES
('USR001','Admin','Pustaka','L','admin@pustaka.com','adminpustaka','admin123','Admin','081234567801','Pekanbaru','Administrator sistem.'),
('USR002','Andi','Saputra','L','andi@email.com','andisaputra','andi123','anggota','081234567802','Pekanbaru','Mahasiswa yang gemar membaca.'),
('USR003','Siti','Aulia','P','siti@email.com','sitiaulia','siti123','anggota','081234567803','Dumai','Menyukai buku fiksi.'),
('USR004','Budi','Pratama','L','budi@email.com','budipratama','budi123','anggota','081234567804','Bangkinang','Menyukai buku teknologi.'),
('USR005','Dewi','Lestari','P','dewi@email.com','dewilestari','dewi123','anggota','081234567805','Siak','Menyukai novel.'),
('USR006','Rizky','Ramadhan','L','rizky@email.com','rizkyramadhan','rizky123','anggota','081234567806','Rengat','Membaca buku pengembangan diri.'),
('USR007','Putri','Maharani','P','putri@email.com','putrimaharani','putri123','anggota','081234567807','Tembilahan','Menyukai buku pendidikan.'),
('USR008','Fajar','Hidayat','L','fajar@email.com','fajarhidayat','fajar123','anggota','081234567808','Pelalawan','Menyukai buku sejarah.'),
('USR009','Nadia','Permata','P','nadia@email.com','nadiapermata','nadia123','anggota','081234567809','Bengkalis','Menyukai buku sains.'),
('USR010','Dimas','Firmansyah','L','dimas@email.com','dimasfirmansyah','dimas123','anggota','081234567810','Duri','Menyukai buku pemrograman.');

-- ============================================================
-- DATA: EBOOK
-- ============================================================
INSERT INTO `EBOOK` (`ID_BOOK`,`JUDUL`,`TAHUN_TERBIT`,`JUMLAH_HALAMAN`,`ISBN`,`SINOPSIS`,`COVER`,`FILE_BOOK`,`FORMAT_FILE`,`KATEGORI_ID_KATEGORI`) VALUES
('BK001','Dasar-Dasar Basis Data',2021,250,'978-602-0001-01-1','Pengantar konsep basis data, ERD, dan normalisasi.','cover/bk001.jpg','ebook/bk001.pdf','PDF','KAT01'),
('BK002','Belajar Java OOP',2020,320,'978-602-0001-02-8','Panduan belajar Java dari dasar hingga OOP.','cover/bk002.jpg','ebook/bk002.pdf','PDF','KAT01'),
('BK003','Laskar Pelangi',2005,529,'978-979-3062-79-2','Kisah perjuangan sepuluh anak di Belitung.','cover/bk003.jpg','ebook/bk003.epub','EPUB','KAT02'),
('BK004','Fisika Dasar Modern',2019,410,'978-602-0001-04-2','Pembahasan konsep fisika modern secara ringkas.','cover/bk004.jpg','ebook/bk004.pdf','PDF','KAT03'),
('BK005','Sejarah Nusantara',2018,380,'978-602-0001-05-9','Rangkuman sejarah kerajaan di Nusantara.','cover/bk005.jpg','ebook/bk005.epub','EPUB','KAT04'),
('BK006','Strategi Bisnis Digital',2022,200,'978-602-0001-06-6','Langkah praktis membangun usaha berbasis digital.','cover/bk006.jpg','ebook/bk006.pdf','PDF','KAT05'),
('BK007','Harry Potter 1',1997,350,'978-602-0001-07-3','Kisah penyihir muda bernama Harry Potter.','cover/bk007.jpg','ebook/bk007.pdf','PDF','KAT02'),
('BK008','Metode Pembelajaran Aktif',2021,180,'978-602-0001-08-0','Kumpulan metode belajar mengajar interaktif.','cover/bk008.jpg','ebook/bk008.mobi','MOBI','KAT06'),
('BK009','Psikologi Remaja',2016,290,'978-602-0001-09-7','Ulasan tahap perkembangan psikologis remaja.','cover/bk009.jpg','ebook/bk009.epub','EPUB','KAT07'),
('BK010','Prinsip Desain UI/UX',2023,220,'978-602-0001-10-3','Prinsip dasar merancang antarmuka yang ramah pengguna.','cover/bk010.jpg','ebook/bk010.pdf','PDF','KAT09');

-- ============================================================
-- DATA: EBOOK_AUTHOR
-- ============================================================
INSERT INTO `EBOOK_AUTHOR` (`BOOK_ID_BOOK`,`AUTHOR_ID_AUTHOR`) VALUES
('BK001','AUT01'),('BK001','AUT02'),
('BK002','AUT03'),
('BK003','AUT04'),('BK003','AUT05'),
('BK004','AUT05'),
('BK005','AUT06'),
('BK006','AUT07'),
('BK007','AUT08'),
('BK008','AUT09'),
('BK009','AUT10'),
('BK010','AUT01');

-- ============================================================
-- DATA: READING_PROGRESS
-- ============================================================
INSERT INTO `READING_PROGRESS`
(`ID_PROGRESS`,`USER_ID_USER`,`BOOK_ID_BOOK`,`PROGRESS_PERCENT`,`LAST_READ`,`LAST_POSITION`,`START_DATE`,`END_DATE`,`STATUS_BACA`) VALUES
('PRG001','USR002','BK001',100,'2024-01-10',500,'2024-01-01','2024-01-10','Telah dibaca'),
('PRG002','USR003','BK003',50,'2024-01-20',150,'2024-01-15',NULL,'Sedang dibaca'),
('PRG003','USR004','BK009',0,NULL,0,NULL,NULL,'Akan dibaca'),
('PRG004','USR005','BK007',10,'2024-02-12',25,'2024-02-10',NULL,'Tidak selesai'),
('PRG005','USR006','BK006',100,'2024-03-15',410,'2024-03-01','2024-03-15','Telah dibaca'),
('PRG006','USR007','BK002',75,'2024-03-25',280,'2024-03-20',NULL,'Sedang dibaca'),
('PRG007','USR008','BK005',0,NULL,0,NULL,NULL,'Akan dibaca'),
('PRG008','USR009','BK004',5,'2024-04-16',10,'2024-04-15',NULL,'Tidak selesai'),
('PRG009','USR010','BK010',100,'2024-05-05',120,'2024-05-01','2024-05-05','Telah dibaca'),
('PRG010','USR001','BK008',40,'2024-05-12',180,'2024-05-10',NULL,'Sedang dibaca');

SET FOREIGN_KEY_CHECKS = 1;