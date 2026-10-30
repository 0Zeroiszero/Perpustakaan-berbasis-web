-- PustakaDigital - skema + data db_pustaka
-- Impor lewat phpMyAdmin, atau:
--   mysql -uroot < database/pustakadigital.sql
-- Akun demo: admin@pustaka.com / admin123 (Admin), andi@email.com / andi123 (User).
-- Password disimpan sebagai hash Bcrypt dari password_hash().
-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: db_pustaka
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `db_pustaka`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_pustaka` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `db_pustaka`;

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author` (
  `ID_AUTHOR` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NAMA_DEPAN` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NAMA_BELAKANG` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `EMAIL` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NEGARA_ASAL` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BIO` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ID_AUTHOR`),
  UNIQUE KEY `UQ_AUTHORS_EMAIL` (`EMAIL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author`
--

/*!40000 ALTER TABLE `author` DISABLE KEYS */;
INSERT INTO `author` VALUES ('AUT01','Pramoedya','Ananta Toer','pram@pustaka.id','Indonesia','Sastrawan legendaris penulis Tetralogi Buru.'),('AUT02','Andrea','Hirata','andrea@pustaka.id','Indonesia','Penulis novel Laskar Pelangi.'),('AUT03','J.K.','Rowling','jk@pustaka.id','Inggris','Penulis seri Harry Potter.'),('AUT04','George','R.R. Martin','grrm@pustaka.id','Amerika','Penulis A Song of Ice and Fire.'),('AUT05','Tere','Liye','tere@pustaka.id','Indonesia','Penulis produktif novel populer.'),('AUT06','Dee','Lestari','dee@pustaka.id','Indonesia','Penulis seri Supernova.'),('AUT07','Ahmad','Tohari','ahmad@pustaka.id','Indonesia','Sastrawan Ronggeng Dukuh Paruk.'),('AUT08','Haruki','Murakami','haruki@pustaka.id','Jepang','Novelis surrealis ternama.'),('AUT09','Raditya','Dika','raditya@pustaka.id','Indonesia','Penulis dan komika.'),('AUT10','Sapardi','Djoko Damono','sapardi@pustaka.id','Indonesia','Penyair dan maestro sastra.');
/*!40000 ALTER TABLE `author` ENABLE KEYS */;

--
-- Table structure for table `ebook`
--

DROP TABLE IF EXISTS `ebook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebook` (
  `ID_BOOK` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `JUDUL` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TAHUN_TERBIT` smallint DEFAULT NULL,
  `JUMLAH_HALAMAN` int DEFAULT NULL,
  `ISBN` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SINOPSIS` varchar(2000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `COVER` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FILE_BOOK` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FORMAT_FILE` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `KATEGORI_ID_KATEGORI` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID_BOOK`),
  UNIQUE KEY `BOOK_ISBN_UN` (`ISBN`),
  KEY `IDX_EBOOK_KATEGORI` (`KATEGORI_ID_KATEGORI`),
  CONSTRAINT `BOOK_KATEGORI_FK` FOREIGN KEY (`KATEGORI_ID_KATEGORI`) REFERENCES `kategori` (`ID_KATEGORI`),
  CONSTRAINT `EBOOK_FORMAT_CK` CHECK ((`FORMAT_FILE` in (_utf8mb4'PDF',_utf8mb4'EPUB',_utf8mb4'MOBI'))),
  CONSTRAINT `EBOOK_TAHUN_CK` CHECK ((`TAHUN_TERBIT` between 1000 and 2100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebook`
--

/*!40000 ALTER TABLE `ebook` DISABLE KEYS */;
INSERT INTO `ebook` VALUES ('BK001','Dasar-Dasar Basis Data',2021,250,'978-602-0001-01-1','Pengantar konsep basis data, ERD, dan normalisasi.','cover/bk001.jpg','ebook/bk001.pdf','PDF','KAT01'),('BK002','Belajar Java OOP',2020,320,'978-602-0001-02-8','Panduan belajar Java dari dasar hingga OOP.','cover/bk002.jpg','ebook/bk002.pdf','PDF','KAT01'),('BK003','Laskar Pelangi',2005,529,'978-979-3062-79-2','Kisah perjuangan sepuluh anak di Belitung.','cover/bk003.jpg','ebook/bk003.epub','EPUB','KAT02'),('BK004','Fisika Dasar Modern',2019,410,'978-602-0001-04-2','Pembahasan konsep fisika modern secara ringkas.','cover/bk004.jpg','ebook/bk004.pdf','PDF','KAT03'),('BK005','Sejarah Nusantara',2018,380,'978-602-0001-05-9','Rangkuman sejarah kerajaan di Nusantara.','cover/bk005.jpg','ebook/bk005.epub','EPUB','KAT04'),('BK006','Strategi Bisnis Digital',2022,200,'978-602-0001-06-6','Langkah praktis membangun usaha berbasis digital.','cover/bk006.jpg','ebook/bk006.pdf','PDF','KAT05'),('BK007','Harry Potter 1',1997,350,'978-602-0001-07-3','Kisah penyihir muda bernama Harry Potter.','cover/bk007.jpg','ebook/bk007.pdf','PDF','KAT02'),('BK008','Metode Pembelajaran Aktif',2021,180,'978-602-0001-08-0','Kumpulan metode belajar mengajar interaktif.','cover/bk008.jpg','ebook/bk008.mobi','MOBI','KAT06'),('BK009','Psikologi Remaja',2016,290,'978-602-0001-09-7','Ulasan tahap perkembangan psikologis remaja.','cover/bk009.jpg','ebook/bk009.epub','EPUB','KAT07'),('BK010','Prinsip Desain UI/UX',2023,220,'978-602-0001-10-3','Prinsip dasar merancang antarmuka yang ramah pengguna.','cover/bk010.jpg','ebook/bk010.pdf','PDF','KAT09');
/*!40000 ALTER TABLE `ebook` ENABLE KEYS */;

--
-- Table structure for table `ebook_author`
--

DROP TABLE IF EXISTS `ebook_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebook_author` (
  `BOOK_ID_BOOK` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AUTHOR_ID_AUTHOR` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`BOOK_ID_BOOK`,`AUTHOR_ID_AUTHOR`),
  KEY `IDX_EA_AUTHOR` (`AUTHOR_ID_AUTHOR`),
  CONSTRAINT `BOOK_AUTHOR_AUTHOR_FK` FOREIGN KEY (`AUTHOR_ID_AUTHOR`) REFERENCES `author` (`ID_AUTHOR`),
  CONSTRAINT `BOOK_AUTHOR_BOOK_FK` FOREIGN KEY (`BOOK_ID_BOOK`) REFERENCES `ebook` (`ID_BOOK`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ebook_author`
--

/*!40000 ALTER TABLE `ebook_author` DISABLE KEYS */;
INSERT INTO `ebook_author` VALUES ('BK001','AUT01'),('BK010','AUT01'),('BK001','AUT02'),('BK002','AUT03'),('BK003','AUT04'),('BK003','AUT05'),('BK004','AUT05'),('BK005','AUT06'),('BK006','AUT07'),('BK007','AUT08'),('BK008','AUT09'),('BK009','AUT10');
/*!40000 ALTER TABLE `ebook_author` ENABLE KEYS */;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori` (
  `ID_KATEGORI` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NAMA_KATEGORI` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DESKRIPSI` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ID_KATEGORI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES ('KAT01','Teknologi','Buku seputar IT, komputer, dan pemrograman.'),('KAT02','Novel','Buku fiksi, roman, dan cerita panjang.'),('KAT03','Sains','Buku ilmu pengetahuan alam dan fisika.'),('KAT04','Sejarah','Buku peristiwa masa lalu dan budaya.'),('KAT05','Bisnis','Buku manajemen, keuangan, dan kewirausahaan.'),('KAT06','Pendidikan','Buku pendukung pembelajaran dan kurikulum.'),('KAT07','Psikologi','Buku tentang perilaku dan kejiwaan manusia.'),('KAT08','Agama','Buku keagamaan, spiritual, dan moral.'),('KAT09','Desain','Buku tentang desain grafis, UI, dan UX.'),('KAT10','Komik','Karya cerita bergambar berbagai genre.');
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;

--
-- Table structure for table `reading_progress`
--

DROP TABLE IF EXISTS `reading_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_progress` (
  `ID_PROGRESS` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `USER_ID_USER` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `BOOK_ID_BOOK` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PROGRESS_PERCENT` decimal(5,2) DEFAULT NULL,
  `LAST_READ` date DEFAULT NULL,
  `LAST_POSITION` int DEFAULT NULL,
  `START_DATE` date DEFAULT NULL,
  `END_DATE` date DEFAULT NULL,
  `STATUS_BACA` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Akan dibaca',
  PRIMARY KEY (`ID_PROGRESS`),
  KEY `IDX_RP_USER` (`USER_ID_USER`),
  KEY `IDX_RP_BOOK` (`BOOK_ID_BOOK`),
  CONSTRAINT `READING_PROGRESS_BOOK_FK` FOREIGN KEY (`BOOK_ID_BOOK`) REFERENCES `ebook` (`ID_BOOK`),
  CONSTRAINT `READING_PROGRESS_USER_FK` FOREIGN KEY (`USER_ID_USER`) REFERENCES `users` (`ID_USER`),
  CONSTRAINT `READING_PROGRESS_STATUS_CK` CHECK ((`STATUS_BACA` in (_utf8mb4'Akan dibaca',_utf8mb4'Sedang dibaca',_utf8mb4'Telah dibaca',_utf8mb4'Tidak selesai')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_progress`
--

/*!40000 ALTER TABLE `reading_progress` DISABLE KEYS */;
INSERT INTO `reading_progress` VALUES ('PRG001','USR002','BK001',100.00,'2024-01-10',500,'2024-01-01','2024-01-10','Telah dibaca'),('PRG002','USR003','BK003',50.00,'2024-01-20',150,'2024-01-15',NULL,'Sedang dibaca'),('PRG003','USR004','BK009',0.00,NULL,0,NULL,NULL,'Akan dibaca'),('PRG004','USR005','BK007',10.00,'2024-02-12',25,'2024-02-10',NULL,'Tidak selesai'),('PRG005','USR006','BK006',100.00,'2024-03-15',410,'2024-03-01','2024-03-15','Telah dibaca'),('PRG006','USR007','BK002',75.00,'2024-03-25',280,'2024-03-20',NULL,'Sedang dibaca'),('PRG007','USR008','BK005',0.00,NULL,0,NULL,NULL,'Akan dibaca'),('PRG008','USR009','BK004',5.00,'2024-04-16',10,'2024-04-15',NULL,'Tidak selesai'),('PRG009','USR010','BK010',100.00,'2024-05-05',120,'2024-05-01','2024-05-05','Telah dibaca'),('PRG010','USR001','BK008',40.00,'2024-05-12',180,'2024-05-10',NULL,'Sedang dibaca');
/*!40000 ALTER TABLE `reading_progress` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `ID_USER` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NAMA_DEPAN` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NAMA_BELAKANG` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `JENIS_KELAMIN` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `EMAIL` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `USERNAME` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PASSWORD` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `USER_ROLE` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NOMOR_PONSEL` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ALAMAT` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BIO` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ID_USER`),
  UNIQUE KEY `USERS_EMAIL_UN` (`EMAIL`),
  UNIQUE KEY `USERS_USERNAME_UN` (`USERNAME`),
  CONSTRAINT `USERS_GENDER_CK` CHECK (((`JENIS_KELAMIN` in (_utf8mb4'L',_utf8mb4'P')) or (`JENIS_KELAMIN` is null))),
  CONSTRAINT `USERS_ROLE_CK` CHECK (((`USER_ROLE` in (_utf8mb4'Admin',_utf8mb4'User',_utf8mb4'admin',_utf8mb4'anggota')) or (`USER_ROLE` is null)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('USR001','Admin','Pustaka','L','admin@pustaka.com','adminpustaka','$2y$12$iYAY01OO6d2pObrZqD3n7On877KKS3GUny.dXh56oY0IMNOMcI78u','Admin','081234567801','Pekanbaru','Administrator sistem.'),('USR002','Andi','Saputra','L','andi@email.com','andisaputra','$2y$12$4ehiq2FWUuarvd77mspoEu/kTV.mvBXwnasn7CG9uv2Dygf5bz0C6','anggota','081234567802','Pekanbaru','Mahasiswa yang gemar membaca.'),('USR003','Siti','Aulia','P','siti@email.com','sitiaulia','$2y$12$6WhyoqOA7ay0sCJKjrU39uhzWen82.v0TELnj.z5QOLjfSusZ6WKS','anggota','081234567803','Dumai','Menyukai buku fiksi.'),('USR004','Budi','Pratama','L','budi@email.com','budipratama','$2y$12$y7wr9yXIOLrdIf6knoOELuqhxDv5ehgDsp2C.MgGChnEQKEqiBbTG','anggota','081234567804','Bangkinang','Menyukai buku teknologi.'),('USR005','Dewi','Lestari','P','dewi@email.com','dewilestari','$2y$12$rbacQmpwbembQet/1WjWFOvhmdQ3yU5AW4q2s3OzFF7BI8HF7caG6','anggota','081234567805','Siak','Menyukai novel.'),('USR006','Rizky','Ramadhan','L','rizky@email.com','rizkyramadhan','$2y$12$jXtrzOTmymAwN7eCXrfzmuJ4Ma9YluTypLDgjqThAjWCOzSMlzXOa','anggota','081234567806','Rengat','Membaca buku pengembangan diri.'),('USR007','Putri','Maharani','P','putri@email.com','putrimaharani','$2y$12$3xGNveYswXfDimAt/a05pOW7TtdMXby5aYZ11bryMJzGH54KL3iIS','anggota','081234567807','Tembilahan','Menyukai buku pendidikan.'),('USR008','Fajar','Hidayat','L','fajar@email.com','fajarhidayat','$2y$12$S2.SrBkO35cpXQNSIeAuVeoaaumufRJUQ2rUm3zy0A2cRoFspcA6y','anggota','081234567808','Pelalawan','Menyukai buku sejarah.'),('USR009','Nadia','Permata','P','nadia@email.com','nadiapermata','$2y$12$9bWAJouCy1BztcN0LCocF.zy1UGtOR9RT4uZzFpMI2RTUGCBjf2Lm','anggota','081234567809','Bengkalis','Menyukai buku sains.'),('USR010','Dimas','Firmansyah','L','dimas@email.com','dimasfirmansyah','$2y$12$Z7Y4bSL6IjauudX.jjai9.h2aRglmDo.M..KSwWRq90cL3kxJ3od2','anggota','081234567810','Duri','Menyukai buku pemrograman.');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-30  5:51:24

