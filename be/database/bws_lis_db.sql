-- MySQL dump 10.13  Distrib 8.4.13, for Linux (x86_64)
--
-- Host: localhost    Database: bws_lis_db
-- ------------------------------------------------------
-- Server version	8.4.13

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
-- Table structure for table `approval_flow_steps`
--

DROP TABLE IF EXISTS `approval_flow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_flow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint unsigned NOT NULL,
  `step_order` int NOT NULL DEFAULT '1',
  `step_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_amount` decimal(15,2) DEFAULT '0.00',
  `max_amount` decimal(15,2) DEFAULT NULL,
  `sla_days` int NOT NULL DEFAULT '3',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_flow_steps_module_id_step_order_unique` (`module_id`,`step_order`),
  CONSTRAINT `approval_flow_steps_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `approval_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_flow_steps`
--

LOCK TABLES `approval_flow_steps` WRITE;
/*!40000 ALTER TABLE `approval_flow_steps` DISABLE KEYS */;
INSERT INTO `approval_flow_steps` VALUES (1,1,1,'Reviewer 1 - HC Business Partner (HCBP)','HCBP',0.00,NULL,2,1,'2026-11-19 04:48:28','2026-11-19 04:48:28'),(2,1,2,'Checker - HC Learning Head','HC Learning Head (Checker)',0.00,NULL,3,1,'2026-11-19 04:48:28','2026-11-19 04:48:28'),(3,1,3,'Approver Final - Kepala Divisi Human Capital','Kadiv HC',0.00,NULL,2,1,'2026-11-19 04:48:28','2026-11-19 04:48:28'),(4,2,1,'Reviewer 1 - Human Capital Business Partner (HCBP)','HCBP',0.00,NULL,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(5,2,2,'Checker - HC Learning Head (Review Anggaran & Metode)','HC Learning Head (Checker)',0.00,NULL,3,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(6,2,3,'Approver Final - Kepala Divisi Human Capital / Executive BoD','Super Admin',0.00,NULL,3,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(7,3,1,'Checker - Validasi Jadwal, Ruangan & Instruktur','HC Learning Head (Checker)',0.00,NULL,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(8,3,2,'Otorisasi Final Kuota & Anggaran Kelas','Super Admin',0.00,NULL,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(9,4,1,'Checker 1 - Verifikasi Bukti Kuitansi & Anggaran','HC Learning Head (Checker)',0.00,50000000.00,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(10,4,2,'Approver Final - Otorisasi Pengeluaran Kas (> Rp 50 Juta)','Super Admin',50000000.00,NULL,3,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(11,5,1,'Reviewer - Validasi Masa Berlaku & Kelayakan Peserta','HCBP',0.00,NULL,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(12,5,2,'Otorisasi Pendaftaran Ujian Sertifikasi LSP/OJK','HC Learning Head (Checker)',0.00,NULL,2,1,'2026-11-19 04:50:22','2026-11-19 04:50:22');
/*!40000 ALTER TABLE `approval_flow_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_histories`
--

DROP TABLE IF EXISTS `approval_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned NOT NULL,
  `step_order` int NOT NULL,
  `step_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approver_id` bigint unsigned DEFAULT NULL,
  `approver_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approver_nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `action_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_histories_approver_id_foreign` (`approver_id`),
  KEY `approval_histories_module_code_record_id_index` (`module_code`,`record_id`),
  CONSTRAINT `approval_histories_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_histories`
--

LOCK TABLES `approval_histories` WRITE;
/*!40000 ALTER TABLE `approval_histories` DISABLE KEYS */;
INSERT INTO `approval_histories` VALUES (1,'tna',1,1,'Pengajuan Dokumen TNA Divisi IT',2,'Hendro Santoso (Dept Head IT)','BWS-DHD-042','Dept Head','SUBMITTED','Usulan diklat prioritas TI tahun 2026 guna mendukung kepatuhan POJK 29/2022 dan ketahanan siber operasional cabang.',185000000.00,'2026-11-14 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(2,'tna',1,1,'Reviewer 1 - Human Capital Business Partner (HCBP)',3,'Siti Rahmawati (HCBP Lead)','BWS-HCP-019','HCBP','APPROVED','Kurikulum telah diverifikasi sesuai matrix kompetensi IT Bank Woori. Rekomendasi pagu disetujui Rp 175.000.000 dengan efisiensi paket kelas in-house.',175000000.00,'2026-11-17 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(3,'tna',2,1,'Pengajuan Dokumen TNA Divisi Kredit',2,'Agus Wicaksono (Dept Head Credit)','BWS-CRD-031','Dept Head','SUBMITTED','Pengajuan program pelatihan mitigasi risiko kredit komersial menyambut ekspansi kredit tahun 2026.',120000000.00,'2026-11-18 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(4,'tna',3,1,'Pengajuan Dokumen TNA Divisi Risiko',2,'Farhan Hidayat (Dept Head Risk)','BWS-RSK-015','Dept Head','SUBMITTED','Kewajiban sertifikasi berkala regulasi OJK untuk pejabat pemutus kredit dan operasional.',165000000.00,'2026-11-09 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(5,'tna',3,1,'Reviewer 1 - Human Capital Business Partner (HCBP)',3,'Siti Rahmawati (HCBP Lead)','BWS-HCP-019','HCBP','APPROVED','Mandatori regulasi OJK & BI, direkomendasikan prioritas utama tanpa penyesuaian pagu.',165000000.00,'2026-11-11 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(6,'tna',3,2,'Checker - HC Learning Head (Review Anggaran & Metode)',5,'Dr. Maya Wulandari (HC Learning Head)','BWS-TRN-002','HC Learning Head (Checker)','APPROVED','Pagu anggaran telah dicocokkan dengan alokasi tahunan L&D BWS 2026. Jadwal ujian LSPP terkonfirmasi.',165000000.00,'2026-11-14 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(7,'tna',3,3,'Approver Final - Kepala Divisi Human Capital / Executive BoD',1,'Budi Pratama (Super Admin)','BWS-ADM-001','Super Admin','APPROVED','Otorisasi final disahkan. Program resmi masuk dalam Annual Training Plan Bank Woori Saudara 2026.',165000000.00,'2026-11-18 04:50:22','2026-11-19 04:50:22','2026-11-19 04:50:22'),(8,'tna',4,1,'Review Kebutuhan & Rekomendasi HCBP',3,'Siti Rahmawati (HCBP Lead)','BWS-HCP-019','HCBP','APPROVED','Telah diverifikasi silang dengan usulan 28 Divisi dan Kantor Wilayah. Program diklat mencakup 5 rumpun lengkap secara seimbang.',1485000000.00,'2026-11-15 04:50:31','2026-11-19 04:50:31','2026-11-19 04:50:31'),(9,'tna',4,2,'Verifikasi Anggaran & Kurikulum Checker',5,'Dr. Maya Wulandari (HC Learning Head)','BWS-TRN-002','HC Learning Head (Checker)','APPROVED','Seluruh silabus 20 materi, vendor pengajar, dan kepatuhan regulasi OJK & BI telah sesuai standar BWS L&D. Pagu disetujui penuh.',1485000000.00,'2026-11-16 04:50:31','2026-11-19 04:50:31','2026-11-19 04:50:31'),(10,'tna',4,3,'Persetujuan Final Direksi / Executive',8,'Kwon Jin-Woo (Director of Human Capital & Ops)','BWS-EXEC-003','Executive','APPROVED','Disetujui penuh untuk penguatan ketahanan operasional, transformasi digital, dan mitigasi NPL cabang di tahun 2026.',1485000000.00,'2026-11-17 04:50:31','2026-11-19 04:50:31','2026-11-19 04:50:31'),(11,'program',7,1,'Reviewer 1 - Human Capital Business Partner (HCBP)',1,'Siti Rahmawati',NULL,'HCBP Lead','APPROVE','Usulan kurikulum dan rincian RAB sudah sesuai dengan plafon TNA Kepatuhan 2026. Disetujui untuk lanjut ke HC Learning Head.',NULL,'2026-11-18 04:50:46','2026-11-19 04:50:46','2026-11-19 04:50:46');
/*!40000 ALTER TABLE `approval_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_modules`
--

DROP TABLE IF EXISTS `approval_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FileCheck2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_modules_module_code_unique` (`module_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_modules`
--

LOCK TABLES `approval_modules` WRITE;
/*!40000 ALTER TABLE `approval_modules` DISABLE KEYS */;
INSERT INTO `approval_modules` VALUES (1,'program','Program Kerja & Rencana Anggaran Biaya (RAB) Diklat','Otorisasi pengesahan program kerja pelatihan tahunan dan rincian anggarannya (Multi-Approval Workflow)','BookOpen',1,'2026-11-19 04:48:28','2026-11-19 04:48:28'),(2,'tna','Usulan TNA & Annual Training Plan','Alur verifikasi dan pengesahan pagu anggaran kebutuhan diklat per divisi kerja Bank Woori Saudara.','Compass',1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(3,'class','Jadwal & Pembentukan Batch Kelas Diklat','Mekanisme dual control (Maker-Checker) sebelum kelas dipublikasikan dan undangan peserta dikirimkan.','GraduationCap',1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(4,'transaction','Transaksi Keuangan & Klaim Biaya Diklat','Dual control pengeluaran kas, reimbursement akomodasi peserta, dan pembayaran honorarium instruktur.','Receipt',1,'2026-11-19 04:50:22','2026-11-19 04:50:22'),(5,'certification','Sertifikasi Wajib Regulasi OJK & BI','Verifikasi eligibilitas kepatuhan lisensi profesi perbankan (BSMR, SKSP, Tresuri, APU-PPT).','ShieldAlert',1,'2026-11-19 04:50:22','2026-11-19 04:50:22');
/*!40000 ALTER TABLE `approval_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_entity_type_index` (`entity_type`),
  KEY `audit_logs_entity_id_index` (`entity_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'BWS-ADM-001','Budi Pratama (Super Admin)','Super Admin','SYSTEM_INIT','App\\Models\\User',1,'10.20.1.1','BWS-Enterprise-Core/v1.0 (Banking Security Node)',NULL,'{\"event\": \"Initial security baseline configuration with RBAC 8 roles and PostgreSQL encryption.\"}','571a7f94b510c37bbb04507e7c8bcfbd5304cfed08f9dfd2a3905e55df745fd5','2026-11-18 18:48:36'),(2,1,'BWS-DHD-042','Hendro Santoso (Dept Head IT)','Dept Head','TNA_PROPOSAL_SUBMITTED','App\\Models\\TnaProposal',1,'10.20.4.15','BWS-Enterprise-Core/v1.0 (Banking Security Node)',NULL,'{\"total_budget\": 185000000, \"proposal_code\": \"TNA-2026-IT-001\"}','462d737b1540a1fc4f98121c208ea7751dfc58c713e3ff6637696ba3a3fad6f7','2026-11-18 20:48:36'),(3,1,'BWS-HCP-019','Siti Rahmawati (HCBP Lead)','HCBP','TNA_HCBP_APPROVAL','App\\Models\\TnaProposal',1,'10.20.3.22','BWS-Enterprise-Core/v1.0 (Banking Security Node)',NULL,'{\"status\": \"APPROVED\", \"approved_budget\": 175000000}','3788eba380d5bb0de7572e436236ba2c49c4dce64844e57fdd448d4b42928bd3','2026-11-18 22:48:36'),(4,1,'BWS-TRN-101','Aditya Nugraha (HC Training Maker)','HC Training (Maker)','MAKER_CLASS_CREATION','App\\Models\\TrainingClass',1,'10.20.2.11','BWS-Enterprise-Core/v1.0 (Banking Security Node)',NULL,'{\"action\": \"Submitted for Dual-Control Checker approval.\", \"class_code\": \"CLS-2026-001\"}','95336b6207b159272dab4b0c8825b814aeee1354480f790285b1a6a956b76824','2026-11-19 00:48:36'),(5,1,'BWS-TRN-002','Dr. Maya Wulandari (HC Learning Head)','HC Learning Head (Checker)','CHECKER_CLASS_APPROVAL','App\\Models\\TrainingClass',1,'10.20.2.1','BWS-Enterprise-Core/v1.0 (Banking Security Node)',NULL,'{\"notes\": \"Verified syllabus & instructor credentials.\", \"decision\": \"APPROVED\", \"class_code\": \"CLS-2026-001\"}','2d201a850d7d7b3dc3ca6951d614aba20aff9d8fc947274d8f7f36557ece7a54','2026-11-19 02:48:36'),(6,NULL,NULL,NULL,NULL,'API_LOGIN_SUCCESS','App\\Models\\User',1,'172.31.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-ID) PowerShell/7.7.0',NULL,'{\"role\": \"Super Admin\", \"email\": \"admin@bws.co.id\", \"branch\": \"Kantor Pusat Sudirman\"}','0da2132240f99beed23fd92e8247af585b32695f0e9f63fae80548b0e507f277','2026-11-19 05:19:08'),(7,NULL,NULL,NULL,NULL,'API_LOGIN_SUCCESS','App\\Models\\User',1,'172.31.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-ID) PowerShell/7.7.0',NULL,'{\"role\": \"Super Admin\", \"email\": \"admin@bws.co.id\", \"branch\": \"Kantor Pusat Sudirman\"}','adaf9db4655e2fc0fd5581919c9182574b3983cd79cd35709c5a61b8f09e3bac','2026-11-19 05:19:18'),(8,NULL,NULL,NULL,NULL,'API_LOGIN_SUCCESS','App\\Models\\User',1,'172.31.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-ID) PowerShell/7.7.0',NULL,'{\"role\": \"Super Admin\", \"email\": \"admin@bws.co.id\", \"branch\": \"Kantor Pusat Sudirman\"}','a906f8b04ec8c41b3f9876e43ff83fc1f71727c354dda19a8ccd1c4dff46bae2','2026-11-19 05:20:14'),(9,NULL,NULL,NULL,NULL,'API_LOGIN_SUCCESS','App\\Models\\User',1,'172.31.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26200; en-ID) PowerShell/7.7.0',NULL,'{\"role\": \"Super Admin\", \"email\": \"admin@bws.co.id\", \"branch\": \"Kantor Pusat Sudirman\"}','c70f421b0fe472cb8aebf604274aac80817dad146ad05e28a6348378ab079ef1','2026-11-19 05:21:28'),(10,NULL,NULL,NULL,NULL,'API_LOGIN_SUCCESS','App\\Models\\User',1,'172.31.0.4','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',NULL,'{\"role\": \"Super Admin\", \"email\": \"admin@bws.co.id\", \"branch\": \"Kantor Pusat Sudirman\"}','44bebd9a87217253a0a9f2a169c59afd029f76f9834fcf9083f8866b30ffe410','2026-11-19 09:57:56'),(11,1,NULL,NULL,NULL,'DELETE_TNA_PROPOSAL','TnaProposal',2,'172.31.0.4',NULL,NULL,NULL,NULL,'2026-11-20 13:58:55'),(12,1,NULL,NULL,NULL,'DELETE_TNA_PROPOSAL','TnaProposal',3,'172.31.0.4',NULL,NULL,NULL,NULL,'2026-11-20 13:58:57'),(13,1,NULL,NULL,NULL,'DELETE_TNA_PROPOSAL','TnaProposal',4,'172.31.0.4',NULL,NULL,NULL,NULL,'2026-11-20 13:59:00');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Building2',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#0067AC',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'CAT-OPS','Operasional & Layanan Perbankan','Standardisasi layanan frontliner cabang, kepatuhan transaksi kas, kliring BI-FAST, trade finance, dan tresuri.','Building2','#0067AC',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(2,'CAT-CRD','Kredit & Pembiayaan Komersial','Peningkatan kompetensi analisa kelayakan pembiayaan, mitigasi risiko kredit, restrukturisasi, dan penanganan NPL.','CreditCard','#003876',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(3,'CAT-RSK','Manajemen Risiko, Kepatuhan & Regulasi OJK','Sertifikasi wajib regulator perbankan OJK & BI, kepatuhan Anti-Pencucian Uang (APU-PPT), dan tata kelola GCG.','ShieldCheck','#002855',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(4,'CAT-IT','Teknologi Informasi & Keamanan Siber','Pemenuhan POJK 29/2022 terkait ketahanan siber, administrasi core banking system, digital banking, dan cloud IT.','Terminal','#00A0E9',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(5,'CAT-LDR','Kepemimpinan, Manajerial & Budaya Kerja','Pengembangan kader pimpinan bank, kepemimpinan branch manager, coaching & mentoring, serta budaya integritas BWS.','Award','#0067AC',1,5,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_attendances`
--

DROP TABLE IF EXISTS `class_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_class_id` bigint unsigned NOT NULL,
  `class_session_id` bigint unsigned DEFAULT NULL,
  `class_enrollment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HADIR',
  `check_in_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cls_att_unique_session_enrollment` (`training_class_id`,`class_session_id`,`class_enrollment_id`),
  KEY `class_attendances_class_session_id_foreign` (`class_session_id`),
  KEY `class_attendances_class_enrollment_id_foreign` (`class_enrollment_id`),
  KEY `class_attendances_user_id_foreign` (`user_id`),
  KEY `class_attendances_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `class_attendances_class_enrollment_id_foreign` FOREIGN KEY (`class_enrollment_id`) REFERENCES `class_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_attendances_class_session_id_foreign` FOREIGN KEY (`class_session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `class_attendances_training_class_id_foreign` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_attendances`
--

LOCK TABLES `class_attendances` WRITE;
/*!40000 ALTER TABLE `class_attendances` DISABLE KEYS */;
INSERT INTO `class_attendances` VALUES (1,3,1,2,1,'2026-02-10','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(2,3,2,2,1,'2026-02-11','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(3,3,3,2,1,'2026-02-12','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(4,3,1,3,1,'2026-02-10','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(5,3,2,3,1,'2026-02-11','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(6,3,3,3,1,'2026-02-12','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(7,3,1,4,1,'2026-02-10','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(8,3,2,4,1,'2026-02-11','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(9,3,3,4,1,'2026-02-12','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(10,3,1,5,1,'2026-02-10','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(11,3,2,5,1,'2026-02-11','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(12,3,3,5,1,'2026-02-12','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(13,3,1,6,1,'2026-02-10','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(14,3,2,6,1,'2026-02-11','IZIN',NULL,'Dispensasi mendesak operasional cabang',1,'2026-11-19 04:51:07','2026-11-19 04:51:07'),(15,3,3,6,1,'2026-02-12','HADIR','08:15 WIB','Hadir tepat waktu',1,'2026-11-19 04:51:07','2026-11-19 04:51:07');
/*!40000 ALTER TABLE `class_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_enrollments`
--

DROP TABLE IF EXISTS `class_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_class_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `attendance_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGISTERED',
  `pre_test_score` decimal(5,2) DEFAULT NULL,
  `post_test_score` decimal(5,2) DEFAULT NULL,
  `task_score` decimal(5,2) DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `elapse_progress_pct` int NOT NULL DEFAULT '0',
  `completion_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOT_STARTED',
  `task_submission_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `task_submitted_at` timestamp NULL DEFAULT NULL,
  `task_feedback` text COLLATE utf8mb4_unicode_ci,
  `certificate_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participant_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `eval_instructor_rating` int DEFAULT NULL,
  `eval_material_rating` int DEFAULT NULL,
  `eval_facility_rating` int DEFAULT NULL,
  `eval_notes` text COLLATE utf8mb4_unicode_ci,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_issued_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_enrollments_certificate_id_unique` (`certificate_id`),
  KEY `class_enrollments_training_class_id_foreign` (`training_class_id`),
  KEY `class_enrollments_user_id_foreign` (`user_id`),
  CONSTRAINT `class_enrollments_training_class_id_foreign` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_enrollments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_enrollments`
--

LOCK TABLES `class_enrollments` WRITE;
/*!40000 ALTER TABLE `class_enrollments` DISABLE KEYS */;
INSERT INTO `class_enrollments` VALUES (1,1,7,'PRESENT',65.00,88.50,92.00,89.20,100,'PASSED','/secure_storage/submissions/action_plan_bws_emp_9821.pdf','2026-11-18 22:48:36','Analisis mitigasi brute-force attack dan pemetaan flow OJK sangat komprehensif. Lulus dengan predikat Sangat Baik.','BWS-CERT-2026-CYBER-8812','/secure_storage/certificates/cert_bws_emp_9821.pdf','2026-11-19 04:48:36','2026-11-19 04:48:36',NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,3,1,'PRESENT',62.00,92.00,90.00,85.40,0,'PASSED',NULL,NULL,NULL,'CERT/BWS-LIS/2026/RMS001/0101',NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2021-041','Budi Santoso','Risk Management','Kantor Pusat','Market Risk Analyst',100.00,5,NULL,NULL,'Sangat kompeten dan memahami perhitungan stress testing.','B','CERT/BWS-LIS/2026/RMS001/0101','2026-11-17 04:51:07'),(3,3,1,'PRESENT',58.00,88.00,85.00,81.10,0,'PASSED',NULL,NULL,NULL,'CERT/BWS-LIS/2026/RMS001/0102',NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2022-089','Siti Rahmawati','Operational Risk','KC Jakarta Sudirman','Risk Officer',100.00,5,NULL,NULL,'Pemahaman matriks risiko operasional sangat baik.','B','CERT/BWS-LIS/2026/RMS001/0102','2026-11-17 04:51:07'),(4,3,1,'PRESENT',65.00,85.00,84.00,80.70,0,'PASSED',NULL,NULL,NULL,'CERT/BWS-LIS/2026/RMS001/0103',NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2020-015','Ahmad Fadillah','Credit Risk','KC Bandung Asia Afrika','Credit Reviewer',100.00,5,NULL,NULL,'Aktif dalam studi kasus restrukturisasi kredit.','B','CERT/BWS-LIS/2026/RMS001/0103','2026-11-17 04:51:07'),(5,3,1,'PRESENT',70.00,94.00,92.00,88.60,0,'PASSED',NULL,NULL,NULL,'CERT/BWS-LIS/2026/RMS001/0104',NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2023-102','Dewi Lestari','Internal Audit','Kantor Pusat','Audit Specialist',100.00,5,NULL,NULL,'Nilai post-test tertinggi, rekomendasi instruktur internal.','B','CERT/BWS-LIS/2026/RMS001/0104','2026-11-17 04:51:07'),(6,3,1,'PERMISSION',50.00,72.00,70.00,67.00,0,'FAILED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2019-034','Rendra Pratama','Compliance','KC Surabaya Darmo','Compliance Officer',66.70,4,NULL,NULL,'Perlu sesi remedial untuk mencapai passing grade 75.','D',NULL,NULL),(7,4,1,'PRESENT',64.00,86.00,NULL,86.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2024-001','Rian Hidayat','Risk Management','Kantor Pusat BWS','Risk Analyst Officer',100.00,5,NULL,NULL,'Peserta sangat proaktif.','B',NULL,NULL),(8,4,1,'PRESENT',64.00,86.00,NULL,86.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2024-002','Siti Nurhaliza','Risk Management','Kantor Pusat BWS','Market Risk Specialist',100.00,5,NULL,NULL,'Peserta sangat proaktif.','B',NULL,NULL),(9,4,1,'PRESENT',64.00,86.00,NULL,86.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2024-003','Bambang Pratama','Compliance','Cabang Surabaya','Compliance Officer',100.00,5,NULL,NULL,'Peserta sangat proaktif.','B',NULL,NULL),(10,5,1,'PRESENT',68.00,95.00,NULL,95.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-IT-001','Ahmad Fajar','IT Security','Kantor Pusat BWS','SOC Analyst',100.00,5,NULL,NULL,'Sangat mahir dalam konfigurasi firewall dan incident handling.','A','CERT/BWS-LIS/2026/SEC002/0201','2026-11-19 04:51:07'),(11,5,1,'PRESENT',68.00,95.00,NULL,95.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-IT-002','Dimas Anggara','IT Infrastructure','Kantor Pusat BWS','Network Engineer',100.00,5,NULL,NULL,'Sangat mahir dalam konfigurasi firewall dan incident handling.','A','CERT/BWS-LIS/2026/SEC002/0201','2026-11-19 04:51:07'),(12,5,1,'PRESENT',68.00,95.00,NULL,95.00,0,'PASSED',NULL,NULL,NULL,NULL,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','BWS-2023-055','Nadia Putri','Core Banking Apps','Kantor Pusat','Application Developer',100.00,5,NULL,NULL,'Sangat mahir dalam konfigurasi firewall dan incident handling.','A','CERT/BWS-LIS/2026/SEC002/0201','2026-11-19 04:51:07');
/*!40000 ALTER TABLE `class_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_evaluations`
--

DROP TABLE IF EXISTS `class_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_evaluations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_class_id` bigint unsigned NOT NULL,
  `class_enrollment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `instructor_rating` int NOT NULL DEFAULT '5',
  `material_rating` int NOT NULL DEFAULT '5',
  `facility_rating` int NOT NULL DEFAULT '5',
  `recommendation_rating` int NOT NULL DEFAULT '5',
  `feedback_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cls_eval_unique_enrollment` (`training_class_id`,`class_enrollment_id`),
  KEY `class_evaluations_class_enrollment_id_foreign` (`class_enrollment_id`),
  KEY `class_evaluations_user_id_foreign` (`user_id`),
  CONSTRAINT `class_evaluations_class_enrollment_id_foreign` FOREIGN KEY (`class_enrollment_id`) REFERENCES `class_enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_evaluations_training_class_id_foreign` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_evaluations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_evaluations`
--

LOCK TABLES `class_evaluations` WRITE;
/*!40000 ALTER TABLE `class_evaluations` DISABLE KEYS */;
INSERT INTO `class_evaluations` VALUES (1,3,2,1,5,5,5,5,'Sangat kompeten dan memahami perhitungan stress testing.','2026-11-19 04:51:07','2026-11-19 04:51:07'),(2,3,3,1,5,5,5,5,'Pemahaman matriks risiko operasional sangat baik.','2026-11-19 04:51:07','2026-11-19 04:51:07'),(3,3,4,1,5,5,5,5,'Aktif dalam studi kasus restrukturisasi kredit.','2026-11-19 04:51:07','2026-11-19 04:51:07'),(4,3,5,1,5,5,5,5,'Nilai post-test tertinggi, rekomendasi instruktur internal.','2026-11-19 04:51:07','2026-11-19 04:51:07'),(5,3,6,1,4,5,5,5,'Perlu sesi remedial untuk mencapai passing grade 75.','2026-11-19 04:51:07','2026-11-19 04:51:07');
/*!40000 ALTER TABLE `class_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_sessions`
--

DROP TABLE IF EXISTS `class_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_class_id` bigint unsigned NOT NULL,
  `session_number` int NOT NULL DEFAULT '1',
  `session_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_date` date NOT NULL,
  `start_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '08:30',
  `end_time` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '16:30',
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trainer_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_or_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_sessions_training_class_id_foreign` (`training_class_id`),
  CONSTRAINT `class_sessions_training_class_id_foreign` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_sessions`
--

LOCK TABLES `class_sessions` WRITE;
/*!40000 ALTER TABLE `class_sessions` DISABLE KEYS */;
INSERT INTO `class_sessions` VALUES (1,3,1,'Hari ke-1: Prinsip Manajemen Risiko & POJK','2026-02-10','08:30','16:30','Regulasi OJK & Bank Indonesia Terkait Risk Governance','Dr. Bambang Soedjarwo, M.M. (Senior Banking Trainer)','BWS Corporate Learning Center Lt. 8, Ruang Diklat 801','2026-11-19 04:51:07','2026-11-19 04:51:07'),(2,3,2,'Hari ke-2: Analisis Risiko Kredit & Pasar','2026-02-11','08:30','16:30','Studi Kasus Mitigasi NPL & Stress Testing Perbankan','Dr. Bambang Soedjarwo, M.M. (Senior Banking Trainer)','BWS Corporate Learning Center Lt. 8, Ruang Diklat 801','2026-11-19 04:51:07','2026-11-19 04:51:07'),(3,3,3,'Hari ke-3: Workshop & Simulasi Ujian BSMR','2026-02-12','08:30','16:30','Simulasi Soal Try-Out BSMR & Pembahasan Komprehensif','Dr. Bambang Soedjarwo, M.M. (Senior Banking Trainer)','BWS Corporate Learning Center Lt. 8, Ruang Diklat 801','2026-11-19 04:51:07','2026-11-19 04:51:07'),(4,4,1,'Sesi 1: Modul Pembelajaran Regional Jabar 1','2026-03-16','09:00','16:00','Penerapan Manajemen Risiko Regional Sesi 1','Ir. Hendra Gunawan, M.M. / Tim Pusdiklat BWS','KC Bandung Asia Afrika & MS Teams Live','2026-11-19 04:51:07','2026-11-19 04:51:07'),(5,4,2,'Sesi 2: Modul Pembelajaran Regional Jabar 2','2026-03-17','09:00','16:00','Penerapan Manajemen Risiko Regional Sesi 2','Ir. Hendra Gunawan, M.M. / Tim Pusdiklat BWS','KC Bandung Asia Afrika & MS Teams Live','2026-11-19 04:51:07','2026-11-19 04:51:07'),(6,4,3,'Sesi 3: Modul Pembelajaran Regional Jabar 3','2026-03-18','09:00','16:00','Penerapan Manajemen Risiko Regional Sesi 3','Ir. Hendra Gunawan, M.M. / Tim Pusdiklat BWS','KC Bandung Asia Afrika & MS Teams Live','2026-11-19 04:51:07','2026-11-19 04:51:07'),(7,5,1,'Sesi 1: Hands-On Lab Cyber Security Day 1','2026-04-06','08:30','17:00','Simulasi Penetrasi & Defense Sesi 1','Trainer Certified Information Security Auditor (CISA)','BWS Innovation Lab Lt. 5 & Virtual Cyber Range','2026-11-19 04:51:07','2026-11-19 04:51:07'),(8,5,2,'Sesi 2: Hands-On Lab Cyber Security Day 2','2026-04-07','08:30','17:00','Simulasi Penetrasi & Defense Sesi 2','Trainer Certified Information Security Auditor (CISA)','BWS Innovation Lab Lt. 5 & Virtual Cyber Range','2026-11-19 04:51:07','2026-11-19 04:51:07'),(9,5,3,'Sesi 3: Hands-On Lab Cyber Security Day 3','2026-04-08','08:30','17:00','Simulasi Penetrasi & Defense Sesi 3','Trainer Certified Information Security Auditor (CISA)','BWS Innovation Lab Lt. 5 & Virtual Cyber Range','2026-11-19 04:51:07','2026-11-19 04:51:07');
/*!40000 ALTER TABLE `class_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_transactions`
--

DROP TABLE IF EXISTS `financial_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trx_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `training_class_id` bigint unsigned DEFAULT NULL,
  `tna_proposal_id` bigint unsigned DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `maker_id` bigint unsigned NOT NULL,
  `checker_id` bigint unsigned DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING_APPROVAL',
  `checker_notes` text COLLATE utf8mb4_unicode_ci,
  `receipt_attachment_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_transactions_trx_number_unique` (`trx_number`),
  KEY `financial_transactions_training_class_id_foreign` (`training_class_id`),
  KEY `financial_transactions_tna_proposal_id_foreign` (`tna_proposal_id`),
  KEY `financial_transactions_maker_id_foreign` (`maker_id`),
  KEY `financial_transactions_checker_id_foreign` (`checker_id`),
  CONSTRAINT `financial_transactions_checker_id_foreign` FOREIGN KEY (`checker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `financial_transactions_maker_id_foreign` FOREIGN KEY (`maker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `financial_transactions_tna_proposal_id_foreign` FOREIGN KEY (`tna_proposal_id`) REFERENCES `tna_proposals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_training_class_id_foreign` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_transactions`
--

LOCK TABLES `financial_transactions` WRITE;
/*!40000 ALTER TABLE `financial_transactions` DISABLE KEYS */;
INSERT INTO `financial_transactions` VALUES (1,'TRX-2026-0081',1,1,'Vendor Fee & Certification Exam','Pembayaran vendor materi sertifikasi keamanan data perbankan & voucher ujian peserta Batch 01.',45000000.00,4,5,'APPROVED','Nominal tagihan sesuai dengan kontrak PO No. PO/BWS/HC/2026/012.','/secure_storage/invoices/inv_vendor_cyber_0081.pdf','2026-11-18 16:48:36','2026-11-19 00:48:36','2026-11-19 04:48:36','2026-11-19 04:48:36'),(2,'TRX-2026-0082',2,1,'Learning Materials & LMS Hosting','Lisensi modul interaktif ELAPSE e-Learning untuk 50 peserta frontliner.',18500000.00,4,NULL,'PENDING_APPROVAL',NULL,NULL,NULL,NULL,'2026-11-19 04:48:36','2026-11-19 04:48:36');
/*!40000 ALTER TABLE `financial_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_years`
--

DROP TABLE IF EXISTS `fiscal_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiscal_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_budget_cap` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fiscal_years_year_unique` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_years`
--

LOCK TABLES `fiscal_years` WRITE;
/*!40000 ALTER TABLE `fiscal_years` DISABLE KEYS */;
INSERT INTO `fiscal_years` VALUES (1,2024,'Tahun Anggaran & Diklat 2024','Stabilisasi Operasional Cabang & Peningkatan Kualitas Kredit Pasca-Pandemi','2024-01-01','2024-12-31',3800000000.00,'TUTUP_BUKU',0,'Tutup buku tahunan audit BWS 2024 telah diselesaikan dan diaudit OJK.','2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(2,2025,'Tahun Anggaran & Diklat 2025','Modernisasi Infrastruktur Core Banking & Penguatan Budaya Kepatuhan','2025-01-01','2025-12-31',4500000000.00,'TUTUP_BUKU',0,'Realisasi anggaran diklat 2025 tercapai 94.2% dengan indeks kepatuhan 99.8%.','2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(3,2026,'Tahun Anggaran & Diklat 2026','Akselerasi Transformasi Digital, Penguatan Keamanan Siber POJK 29/2022 & Mitigasi NPL','2026-01-01','2026-12-31',5250000000.00,'AKTIF',1,'Tahun anggaran berjalan (Current Fiscal Year) untuk seluruh realisasi program diklat BWS.','2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(4,2027,'Tahun Anggaran & Diklat 2027','Next-Gen Intelligent Banking, AI Productivity & Sustainable ESG Capability','2027-01-01','2027-12-31',6000000000.00,'PERENCANAAN',0,'Siklus pengusulan TNA tahun depan (Future TNA Planning Cycle).','2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(5,2020,'Tahun Anggaran 2020','Transformasi Kapabilitas Digital & Bisnis Perbankan BWS 2020','2020-01-01','2020-12-31',1000000000.00,'TUTUP_BUKU',0,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55',NULL),(6,2021,'Tahun Anggaran 2021','Transformasi Kapabilitas Digital & Bisnis Perbankan BWS 2021','2021-01-01','2021-12-31',1050000000.00,'TUTUP_BUKU',0,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55',NULL),(7,2022,'Tahun Anggaran 2022','Transformasi Kapabilitas Digital & Bisnis Perbankan BWS 2022','2022-01-01','2022-12-31',1102500000.00,'TUTUP_BUKU',0,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55',NULL),(8,2023,'Tahun Anggaran 2023','Transformasi Kapabilitas Digital & Bisnis Perbankan BWS 2023','2023-01-01','2023-12-31',1157625000.00,'TUTUP_BUKU',0,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55',NULL);
/*!40000 ALTER TABLE `fiscal_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `karyawan`
--

DROP TABLE IF EXISTS `karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `karyawan` (
  `karyawan_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_panggilan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jk` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departemen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kantor_cabang` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `golongan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `st_kerja` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
  `tgl_masuk` date DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Employee / Participant',
  `target_jam_pelatihan` int NOT NULL DEFAULT '20',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`karyawan_id`),
  UNIQUE KEY `karyawan_nik_unique` (`nik`),
  UNIQUE KEY `karyawan_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `karyawan`
--

LOCK TABLES `karyawan` WRITE;
/*!40000 ALTER TABLE `karyawan` DISABLE KEYS */;
INSERT INTO `karyawan` VALUES (1,'BWS-ADM-001','Budi Pratama (Super Admin)','Budi','L','admin@bws.co.id','+62 811-1001-001','IT Security & Governance','Super Admin','Kantor Pusat Sudirman','Officer','AKTIF','2023-11-19',NULL,'Super Admin',20,1,'2026-11-19 04:48:33','2026-11-19 04:48:33',NULL),(2,'BWS-DHD-042','Hendro Santoso (Dept Head IT)','Hendro','L','depthead.it@bws.co.id','+62 812-2002-042','Information Technology','Dept Head','Kantor Pusat Sudirman','Senior Manager','AKTIF','2023-11-19',NULL,'Dept Head',20,1,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(3,'BWS-HCP-019','Siti Rahmawati (HCBP Lead)','Siti','P','hcbp@bws.co.id','+62 813-3003-019','Human Capital Business Partner','HCBP','Kantor Pusat Sudirman','Officer','AKTIF','2023-11-19',NULL,'HCBP',20,1,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(4,'BWS-TRN-101','Aditya Nugraha (HC Training Maker)','Aditya','L','maker.training@bws.co.id','+62 814-4004-101','HC Learning & Development','HC Training (Maker)','Kantor Pusat Sudirman','Officer','AKTIF','2023-11-19',NULL,'HC Training (Maker)',20,1,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(5,'BWS-TRN-002','Dr. Maya Wulandari (HC Learning Head)','Dr.','P','checker.head@bws.co.id','+62 815-5005-002','HC Learning & Development','HC Learning Head (Checker)','Kantor Pusat Sudirman','Senior Manager','AKTIF','2023-11-19',NULL,'HC Learning Head (Checker)',20,1,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(6,'BWS-FAC-014','Bambang Soedjarwo (Senior Banking Trainer)','Bambang','L','facilitator@bws.co.id','+62 816-6006-014','BWS Corporate Academy','Facilitator / Trainer','Learning Center Bandung','Officer','AKTIF','2023-11-19',NULL,'Facilitator / Trainer',20,1,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL),(7,'BWS-EMP-9821','Yazid Bustomi (Credit & Operations Officer)','Yazid','L','participant@bws.co.id','+62 817-7007-821','Branch Banking Operations','Employee / Participant','KCU Bandung Merdeka','Officer','AKTIF','2023-11-19',NULL,'Employee / Participant',20,1,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL),(8,'BWS-EXEC-003','Kwon Jin-Woo (Director of Human Capital & Ops)','Kwon','L','bod.executive@bws.co.id','+62 818-8008-003','Board of Directors','Executive','Kantor Pusat Sudirman','Executive','AKTIF','2023-11-19',NULL,'Executive',20,1,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL);
/*!40000 ALTER TABLE `karyawan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_10_160314_create_permission_tables',1),(5,'2026_11_10_160540_add_banking_fields_to_users_table',1),(6,'2026_11_10_160551_create_audit_logs_table',1),(7,'2026_11_10_160601_create_lis_core_tables',1),(8,'2026_11_12_030358_create_personal_access_tokens_table',1),(9,'2026_11_16_220000_create_karyawan_table',1),(10,'2026_11_17_000000_create_user_and_rbac_tables',1),(11,'2026_11_17_084832_add_soft_deletes_to_users_and_karyawan_tables',1),(12,'2026_11_17_170000_create_multi_approval_and_enhance_tna_tables',1),(13,'2026_11_18_180000_create_categories_sub_categories_and_years_tables',1),(14,'2026_11_18_230000_enhance_training_programs_table',1),(15,'2026_11_19_110000_create_class_sessions_attendance_and_evaluations_tables',1),(16,'2026_11_20_120000_align_tna_and_programs_to_excel_template',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(3,'App\\Models\\User',3),(4,'App\\Models\\User',4),(5,'App\\Models\\User',5),(6,'App\\Models\\User',6),(7,'App\\Models\\User',7),(8,'App\\Models\\User',8);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_dashboard','web','2026-11-19 04:48:32','2026-11-19 04:48:32'),(2,'manage_tna_proposals','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(3,'approve_tna_proposals','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(4,'manage_programs','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(5,'create_class_maker','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(6,'approve_class_checker','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(7,'grade_participants','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(8,'view_own_learning','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(9,'submit_task','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(10,'manage_financials_maker','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(11,'approve_financials_checker','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(12,'view_executive_reports','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(13,'view_audit_trail','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(14,'system_administration','web','2026-11-19 04:48:33','2026-11-19 04:48:33');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'bws-lis-token','371bfb38a25748cf677284536f05184540d52272e17ee0c46cc4b5a34d0e9bb4','[\"*\"]','2026-11-19 05:19:09',NULL,'2026-11-19 05:19:08','2026-11-19 05:19:09'),(2,'App\\Models\\User',1,'bws-lis-token','eedb3157cdb9cdc15d8c36c45bb9e4f06b7cb4d999c8936ed03b39da8e07f4de','[\"*\"]','2026-11-19 05:19:18',NULL,'2026-11-19 05:19:18','2026-11-19 05:19:18'),(3,'App\\Models\\User',1,'bws-lis-token','2a961af56c34e5489cbadd7d6e20b9df022da303f479973268690ad566994d51','[\"*\"]','2026-11-19 05:20:15',NULL,'2026-11-19 05:20:14','2026-11-19 05:20:15'),(4,'App\\Models\\User',1,'bws-lis-token','e13d47a988d204c146d184e8e3284a7aadc5f1bc3224b48a3d8519cce5a109bd','[\"*\"]','2026-11-19 05:21:29',NULL,'2026-11-19 05:21:28','2026-11-19 05:21:29'),(5,'App\\Models\\User',1,'bws-lis-token','ebc2d55c99f78fe4db2b927b6fca017f3e0754ed62ec3de8a19b8ce81285ad54','[\"*\"]','2026-11-20 13:59:01',NULL,'2026-11-19 09:57:56','2026-11-20 13:59:01');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_budget_items`
--

DROP TABLE IF EXISTS `program_budget_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_budget_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_program_id` bigint unsigned NOT NULL,
  `item_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Paket',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_budget_items_training_program_id_foreign` (`training_program_id`),
  CONSTRAINT `program_budget_items_training_program_id_foreign` FOREIGN KEY (`training_program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_budget_items`
--

LOCK TABLES `program_budget_items` WRITE;
/*!40000 ALTER TABLE `program_budget_items` DISABLE KEYS */;
INSERT INTO `program_budget_items` VALUES (1,5,'SPEAKER','Honorarium Instruktur BSMR Senior Bersertifikasi',2.00,'Hari',5000000.00,10000000.00,'2 Hari x Rp 5.000.000','2026-11-19 04:50:46','2026-11-19 04:50:46'),(2,5,'VENUE','Sewa Training Room & Multimedia AV System',2.00,'Hari',2500000.00,5000000.00,'Gedung Diklat BWS','2026-11-19 04:50:46','2026-11-19 04:50:46'),(3,5,'LUNCH','Konsumsi Makan Siang Prasmanan Peserta & Trainer',50.00,'Porsi',75000.00,3750000.00,'25 Pax x 2 Hari','2026-11-19 04:50:46','2026-11-19 04:50:46'),(4,5,'SNACK','Coffee Break Pagi & Sore (Snack Box)',100.00,'Porsi',30000.00,3000000.00,'25 Pax x 4 Sesi','2026-11-19 04:50:46','2026-11-19 04:50:46'),(5,5,'MATERIALS','Buku Modul BSMR, Training Kit, Binder & ATK',25.00,'Paket',200000.00,5000000.00,'Kit Lengkap','2026-11-19 04:50:46','2026-11-19 04:50:46'),(6,5,'CERTIFICATION','Biaya Registrasi Ujian Sertifikasi BSMR Level 1 & 2',25.00,'Peserta',1500000.00,37500000.00,'Ujian Resmi LSPP','2026-11-19 04:50:46','2026-11-19 04:50:46'),(7,5,'TRANSPORT','Transportasi & Operasional Tim Instruktur External',1.00,'Paket',2500000.00,2500000.00,'PP Jakarta-Bandung','2026-11-19 04:50:46','2026-11-19 04:50:46'),(8,6,'SPEAKER','Trainer Certified Information Security Auditor (CISA)',1.00,'Paket',15000000.00,15000000.00,'3 Hari Workshop','2026-11-19 04:50:46','2026-11-19 04:50:46'),(9,6,'MATERIALS','Lisensi Hands-on Virtual Cyber Range Simulation Lab',30.00,'Lisensi',350000.00,10500000.00,'Akses 30 hari','2026-11-19 04:50:46','2026-11-19 04:50:46'),(10,6,'LUNCH','Makan Siang Sesi Tatap Muka (Offline Day)',30.00,'Porsi',60000.00,1800000.00,'Hari ke-3 Workshop','2026-11-19 04:50:46','2026-11-19 04:50:46'),(11,6,'SNACK','Snack Box & Kopi',60.00,'Porsi',25000.00,1500000.00,'2 Sesi','2026-11-19 04:50:46','2026-11-19 04:50:46'),(12,7,'SPEAKER','Narasumber Ahli Kepatuhan APU-PPT Eks-Regulator',1.00,'Paket',12000000.00,12000000.00,'2 Hari Webinar','2026-11-19 04:50:46','2026-11-19 04:50:46'),(13,7,'MATERIALS','Digital Handout & Modul Studi Kasus BWS',40.00,'Akses',100000.00,4000000.00,'Akses Cloud','2026-11-19 04:50:46','2026-11-19 04:50:46'),(14,7,'OTHER','Biaya Ujian Post-Test & Sertifikat Digital Berbarcode',40.00,'Pax',90000.00,3600000.00,'Verifikasi Otomatis','2026-11-19 04:50:46','2026-11-19 04:50:46');
/*!40000 ALTER TABLE `program_budget_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_participants`
--

DROP TABLE IF EXISTS `program_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `training_program_id` bigint unsigned NOT NULL,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOMINATED',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_participants_training_program_id_foreign` (`training_program_id`),
  CONSTRAINT `program_participants_training_program_id_foreign` FOREIGN KEY (`training_program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_participants`
--

LOCK TABLES `program_participants` WRITE;
/*!40000 ALTER TABLE `program_participants` DISABLE KEYS */;
INSERT INTO `program_participants` VALUES (1,5,'BWS-2021-041','Budi Santoso','Risk Management','Kantor Pusat','Market Risk Analyst',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(2,5,'BWS-2022-089','Siti Rahmawati','Operational Risk','KC Jakarta Sudirman','Risk Officer',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(3,5,'BWS-2020-015','Ahmad Fadillah','Credit Risk','KC Bandung Asia Afrika','Credit Reviewer',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(4,5,'BWS-2023-102','Dewi Lestari','Internal Audit','Kantor Pusat','Audit Specialist',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(5,5,'BWS-2019-034','Rendra Pratama','Compliance','KC Surabaya Darmo','Compliance Officer',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(6,6,'BWS-2024-001','Fajar Nugraha','IT Infrastructure','Kantor Pusat','Network Engineer',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(7,6,'BWS-2023-055','Nadia Putri','Core Banking Apps','Kantor Pusat','Application Developer',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46'),(8,6,'BWS-2022-019','Bambang Irawan','IT Security','Kantor Pusat','SOC Analyst',NULL,'NOMINATED','2026-11-19 04:50:46','2026-11-19 04:50:46');
/*!40000 ALTER TABLE `program_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(1,2),(2,2),(12,2),(1,3),(2,3),(3,3),(12,3),(1,4),(4,4),(5,4),(10,4),(1,5),(6,5),(11,5),(12,5),(13,5),(1,6),(7,6),(1,7),(8,7),(9,7),(1,8),(12,8);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(2,'Dept Head','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(3,'HCBP','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(4,'HC Training (Maker)','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(5,'HC Learning Head (Checker)','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(6,'Facilitator / Trainer','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(7,'Employee / Participant','web','2026-11-19 04:48:33','2026-11-19 04:48:33'),(8,'Executive','web','2026-11-19 04:48:33','2026-11-19 04:48:33');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('4CEJLsHIP4O0Zyx9OiPXBsQmCk50zipq7B2D2snN',NULL,'172.31.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ0cmhleU1PdnhlVHZESnF0QWE2NjN1dDJoS29rQnlJb1Z3TWM2Qk1nIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAxIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1795082241);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_categories`
--

DROP TABLE IF EXISTS `sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `target_competency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_categories_code_unique` (`code`),
  KEY `sub_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `sub_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_categories`
--

LOCK TABLES `sub_categories` WRITE;
/*!40000 ALTER TABLE `sub_categories` DISABLE KEYS */;
INSERT INTO `sub_categories` VALUES (1,1,'SUB-OPS-001','Frontliner, Teller & Service Excellence','Standar pelayanan nasabah BWS, ketelitian penanganan uang tunai, pencegahan selisih kas, dan service mindset.','SOP Kas, Deteksi Uang Palsu, Handling Complain & Service Mindset',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(2,1,'SUB-OPS-002','Back Office, Kliring & Sistem Pembayaran (SKNBI & BI-FAST)','Operasional administrasi cabang, kliring warkat, rekonsiliasi harian, dan kepatuhan transaksi Bank Indonesia.','Operasional SKNBI, Settlement BI-FAST, Rekonsiliasi Kas GL',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(3,1,'SUB-OPS-003','Trade Finance & Transaksi Ekspor-Impor','Penerbitan Letter of Credit (L/C), SKBDN, Bank Garansi, dan pembiayaan perdagangan internasional.','UCP 600, Standar L/C Ekspor-Impor, Verifikasi Dokumen Perdagangan',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(4,1,'SUB-OPS-004','Treasury Operations & Pasar Uang','Manajemen likuiditas bank, transaksi valuta asing (Valas), pasar uang antar bank (PUAB), dan surat berharga.','Dealing Room Support, Sertifikasi SKSP Bank Indonesia, Pengelolaan Likuiditas',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(5,2,'SUB-CRD-001','Analisis Kelayakan Kredit & Cashflow Modelling Komersial','Bedah laporan keuangan calon debitur, analisa rasio keuangan, proyeksi cashflow, dan verifikasi kelayakan usaha.','Analisa 5C, Proyeksi Cashflow Bisnis, Stress Testing Debitur Komersial',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(6,2,'SUB-CRD-002','Mitigasi Risiko Kredit & Pencegahan Non-Performing Loan (NPL)','Sistem peringatan dini (Early Warning System), monitoring agunan kredit, dan mitigasi potensi kredit macet.','Early Warning Signal, Penilaian Agunan Legal, Manajemen Kolektibilitas',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(7,2,'SUB-CRD-003','Restrukturisasi Kredit & Penyelamatan Aset (Remedial)','Strategi restrukturisasi pembiayaan debitur terdampak, negosiasi novasi/rescheduling, dan lelang eksekusi hak tanggungan.','Hukum Kepailitan & Eksekusi Jaminan, Manajemen Kredit Bermasalah',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(8,2,'SUB-CRD-004','Kredit Konsumer, KPR & Pembiayaan Pensiunan BWS','Produk unggulan BWS Kredit Pegawai Aktif & Pensiunan, analisis scoring pembiayaan ritel, dan KPR.','Credit Scoring Ritel, Penilaian Kemampuan Angsuran Pensiun BWS',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(9,3,'SUB-RSK-001','Sertifikasi Manajemen Risiko Perbankan (BSMR Level 1 - 4)','Persiapan ujian sertifikasi Badan Sertifikasi Manajemen Risiko (BSMR) sesuai regulasi mandatori OJK.','Risiko Pasar, Kredit, Operasional, Likuiditas, Strategik & Kepatuhan',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(10,3,'SUB-RSK-002','APU-PPT (Anti Pencucian Uang & Pencegahan Pendanaan Terorisme)','Customer Due Diligence (CDD), Enhanced Due Diligence (EDD), pemantauan Transaksi Keuangan Mencurigakan (LTKM).','Kepatuhan PPATK, Penerapan 5 Pilar APU-PPT, Red Flags Pencucian Uang',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(11,3,'SUB-RSK-003','Audit Internal Perbankan & Investigasi Anti-Fraud','Teknik audit operasional cabang, sampling audit kepatuhan, audit forensik internal, dan zero tolerance fraud.','Risk-Based Internal Audit, Investigasi Fraud, Fraud Risk Management',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(12,3,'SUB-RSK-004','Perlindungan Konsumen & Kerahasiaan Data (UU PDP Perbankan)','Kepatuhan Undang-Undang Perlindungan Data Pribadi (UU PDP) dan regulasi perlindungan konsumen sektor perbankan.','UU No. 27/2022 UU PDP, Tata Kelola Data Nasabah, Kepatuhan Konsumen OJK',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(13,4,'SUB-IT-001','Keamanan Siber Perbankan & Kepatuhan POJK 29/2022','Penerapan Information Security Framework, incident response siber, vulnerability assessment, dan kepatuhan POJK 29/2022.','CSIRT Bank, Kepatuhan Regulasi Siber POJK, Threat Hunting & Hardening',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(14,4,'SUB-IT-002','Core Banking Administration & Database Tuning','Manajemen basis data core banking (PostgreSQL/Oracle), batch process end-of-day (EOD), dan pemeliharaan transaksi 24/7.','PostgreSQL DBA, EOD Batch Handling, High-Availability Database Clustering',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(15,4,'SUB-IT-003','Digital Banking, API Open Banking & Cloud Architecture','Standar Nasional Open API Pembayaran (SNAP BI), arsitektur microservices, dan pengembangan aplikasi mobile banking BWS.','Standar SNAP BI, RESTful API Gateway Security, Containerization Docker',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(16,4,'SUB-IT-004','IT Disaster Recovery & Business Continuity Management (BCM)','Simulasi failover Data Center (DC) ke Disaster Recovery Center (DRC), RPO/RTO testing, dan audit IT kontinuitas bisnis.','ISO 22301 BCM, Uji Coba DRC Tahunan, RPO/RTO Mandatori Bank',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(17,5,'SUB-LDR-001','Officer Development Program (ODP) BWS Corporate Academy','Kurikulum intensif pembentukan perwira bank, fondasi perbankan menyeluruh, rotasi magang cabang, dan leadership sprint.','Banking Fundamentals, Presentation Skill, Strategic Problem Solving',1,1,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(18,5,'SUB-LDR-002','Branch Manager Leadership & P&L Business Mastery','Strategi pengelolaan cabang, pencapaian target profit & loss (P&L), CASA growth, dan kepemimpinan tim cabang.','P&L Branch Management, Commercial Negotiation, People Leadership',1,2,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(19,5,'SUB-LDR-003','Supervisory Coaching, Mentoring & People Development','Keterampilan coaching bagi Supervisor dan Section Head dalam meningkatkan performa dan retensi talenta staf.','GROW Coaching Model, Constructive Feedback, Talent Retention Strategy',1,3,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL),(20,5,'SUB-LDR-004','Budaya Kerja Perbankan BWS & Integrity Excellence','Internalisasi nilai-nilai budaya Bank Woori Saudara, kode etik bankir, whistleblowing system, dan profesionalisme kerja.','Code of Conduct, Whistleblowing System, Nilai Budaya Perbankan BWS',1,4,'2026-11-19 04:50:14','2026-11-19 04:50:14',NULL);
/*!40000 ALTER TABLE `sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tna_items`
--

DROP TABLE IF EXISTS `tna_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tna_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tna_proposal_id` bigint unsigned NOT NULL,
  `competency_gap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recommended_program` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_audience` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_participants_count` int NOT NULL DEFAULT '1',
  `estimated_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `urgency` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `job_family` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General Banking',
  `training_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'In-House Training',
  `execution_quarter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Q1',
  `cost_per_participant` decimal(15,2) NOT NULL DEFAULT '0.00',
  `impact_kpi` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `category_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_category_id` bigint unsigned DEFAULT NULL,
  `sub_category_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `realization_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `tna_items_tna_proposal_id_foreign` (`tna_proposal_id`),
  KEY `tna_items_category_id_foreign` (`category_id`),
  KEY `tna_items_sub_category_id_foreign` (`sub_category_id`),
  CONSTRAINT `tna_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tna_items_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tna_items_tna_proposal_id_foreign` FOREIGN KEY (`tna_proposal_id`) REFERENCES `tna_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tna_items`
--

LOCK TABLES `tna_items` WRITE;
/*!40000 ALTER TABLE `tna_items` DISABLE KEYS */;
INSERT INTO `tna_items` VALUES (1,1,'Security Operations Center (SOC) Incident Response','PRG-CYBER-01 - Cyber Security Defense','IT Infrastructure & Security Engineers',15,85000000.00,'Critical','2026-11-19 04:48:36','2026-11-19 04:50:22','General Banking','In-House Training','Q1',0.00,NULL,'2026-11-19 04:50:22',4,'Teknologi Informasi & Keamanan Siber',13,'Keamanan Siber Perbankan & Kepatuhan POJK 29/2022',60000000.00,0.00),(2,1,'Cloud Native Banking Migration Resilience','PostgreSQL & Enterprise Container Architecture','Core Banking Developers & DBA',10,100000000.00,'High','2026-11-19 04:48:36','2026-11-19 04:50:22','General Banking','In-House Training','Q1',0.00,NULL,'2026-11-19 04:50:22',NULL,NULL,NULL,NULL,NULL,0.00),(3,1,'Pengetahuan Mendalam Terkait Vulnerability Assessment & Standar POJK 29/2022 Keamanan Siber','Certified Banking Cyber Security Officer & Threat Modeling (BWS Academy)','Officer - IT Security & Core Banking Infrastructure',10,85000000.00,'Critical','2026-11-19 04:50:22','2026-11-19 04:50:22','IT & Digital Banking','In-House Training (Internal Academy)','Q1',8500000.00,'Zero High-Risk Vulnerability Finding pada Audit IT OJK & BI 2026.',NULL,NULL,NULL,NULL,NULL,NULL,0.00),(4,1,'Desain Arsitektur Microservices Perbankan, Event-Driven Architecture & Real-Time Settlement','Cloud Native Banking Architecture & API Modernization','Lead Developer & System Architect',10,60000000.00,'High','2026-11-19 04:50:22','2026-11-19 04:50:22','IT & Digital Banking','Public / External Course (LPPI / Certified)','Q2',6000000.00,'SLA Sistem Core Banking & Mobile Apps LIS mencapai 99.98% uptime.',NULL,NULL,NULL,NULL,NULL,NULL,0.00),(5,1,'Tata Kelola TI Berdasarkan Framework COBIT 2019 & Sertifikasi ISO/IEC 27001 Lead Implementer','COBIT 2019 Foundation & ISO 27001 Information Security Lead Implementer','Manager & Quality Assurance IT',4,40000000.00,'High','2026-11-19 04:50:22','2026-11-19 04:50:22','Risk & Compliance','Sertifikasi Wajib Regulasi (OJK/BI)','Q2',10000000.00,'Lolos resertifikasi ISO 27001:2022 tanpa temuan mayor.',NULL,NULL,NULL,NULL,NULL,NULL,0.00),(30,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 1','Pegawai BWS Terkait',25,500000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 1','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 1',NULL,'Training Program 1',500000000.00,485000000.00),(31,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 2','Pegawai BWS Terkait',25,200000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 1','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 1',NULL,'Training Program 2',200000000.00,190000000.00),(32,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 3','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 1','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 1',NULL,'Training Program 3',100000000.00,94000000.00),(33,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 4','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 2','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 2',NULL,'Training Program 4',100000000.00,87000000.00),(34,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 5','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 2','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 2',NULL,'Training Program 5',100000000.00,77000000.00),(35,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 6','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 2','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 2',NULL,'Training Program 6',100000000.00,99000000.00),(36,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 7','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 3','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 3',NULL,'Training Program 7',100000000.00,94000000.00),(37,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 8','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 3','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 3',NULL,'Training Program 8',100000000.00,91000000.00),(38,9,'Peningkatan gap kompetensi teknis perbankan','Training Program 9','Pegawai BWS Terkait',25,100000000.00,'High','2026-11-20 12:58:55','2026-11-20 12:58:55','Category 3','In-House Training','Q1',0.00,NULL,NULL,NULL,'Category 3',NULL,'Training Program 9',100000000.00,90000000.00);
/*!40000 ALTER TABLE `tna_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tna_proposals`
--

DROP TABLE IF EXISTS `tna_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tna_proposals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proposal_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `proposed_by` bigint unsigned NOT NULL,
  `priority` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Medium',
  `total_budget_requested` decimal(15,2) NOT NULL DEFAULT '0.00',
  `minimum_regulator_investment` decimal(15,2) DEFAULT NULL,
  `realization_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_budget_approved` decimal(15,2) DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `hcbp_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `strategic_objective` text COLLATE utf8mb4_unicode_ci,
  `target_quarter` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'All Quarters (Q1-Q4)',
  `current_step_order` int NOT NULL DEFAULT '1',
  `approval_status_badge` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu Review',
  `revision_count` int NOT NULL DEFAULT '0',
  `rejection_notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_size` int DEFAULT NULL,
  `fiscal_year_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tna_proposals_proposal_code_unique` (`proposal_code`),
  KEY `tna_proposals_proposed_by_foreign` (`proposed_by`),
  KEY `tna_proposals_approved_by_foreign` (`approved_by`),
  KEY `tna_proposals_fiscal_year_id_foreign` (`fiscal_year_id`),
  CONSTRAINT `tna_proposals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tna_proposals_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tna_proposals_proposed_by_foreign` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tna_proposals`
--

LOCK TABLES `tna_proposals` WRITE;
/*!40000 ALTER TABLE `tna_proposals` DISABLE KEYS */;
INSERT INTO `tna_proposals` VALUES (1,'TNA-2026-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2026,1,'Critical',1485000000.00,1100000000.00,425000000.00,1485000000.00,'APPROVED',NULL,'Disetujui untuk peningkatan kesiapan audit OJK IT Security semester 1.',3,'2026-11-14 04:48:36','2026-11-14 04:50:22','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2026','Q1 - Q4 2026',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2026.pdf','Rencana_Induk_TNA_Tahunan_BWS_2026.pdf',NULL,3),(5,'TNA-2020-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2020,1,'Critical',1000000000.00,800000000.00,980000000.00,1000000000.00,'APPROVED',NULL,NULL,NULL,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2020','Q1 - Q4 2020',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2020.pdf','Rencana_Induk_TNA_Tahunan_BWS_2020.pdf',NULL,5),(6,'TNA-2021-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2021,1,'Critical',1050000000.00,850000000.00,1048600000.00,1050000000.00,'APPROVED',NULL,NULL,NULL,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2021','Q1 - Q4 2021',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2021.pdf','Rencana_Induk_TNA_Tahunan_BWS_2021.pdf',NULL,6),(7,'TNA-2022-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2022,1,'Critical',1102500000.00,900000000.00,1069572000.00,1102500000.00,'APPROVED',NULL,NULL,NULL,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2022','Q1 - Q4 2022',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2022.pdf','Rencana_Induk_TNA_Tahunan_BWS_2022.pdf',NULL,7),(8,'TNA-2023-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2023,1,'Critical',1157625000.00,950000000.00,1155137760.00,1157625000.00,'APPROVED',NULL,NULL,NULL,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2023','Q1 - Q4 2023',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2023.pdf','Rencana_Induk_TNA_Tahunan_BWS_2023.pdf',NULL,8),(9,'TNA-2024-BWS','Human Capital & Corporate Academy (Bank-Wide Plan)',2024,1,'Critical',1400000000.00,1000000000.00,1307000000.00,1400000000.00,'APPROVED',NULL,NULL,NULL,NULL,'2026-11-20 12:58:55','2026-11-20 12:58:55','Peningkatan Indeks Kompetensi dan Kepatuhan Regulasi POJK & Standar BWS 2024','Q1 - Q4 2024',3,'Disetujui Final Direksi & OJK',0,NULL,NULL,'attachments/tna/Rencana_Induk_TNA_Tahunan_BWS_2024.pdf','Rencana_Induk_TNA_Tahunan_BWS_2024.pdf',NULL,1);
/*!40000 ALTER TABLE `tna_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_classes`
--

DROP TABLE IF EXISTS `training_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint unsigned NOT NULL,
  `class_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `max_quota` int NOT NULL DEFAULT '30',
  `enrolled_count` int NOT NULL DEFAULT '0',
  `location_or_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facilitator_id` bigint unsigned DEFAULT NULL,
  `dual_control_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `maker_id` bigint unsigned NOT NULL,
  `checker_id` bigint unsigned DEFAULT NULL,
  `checker_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `daily_schedule` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '08:30 - 16:30 WIB',
  `trainer_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passing_grade` int NOT NULL DEFAULT '75',
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_attendance_open` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_classes_class_code_unique` (`class_code`),
  KEY `training_classes_program_id_foreign` (`program_id`),
  KEY `training_classes_facilitator_id_foreign` (`facilitator_id`),
  KEY `training_classes_maker_id_foreign` (`maker_id`),
  KEY `training_classes_checker_id_foreign` (`checker_id`),
  CONSTRAINT `training_classes_checker_id_foreign` FOREIGN KEY (`checker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `training_classes_facilitator_id_foreign` FOREIGN KEY (`facilitator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `training_classes_maker_id_foreign` FOREIGN KEY (`maker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `training_classes_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_classes`
--

LOCK TABLES `training_classes` WRITE;
/*!40000 ALTER TABLE `training_classes` DISABLE KEYS */;
INSERT INTO `training_classes` VALUES (1,1,'CLS-2026-001','Batch 01 - Cyber Defense Core Banking','Hybrid (ELAPSE + In-Classroom)','2026-11-21','2026-11-24',25,18,'BWS Auditorium Lt. 7 Sudirman & ELAPSE Portal',6,'APPROVED',4,5,'Silabus dan vendor penguji kredensial telah terverifikasi sesuai standar OJK.','2026-11-18 04:48:36','2026-11-19 04:48:36','2026-11-19 04:48:36','08:30 - 16:30 WIB',NULL,75,NULL,NULL,1),(2,2,'CLS-2026-002','Batch 02 - Sertifikasi APU PPT Seluruh Frontliner','ELAPSE E-Learning','2026-11-26','2026-12-10',50,42,'https://learning.bankwoorisaudara.com/courses/apu-ppt-2026',6,'PENDING_CHECKER',4,NULL,NULL,NULL,'2026-11-19 04:48:36','2026-11-19 04:48:36','08:30 - 16:30 WIB',NULL,75,NULL,NULL,1),(3,5,'CLS-2026-RMS001-B1','Batch 1 - Regular Kantor Pusat','In-Classroom (Tatap Muka)','2026-02-10','2026-02-12',25,5,'BWS Corporate Learning Center Lt. 8, Ruang Diklat 801',NULL,'APPROVED',1,1,'Disetujui. Alokasi ruangan dan trainer terkonfirmasi.','2026-01-20 10:00:00','2026-11-19 04:51:07','2026-11-19 04:51:07','08:30 - 16:30 WIB','Dr. Bambang Soedjarwo, M.M. (Senior Banking Trainer)',75,'Pelaksanaan sertifikasi kompetensi manajemen risiko BSMR Level 1 & 2 tatap muka untuk jajaran Officer Kantor Pusat.',NULL,1),(4,5,'CLS-2026-RMS001-B2','Batch 2 - Wilayah Jawa Barat (KC Bandung)','Hybrid (ELAPSE + In-Classroom)','2026-03-16','2026-03-18',20,3,'KC Bandung Asia Afrika & MS Teams Live',NULL,'APPROVED',1,1,'Disetujui. Jadwal hybrid selaras dengan kalender operasional cabang.','2026-01-25 14:00:00','2026-11-19 04:51:07','2026-11-19 04:51:07','09:00 - 16:00 WIB','Ir. Hendra Gunawan, M.M. / Tim Pusdiklat BWS',75,'Batch 2 khusus pembinaan cabang-cabang wilayah Jawa Barat untuk akselerasi kepatuhan sertifikasi risiko.',NULL,1),(5,6,'CLS-2026-SEC002-B1','Batch 1 - IT Infrastructure & DevOps Lab','Hybrid (ELAPSE + In-Classroom)','2026-04-06','2026-04-08',30,3,'BWS Innovation Lab Lt. 5 & Virtual Cyber Range',NULL,'APPROVED',1,1,NULL,'2026-11-19 04:51:07','2026-11-19 04:51:07','2026-11-19 04:51:07','08:30 - 17:00 WIB','Trainer Certified Information Security Auditor (CISA)',75,'Hands-on workshop simulasi penanganan insiden siber ransomware dan audit ISO 27001.',NULL,1);
/*!40000 ALTER TABLE `training_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_programs`
--

DROP TABLE IF EXISTS `training_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `passing_grade` int NOT NULL DEFAULT '75',
  `duration_hours` int NOT NULL DEFAULT '8',
  `duration_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tna_proposal_id` bigint unsigned DEFAULT NULL,
  `tna_item_id` bigint unsigned DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int NOT NULL DEFAULT '2026',
  `execution_quarter` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Q1',
  `target_audience` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_participants_count` int NOT NULL DEFAULT '20',
  `participant_prerequisites` text COLLATE utf8mb4_unicode_ci,
  `method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hybrid',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facilitator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_initiator` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bi_category` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WORKFLOW',
  `approval_doc_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_doc_date` date DEFAULT NULL,
  `approval_doc_signer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_doc_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc1_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc1_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc1_remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc2_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc2_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_doc_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_doc_size` int DEFAULT NULL,
  `total_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_fee_facilitator` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_meals` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_hotel` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_meeting_package` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_transportation` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_business_trip` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_reward` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_material` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_internet` decimal(15,2) NOT NULL DEFAULT '0.00',
  `budget_miscellaneous` decimal(15,2) NOT NULL DEFAULT '0.00',
  `realization_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `current_step_order` int NOT NULL DEFAULT '1',
  `approval_status_badge` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_notes` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `approval_scenario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WITH_BUDGET',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_programs_program_code_unique` (`program_code`),
  KEY `training_programs_created_by_foreign` (`created_by`),
  KEY `training_programs_tna_proposal_id_foreign` (`tna_proposal_id`),
  KEY `training_programs_tna_item_id_foreign` (`tna_item_id`),
  KEY `training_programs_approved_by_foreign` (`approved_by`),
  CONSTRAINT `training_programs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `training_programs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `training_programs_tna_item_id_foreign` FOREIGN KEY (`tna_item_id`) REFERENCES `tna_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `training_programs_tna_proposal_id_foreign` FOREIGN KEY (`tna_proposal_id`) REFERENCES `tna_proposals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_programs`
--

LOCK TABLES `training_programs` WRITE;
/*!40000 ALTER TABLE `training_programs` DISABLE KEYS */;
INSERT INTO `training_programs` VALUES (1,'PRG-CYBER-01','Cyber Security Defense & Anti-Phishing in Core Banking',NULL,NULL,'Compliance & Risk','Pelatihan intensif mitigasi ancaman ransomware, social engineering, perlindungan kredensial OJK & pematuhan ISO 27001 pada ekosistem perbankan BWS.',80,16,NULL,1,4,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL,NULL,NULL,2026,'Q1',NULL,20,NULL,'Hybrid',NULL,NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'DRAFT',1,NULL,NULL,NULL,'WITH_BUDGET',NULL,NULL),(2,'PRG-APU-02','Penerapan Anti Pencucian Uang & Pencegahan Pendanaan Terorisme (APU PPT)',NULL,NULL,'Compliance & Risk','Kewajiban regulator Bank Indonesia & PPATK untuk identifikasi transaksi mencurigakan (STR), Customer Due Diligence (CDD), dan Enhanced Due Diligence (EDD).',75,12,NULL,1,4,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL,NULL,NULL,2026,'Q1',NULL,20,NULL,'Hybrid',NULL,NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'DRAFT',1,NULL,NULL,NULL,'WITH_BUDGET',NULL,NULL),(3,'PRG-CRE-03','Comprehensive Commercial & SME Credit Risk Underwriting',NULL,NULL,'Banking Operations','Analisis rasio 5C, kelayakan agunan, restrukturisasi kredit, dan simulasi stress-testing NPL pada segmen pembiayaan korporasi.',75,24,NULL,0,4,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL,NULL,NULL,2026,'Q1',NULL,20,NULL,'Hybrid',NULL,NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'DRAFT',1,NULL,NULL,NULL,'WITH_BUDGET',NULL,NULL),(4,'PRG-LEAD-04','Strategic Banking Leadership & High Impact Branch Execution',NULL,NULL,'Leadership','Transformasi manajerial bagi calon Pemimpin Cabang dan Team Leader dalam mengakselerasi CASA, fee-based income, dan coaching SDM perbankan.',70,32,NULL,0,4,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL,NULL,NULL,2026,'Q1',NULL,20,NULL,'Hybrid',NULL,NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'DRAFT',1,NULL,NULL,NULL,'WITH_BUDGET',NULL,NULL),(5,'PRG-2026-RMS-001','Sertifikasi Manajemen Risiko Perbankan Level 1 & 2 (BSMR)',NULL,NULL,'Risk Management','Program sertifikasi kompetensi manajemen risiko bank wajib regulasi OJK & Bank Indonesia untuk seluruh jajaran Officer & Manager.',75,32,NULL,1,1,'2026-11-19 04:50:46','2026-11-19 04:50:46',NULL,NULL,'Risk Management Division',2026,'Q1','Officer to Manager (Risk & Operations)',25,'Masa kerja minimal 1 tahun, lulus evaluasi kinerja minimum Baik.','OFFLINE','BWS Corporate Learning Center, Lantai 8 Kantor Pusat',NULL,NULL,NULL,NULL,NULL,'DOCUMENT','MEMO/HC-LD/2026/012/BWS','2026-01-15','Kepala Divisi Human Capital Bank Woori Saudara',NULL,NULL,NULL,NULL,NULL,NULL,'SK_Direksi_Program_Sertifikasi_Risiko_2026.pdf',1420500,66750000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'APPROVED',1,'Disahkan via Dokumen (MEMO/HC-LD/2026/012/BWS)',NULL,NULL,'WITH_BUDGET',1,'2026-01-16 09:30:00'),(6,'PRG-2026-SEC-002','Cyber Security Awareness & ISO 27001 Implementation',NULL,NULL,'Information Technology','Pelatihan intensif mitigasi ancaman siber perbankan digital, pengamanan data nasabah, dan kepatuhan standar ISO 27001.',75,24,NULL,0,1,'2026-11-19 04:50:46','2026-11-19 04:50:46',NULL,NULL,'Information Technology Division',2026,'Q2','IT Engineers, System Administrators, & Security Champions',30,'Memiliki basic networking dan pemahaman infrastruktur core banking.','HYBRID','BWS Innovation Hub & MS Teams Live',NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,28800000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'SUBMITTED',1,'Menunggu Reviewer 1 - HCBP',NULL,NULL,'WITH_BUDGET',NULL,NULL),(7,'PRG-2026-AML-003','Pelatihan Anti Pencucian Uang & Pencegahan Pendanaan Terorisme (APU-PPT)',NULL,NULL,'Compliance & Regulatory','Penyegaran ketentuan terbaru regulasi PPATK & Peraturan OJK (POJK) mengenai mitigasi transaksi keuangan mencurigakan.',75,16,NULL,1,1,'2026-11-19 04:50:46','2026-11-19 04:50:46',NULL,NULL,'Compliance Division',2026,'Q1','Frontliner, Customer Service, Teller, & Branch Operations',40,'Wajib bagi staf operasional dan frontliner cabang baru.','ONLINE','BWS LMS & Zoom Webinar Enterprise',NULL,NULL,NULL,NULL,NULL,'WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,19600000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'SUBMITTED',2,'Menunggu Reviewer 2 - HC Learning Head',NULL,NULL,'WITH_BUDGET',NULL,NULL),(8,'D24_001','Program A','2024-02-02','2024-02-10','Category 1',NULL,75,8,'8 Hari (40 Jam)',0,1,'2026-11-20 12:58:55','2026-11-20 12:58:55',9,NULL,NULL,2024,'Q1',NULL,20,NULL,'Hybrid','Ruang 1','PT A','In-House Training','Operasional & Layanan','Pelatihan','Operasional Perbankan','WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2000000.00,800000.00,300000.00,0.00,400000.00,100000.00,0.00,0.00,200000.00,100000.00,100000.00,1600000.00,'APPROVED',1,'Disetujui Final',NULL,NULL,'WITH_BUDGET',NULL,NULL),(9,'D24_002','Program B','2024-02-09','2024-02-09','Category 1',NULL,75,8,'1 Hari (8 Jam)',0,1,'2026-11-20 12:58:55','2026-11-20 12:58:55',9,NULL,NULL,2024,'Q1',NULL,20,NULL,'Hybrid','Ruang 2','PT B','In-House Training','Kredit & Pembiayaan','Workshop','Kredit & Pembiayaan Komersial','WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1000000.00,500000.00,200000.00,0.00,150000.00,50000.00,0.00,0.00,50000.00,25000.00,25000.00,740000.00,'APPROVED',1,'Disetujui Final',NULL,NULL,'WITH_BUDGET',NULL,NULL),(10,'D24_003','Program C','2024-03-01','2024-03-02','Category 2',NULL,75,8,'2 Hari (16 Jam)',0,1,'2026-11-20 12:58:55','2026-11-20 12:58:55',9,NULL,NULL,2024,'Q1',NULL,20,NULL,'Hybrid','Virtual','PT C','Virtual Webinar','Kepatuhan & AML','Sertifikasi','Manajemen Risiko, Kepatuhan & OJK','WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1000000.00,600000.00,0.00,0.00,0.00,0.00,0.00,0.00,200000.00,100000.00,100000.00,770000.00,'APPROVED',1,'Disetujui Final',NULL,NULL,'WITH_BUDGET',NULL,NULL),(11,'D26_001','Executive Cyber Security & Cloud Defense BWS 2026','2026-03-15','2026-03-18','Teknologi Informasi & Siber',NULL,75,8,'4 Hari (32 Jam)',0,1,'2026-11-20 12:58:55','2026-11-20 12:58:55',NULL,NULL,NULL,2026,'Q1',NULL,20,NULL,'Hybrid','Ruang Diklat 101 - BWS Tower','PT Cyber Security Global','In-House Training','Divisi Teknologi Informasi','Pelatihan & Uji Penetrasi','Teknologi Informasi & Siber (POJK 29/2022)','WORKFLOW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,85000000.00,45000000.00,8000000.00,12000000.00,6000000.00,4000000.00,0.00,0.00,5000000.00,2000000.00,3000000.00,78500000.00,'APPROVED',1,'Disetujui Final',NULL,NULL,'WITH_BUDGET',NULL,NULL);
/*!40000 ALTER TABLE `training_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telpon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` bigint unsigned DEFAULT NULL,
  `karyawan_id` bigint unsigned DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_username_unique` (`username`),
  UNIQUE KEY `user_email_unique` (`email`),
  KEY `user_level_id_foreign` (`level_id`),
  KEY `user_karyawan_id_foreign` (`karyawan_id`),
  CONSTRAINT `user_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`karyawan_id`) ON DELETE SET NULL,
  CONSTRAINT `user_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `user_level` (`level_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_access`
--

DROP TABLE IF EXISTS `user_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_access` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `level_id` bigint unsigned NOT NULL,
  `controller_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_access_level_id_controller_key_index` (`level_id`,`controller_key`),
  CONSTRAINT `user_access_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `user_level` (`level_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_access`
--

LOCK TABLES `user_access` WRITE;
/*!40000 ALTER TABLE `user_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_level`
--

DROP TABLE IF EXISTS `user_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_level` (
  `level_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`level_id`),
  UNIQUE KEY `user_level_role_unique` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_level`
--

LOCK TABLES `user_level` WRITE;
/*!40000 ALTER TABLE `user_level` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Employee / Participant',
  `level_id` bigint unsigned DEFAULT NULL,
  `karyawan_id` bigint unsigned DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nik_unique` (`nik`),
  KEY `users_level_id_foreign` (`level_id`),
  KEY `users_karyawan_id_foreign` (`karyawan_id`),
  CONSTRAINT `users_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`karyawan_id`) ON DELETE SET NULL,
  CONSTRAINT `users_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `user_level` (`level_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'BWS-ADM-001','Budi Pratama (Super Admin)','admin@bws.co.id','Super Admin',NULL,NULL,'IT Security & Governance','Kantor Pusat Sudirman','+62 811-1001-001',NULL,1,'2026-11-19 09:57:56','172.31.0.4',NULL,'$2y$12$1aPVmwLxoLstRxs9HI6l3eL4LQEWDMZuk7hyKDakTX90mRiTsDCQi',NULL,'2026-11-19 04:48:33','2026-11-19 09:57:56',NULL),(2,'BWS-DHD-042','Hendro Santoso (Dept Head IT)','depthead.it@bws.co.id','Dept Head',NULL,NULL,'Information Technology','Kantor Pusat Sudirman','+62 812-2002-042',NULL,1,'2026-11-17 18:48:34','10.20.11.145',NULL,'$2y$12$wjmKjaejeTZ/KmrL.Js.Fu8cHmn2Vp.uxUe.UcLyuHZhp4BCeMYhS',NULL,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(3,'BWS-HCP-019','Siti Rahmawati (HCBP Lead)','hcbp@bws.co.id','HCBP',NULL,NULL,'Human Capital Business Partner','Kantor Pusat Sudirman','+62 813-3003-019',NULL,1,'2026-11-17 20:48:34','10.20.6.101',NULL,'$2y$12$60X3ZIZcy8ywrTNL2hByNOqrIZJtZaLaYu2Q3dlM1F4Fom71l0ucS',NULL,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(4,'BWS-TRN-101','Aditya Nugraha (HC Training Maker)','maker.training@bws.co.id','HC Training (Maker)',NULL,NULL,'HC Learning & Development','Kantor Pusat Sudirman','+62 814-4004-101',NULL,1,'2026-11-17 19:48:34','10.20.30.62',NULL,'$2y$12$oUMW6kN4q6I7zaU8AdPQaeCpokUXsSrvKB7GjLytmUoLXCjjsqaI2',NULL,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(5,'BWS-TRN-002','Dr. Maya Wulandari (HC Learning Head)','checker.head@bws.co.id','HC Learning Head (Checker)',NULL,NULL,'HC Learning & Development','Kantor Pusat Sudirman','+62 815-5005-002',NULL,1,'2026-11-18 05:48:34','10.20.31.125',NULL,'$2y$12$BBOsLGWHRFNKCHDXSk7AO.hbz5O6t0tYTRCLxP3GBmXA8TDesKgoS',NULL,'2026-11-19 04:48:34','2026-11-19 04:48:34',NULL),(6,'BWS-FAC-014','Bambang Soedjarwo (Senior Banking Trainer)','facilitator@bws.co.id','Facilitator / Trainer',NULL,NULL,'BWS Corporate Academy','Learning Center Bandung','+62 816-6006-014',NULL,1,'2026-11-18 15:48:35','10.20.37.39',NULL,'$2y$12$/JsYQv9j9rp5wvfRaS0ODOEpZfjMGGpj3pXtKI8xsTGCw.DP/eut2',NULL,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL),(7,'BWS-EMP-9821','Yazid Bustomi (Credit & Operations Officer)','participant@bws.co.id','Employee / Participant',NULL,NULL,'Branch Banking Operations','KCU Bandung Merdeka','+62 817-7007-821',NULL,1,'2026-11-18 11:48:35','10.20.50.153',NULL,'$2y$12$lNDJGO6l9Wh0eTrtMzAUu.d9pQTRNyWKWb2Xk4rkTZ0fY4zJDOsgu',NULL,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL),(8,'BWS-EXEC-003','Kwon Jin-Woo (Director of Human Capital & Ops)','bod.executive@bws.co.id','Executive',NULL,NULL,'Board of Directors','Kantor Pusat Sudirman','+62 818-8008-003',NULL,1,'2026-11-18 21:48:35','10.20.7.6',NULL,'$2y$12$.mAIWaOIni1SeuEjn5qCfOALTqHevYnDOffE/ThhMEFHWlUA36WXG',NULL,'2026-11-19 04:48:35','2026-11-19 04:48:35',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'bws_lis_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-20 14:01:21
