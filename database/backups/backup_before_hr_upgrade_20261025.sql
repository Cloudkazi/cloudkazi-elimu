-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: elimu_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_terms`
--

DROP TABLE IF EXISTS `academic_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_terms_school_id_foreign` (`school_id`),
  KEY `academic_terms_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `academic_terms_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_terms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_terms`
--

LOCK TABLES `academic_terms` WRITE;
/*!40000 ALTER TABLE `academic_terms` DISABLE KEYS */;
INSERT INTO `academic_terms` VALUES (7,12,3,'Term 1','2026-01-02','2026-12-15',0,'2026-10-25 04:33:20','2026-10-25 04:33:40'),(8,12,3,'Term 2','2026-01-02','2026-12-15',1,'2026-10-25 04:33:20','2026-10-25 04:33:40'),(9,12,3,'Term 3','2026-01-02','2026-12-15',0,'2026-10-25 04:33:20','2026-10-25 04:33:40');
/*!40000 ALTER TABLE `academic_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_years_school_id_foreign` (`school_id`),
  CONSTRAINT `academic_years_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_years`
--

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES (3,12,'2026','2026-01-02','2026-12-15',1,'2026-10-25 04:33:20','2026-10-25 04:33:20');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('All','Teachers','Students','Parents') NOT NULL DEFAULT 'All',
  `published_at` date NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_school_id_foreign` (`school_id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `announcements_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `asset_tag` varchar(255) NOT NULL,
  `category` enum('Furniture','IT & Computers','Lab Equipment','Sports','Musical','Vehicles') NOT NULL DEFAULT 'Furniture',
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `condition` enum('New','Good','Fair','Needs Repair','Condemned') NOT NULL DEFAULT 'Good',
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_asset_tag_unique` (`asset_tag`),
  KEY `assets_school_id_foreign` (`school_id`),
  CONSTRAINT `assets_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_school_id_created_at_index` (`school_id`,`created_at`),
  KEY `audit_logs_action_module_index` (`action`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,36,11,'school.created','school','Provisioned school \'Kitui School\' with package \'Standard (Legacy)\'',NULL,'{\"school_name\":\"Kitui School\",\"package\":\"Standard (Legacy)\",\"admin\":\"kitui@gmail.com\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-25 03:43:05',NULL),(2,36,12,'school.created','school','Provisioned school \'Kitui School\' with package \'Standard (Legacy)\'',NULL,'{\"school_name\":\"Kitui School\",\"package\":\"Standard (Legacy)\",\"admin\":\"kitui@gmail.com\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-25 04:19:31',NULL);
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_categories`
--

DROP TABLE IF EXISTS `book_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_categories_school_id_foreign` (`school_id`),
  CONSTRAINT `book_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_categories`
--

LOCK TABLES `book_categories` WRITE;
/*!40000 ALTER TABLE `book_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_issues`
--

DROP TABLE IF EXISTS `book_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('issued','returned','overdue','lost') NOT NULL DEFAULT 'issued',
  `fine_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `book_issues_school_id_foreign` (`school_id`),
  KEY `book_issues_book_id_foreign` (`book_id`),
  KEY `book_issues_student_id_foreign` (`student_id`),
  KEY `book_issues_user_id_foreign` (`user_id`),
  CONSTRAINT `book_issues_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_issues_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_issues_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_issues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_issues`
--

LOCK TABLES `book_issues` WRITE;
/*!40000 ALTER TABLE `book_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `shelf_location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `books_school_id_foreign` (`school_id`),
  KEY `books_category_id_foreign` (`category_id`),
  CONSTRAINT `books_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `books_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('cloudkazi-elimu-cache-5b384ce32d8cdef02bc3a139d4cac0a22bb029e8','i:1;',1792924411),('cloudkazi-elimu-cache-5b384ce32d8cdef02bc3a139d4cac0a22bb029e8:timer','i:1792924411;',1792924411),('cloudkazi-elimu-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1792924120),('cloudkazi-elimu-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1792924120;',1792924120),('cloudkazi-elimu-cache-af3e133428b9e25c55bc59fe534248e6a0c0f17b','i:2;',1792922948),('cloudkazi-elimu-cache-af3e133428b9e25c55bc59fe534248e6a0c0f17b:timer','i:1792922948;',1792922948),('cloudkazi-elimu-cache-ca3512f4dfa95a03169c5a670a4c91a19b3077b4','i:3;',1792923975),('cloudkazi-elimu-cache-ca3512f4dfa95a03169c5a670a4c91a19b3077b4:timer','i:1792923975;',1792923975),('cloudkazi-elimu-cache-fc074d501302eb2b93e2554793fcaf50b3bf7291','i:1;',1792912833),('cloudkazi-elimu-cache-fc074d501302eb2b93e2554793fcaf50b3bf7291:timer','i:1792912833;',1792912833);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
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
-- Table structure for table `cbc_assessments`
--

DROP TABLE IF EXISTS `cbc_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbc_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `examination_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_offering_id` bigint(20) unsigned DEFAULT NULL,
  `strand` varchar(255) NOT NULL,
  `sub_strand` varchar(255) DEFAULT NULL,
  `achievement_level` enum('EE','ME','AE','BE') NOT NULL DEFAULT 'ME',
  `score_points` int(11) NOT NULL DEFAULT 3,
  `rubric_feedback` text DEFAULT NULL,
  `assessed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cbc_assessments_unique` (`school_id`,`examination_id`,`student_id`,`subject_id`),
  KEY `cbc_assessments_examination_id_foreign` (`examination_id`),
  KEY `cbc_assessments_student_id_foreign` (`student_id`),
  KEY `cbc_assessments_subject_id_foreign` (`subject_id`),
  KEY `cbc_assessments_assessed_by_foreign` (`assessed_by`),
  KEY `cbc_assessments_subject_offering_id_index` (`subject_offering_id`),
  CONSTRAINT `cbc_assessments_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cbc_assessments_examination_id_foreign` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cbc_assessments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cbc_assessments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cbc_assessments_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cbc_assessments`
--

LOCK TABLES `cbc_assessments` WRITE;
/*!40000 ALTER TABLE `cbc_assessments` DISABLE KEYS */;
INSERT INTO `cbc_assessments` VALUES (37,12,3,15,37,1,'Core Competency','Formative Evaluation','ME',3,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(38,12,3,15,39,2,'Core Competency','Formative Evaluation','EE',4,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(39,12,3,15,34,3,'Core Competency','Formative Evaluation','EE',4,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(40,12,4,15,39,2,'Core Competency','Formative Evaluation','EE',4,'Excellent',38,'2026-10-25 04:42:48','2026-10-25 04:42:48'),(41,12,4,15,34,3,'Core Competency','Formative Evaluation','EE',4,'Excellent',38,'2026-10-25 04:42:48','2026-10-25 04:42:48');
/*!40000 ALTER TABLE `cbc_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chatbot_logs`
--

DROP TABLE IF EXISTS `chatbot_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chatbot_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `matched_article_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'knowledge_base',
  `was_helpful` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chatbot_logs_user_id_foreign` (`user_id`),
  KEY `chatbot_logs_matched_article_id_foreign` (`matched_article_id`),
  KEY `chatbot_logs_session_id_index` (`session_id`),
  CONSTRAINT `chatbot_logs_matched_article_id_foreign` FOREIGN KEY (`matched_article_id`) REFERENCES `help_articles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chatbot_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_logs`
--

LOCK TABLES `chatbot_logs` WRITE;
/*!40000 ALTER TABLE `chatbot_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `chatbot_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classrooms`
--

DROP TABLE IF EXISTS `classrooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classrooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('regular','science_lab','computer_lab','workshop','music_room') NOT NULL DEFAULT 'regular',
  `capacity` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classrooms_school_id_foreign` (`school_id`),
  CONSTRAINT `classrooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classrooms`
--

LOCK TABLES `classrooms` WRITE;
/*!40000 ALTER TABLE `classrooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `classrooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `hod_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_school_id_foreign` (`school_id`),
  KEY `departments_hod_id_foreign` (`hod_id`),
  CONSTRAINT `departments_hod_id_foreign` FOREIGN KEY (`hod_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discipline_incidents`
--

DROP TABLE IF EXISTS `discipline_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discipline_incidents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `incident_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `action_taken` varchar(255) DEFAULT NULL,
  `parent_notified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('open','in_review','resolved') NOT NULL DEFAULT 'resolved',
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discipline_incidents_school_id_foreign` (`school_id`),
  KEY `discipline_incidents_student_id_foreign` (`student_id`),
  KEY `discipline_incidents_reported_by_foreign` (`reported_by`),
  CONSTRAINT `discipline_incidents_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discipline_incidents_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discipline_incidents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discipline_incidents`
--

LOCK TABLES `discipline_incidents` WRITE;
/*!40000 ALTER TABLE `discipline_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `discipline_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dormitories`
--

DROP TABLE IF EXISTS `dormitories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dormitories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `gender` enum('Male','Female') NOT NULL DEFAULT 'Male',
  `capacity` int(11) NOT NULL DEFAULT 100,
  `warden_name` varchar(255) DEFAULT NULL,
  `warden_phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dormitories_school_id_foreign` (`school_id`),
  CONSTRAINT `dormitories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dormitories`
--

LOCK TABLES `dormitories` WRITE;
/*!40000 ALTER TABLE `dormitories` DISABLE KEYS */;
/*!40000 ALTER TABLE `dormitories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `employee_no` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `kra_pin` varchar(255) DEFAULT NULL,
  `nssf_no` varchar(255) DEFAULT NULL,
  `nhif_no` varchar(255) DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','on_leave','terminated') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_no_unique` (`employee_no`),
  KEY `employees_school_id_foreign` (`school_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  CONSTRAINT `employees_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (3,12,NULL,'EMP-001','Kinuthia','Wakwitu',NULL,NULL,NULL,NULL,'Chef','Jikoni','2026-10-25',15000.00,'active','2026-10-25 07:29:02','2026-10-25 07:29:02');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_marks_844`
--

DROP TABLE IF EXISTS `exam_marks_844`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_marks_844` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `examination_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_offering_id` bigint(20) unsigned DEFAULT NULL,
  `score` decimal(5,2) NOT NULL,
  `out_of` decimal(5,2) NOT NULL DEFAULT 100.00,
  `grade` varchar(3) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_marks_unique` (`school_id`,`examination_id`,`student_id`,`subject_id`),
  KEY `exam_marks_844_examination_id_foreign` (`examination_id`),
  KEY `exam_marks_844_student_id_foreign` (`student_id`),
  KEY `exam_marks_844_subject_id_foreign` (`subject_id`),
  KEY `exam_marks_844_recorded_by_foreign` (`recorded_by`),
  KEY `exam_marks_844_subject_offering_id_index` (`subject_offering_id`),
  CONSTRAINT `exam_marks_844_examination_id_foreign` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_marks_844_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_marks_844_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_marks_844_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_marks_844_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_marks_844`
--

LOCK TABLES `exam_marks_844` WRITE;
/*!40000 ALTER TABLE `exam_marks_844` DISABLE KEYS */;
INSERT INTO `exam_marks_844` VALUES (15,12,3,15,37,1,76.00,100.00,'A-',11,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(16,12,3,15,39,2,89.00,100.00,'A',12,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(17,12,3,15,34,3,98.00,100.00,'A',12,'Good work.',38,'2026-10-25 04:39:11','2026-10-25 04:39:11'),(18,12,4,15,39,2,98.00,100.00,'A',12,'Excellent',38,'2026-10-25 04:42:48','2026-10-25 04:43:04'),(19,12,4,15,34,3,89.00,100.00,'A',12,'Excellent',38,'2026-10-25 04:42:48','2026-10-25 04:43:04');
/*!40000 ALTER TABLE `exam_marks_844` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `examinations`
--

DROP TABLE IF EXISTS `examinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `examinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `curriculum_type` enum('CBC','8-4-4','Both') NOT NULL DEFAULT 'Both',
  `exam_type` enum('opener','midterm','endterm','mock','cbc_assessment') NOT NULL DEFAULT 'endterm',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `examinations_school_id_foreign` (`school_id`),
  KEY `examinations_academic_year_id_foreign` (`academic_year_id`),
  KEY `examinations_term_id_foreign` (`term_id`),
  CONSTRAINT `examinations_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `examinations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `examinations_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examinations`
--

LOCK TABLES `examinations` WRITE;
/*!40000 ALTER TABLE `examinations` DISABLE KEYS */;
INSERT INTO `examinations` VALUES (3,12,3,8,'KNEC SBA','CBC','cbc_assessment','2026-10-25','2026-10-30',1,'2026-10-25 04:36:11','2026-10-25 04:39:11'),(4,12,3,7,'KITU SAWA','CBC','opener','2026-10-26','2026-10-30',1,'2026-10-25 04:42:12','2026-10-25 04:42:48');
/*!40000 ALTER TABLE `examinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
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
-- Table structure for table `fee_categories`
--

DROP TABLE IF EXISTS `fee_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_categories_school_id_foreign` (`school_id`),
  CONSTRAINT `fee_categories_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_categories`
--

LOCK TABLES `fee_categories` WRITE;
/*!40000 ALTER TABLE `fee_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_payments`
--

DROP TABLE IF EXISTS `fee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `receipt_number` varchar(255) NOT NULL,
  `student_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('mpesa','mpesa_stk','mpesa_paybill','mpesa_till','bank','bank_transfer','bank_deposit','cash','cheque') NOT NULL DEFAULT 'mpesa',
  `reference_code` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_source` enum('bursar','parent_portal','mpesa_callback','admin') NOT NULL DEFAULT 'bursar',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `balance_before` decimal(12,2) DEFAULT NULL,
  `balance_after` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_school_id_receipt_number_unique` (`school_id`,`receipt_number`),
  KEY `payments_student_invoice_id_foreign` (`student_invoice_id`),
  KEY `payments_student_id_foreign` (`student_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `fee_payments_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `fee_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `school_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_student_invoice_id_foreign` FOREIGN KEY (`student_invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_payments`
--

LOCK TABLES `fee_payments` WRITE;
/*!40000 ALTER TABLE `fee_payments` DISABLE KEYS */;
INSERT INTO `fee_payments` VALUES (13,12,'REC-YWMLPICQ',NULL,15,1200.00,'cash','QWRETYUIO','2026-10-25',38,'Received','bursar','2026-10-25 05:16:51','2026-10-25 05:16:51',NULL,NULL,NULL);
/*!40000 ALTER TABLE `fee_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `fee_category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_structures_school_id_foreign` (`school_id`),
  KEY `fee_structures_academic_year_id_foreign` (`academic_year_id`),
  KEY `fee_structures_term_id_foreign` (`term_id`),
  KEY `fee_structures_school_class_id_foreign` (`school_class_id`),
  KEY `fee_structures_fee_category_id_foreign` (`fee_category_id`),
  CONSTRAINT `fee_structures_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_fee_category_id_foreign` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structures`
--

LOCK TABLES `fee_structures` WRITE;
/*!40000 ALTER TABLE `fee_structures` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guardians`
--

DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `relationship` varchar(255) NOT NULL DEFAULT 'Parent',
  `occupation` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_school_id_foreign` (`school_id`),
  KEY `guardians_user_id_foreign` (`user_id`),
  CONSTRAINT `guardians_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guardians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guardians`
--

LOCK TABLES `guardians` WRITE;
/*!40000 ALTER TABLE `guardians` DISABLE KEYS */;
INSERT INTO `guardians` VALUES (5,12,40,'Fatuma','Hassan','0711111111','fatuma.hassan@example.com','Parent',NULL,NULL,NULL,'2026-10-25 04:26:05','2026-10-25 04:26:05'),(6,12,42,'David','Kiptoo','0722222222','david.kiptoo@example.com','Parent',NULL,NULL,NULL,'2026-10-25 04:26:06','2026-10-25 04:26:06'),(7,12,44,'Grace','Njeri','0733333333','grace.njeri@example.com','Parent',NULL,NULL,NULL,'2026-10-25 04:26:07','2026-10-25 04:26:07'),(8,12,46,'Mary','Otieno','0744444444','mary.otieno@example.com','Parent',NULL,NULL,NULL,'2026-10-25 04:26:09','2026-10-25 04:26:09'),(9,12,48,'Peter','Mutua','0755555555','peter.mutua@example.com','Parent',NULL,NULL,NULL,'2026-10-25 04:26:10','2026-10-25 04:26:10');
/*!40000 ALTER TABLE `guardians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `help_articles`
--

DROP TABLE IF EXISTS `help_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(64) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `keywords` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `view_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `help_articles_category_index` (`category`),
  KEY `help_articles_is_active_index` (`is_active`),
  FULLTEXT KEY `help_articles_question_answer_keywords_fulltext` (`question`,`answer`,`keywords`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `help_articles`
--

LOCK TABLES `help_articles` WRITE;
/*!40000 ALTER TABLE `help_articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `help_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_rooms`
--

DROP TABLE IF EXISTS `hostel_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `dormitory_id` bigint(20) unsigned NOT NULL,
  `room_number` varchar(255) NOT NULL,
  `total_beds` int(11) NOT NULL DEFAULT 4,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hostel_rooms_school_id_foreign` (`school_id`),
  KEY `hostel_rooms_dormitory_id_foreign` (`dormitory_id`),
  CONSTRAINT `hostel_rooms_dormitory_id_foreign` FOREIGN KEY (`dormitory_id`) REFERENCES `dormitories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hostel_rooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_rooms`
--

LOCK TABLES `hostel_rooms` WRITE;
/*!40000 ALTER TABLE `hostel_rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
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
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
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
-- Table structure for table `learning_attempts`
--

DROP TABLE IF EXISTS `learning_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `learning_question_id` bigint(20) unsigned NOT NULL,
  `answer` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_attempts_school_id_foreign` (`school_id`),
  KEY `learning_attempts_learning_question_id_foreign` (`learning_question_id`),
  KEY `learning_attempts_student_question_answered_index` (`student_id`,`learning_question_id`,`answered_at`),
  CONSTRAINT `learning_attempts_learning_question_id_foreign` FOREIGN KEY (`learning_question_id`) REFERENCES `learning_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_attempts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_attempts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_attempts`
--

LOCK TABLES `learning_attempts` WRITE;
/*!40000 ALTER TABLE `learning_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `learning_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_chapter_progress`
--

DROP TABLE IF EXISTS `learning_chapter_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_chapter_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `learning_chapter_id` bigint(20) unsigned NOT NULL,
  `attempts_count` int(10) unsigned NOT NULL DEFAULT 0,
  `correct_count` int(10) unsigned NOT NULL DEFAULT 0,
  `completed_questions_count` int(10) unsigned NOT NULL DEFAULT 0,
  `mastery_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `completion_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `last_attempted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_chapter_progress_student_unique` (`student_id`,`learning_chapter_id`),
  KEY `learning_chapter_progress_school_id_foreign` (`school_id`),
  KEY `learning_chapter_progress_learning_chapter_id_foreign` (`learning_chapter_id`),
  CONSTRAINT `learning_chapter_progress_learning_chapter_id_foreign` FOREIGN KEY (`learning_chapter_id`) REFERENCES `learning_chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_chapter_progress_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_chapter_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_chapter_progress`
--

LOCK TABLES `learning_chapter_progress` WRITE;
/*!40000 ALTER TABLE `learning_chapter_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `learning_chapter_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_chapters`
--

DROP TABLE IF EXISTS `learning_chapters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_chapters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sequence` int(10) unsigned NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learning_chapters_sequence_unique` (`school_id`,`school_class_id`,`subject_id`,`sequence`),
  KEY `learning_chapters_school_class_id_foreign` (`school_class_id`),
  KEY `learning_chapters_subject_id_foreign` (`subject_id`),
  CONSTRAINT `learning_chapters_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_chapters_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_chapters_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_chapters`
--

LOCK TABLES `learning_chapters` WRITE;
/*!40000 ALTER TABLE `learning_chapters` DISABLE KEYS */;
INSERT INTO `learning_chapters` VALUES (1,12,19,33,'Introduction to Computer','Foundation practice questions and core concepts',1,1,'2026-10-25 07:22:19','2026-10-25 07:22:19');
/*!40000 ALTER TABLE `learning_chapters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_materials`
--

DROP TABLE IF EXISTS `learning_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `type` enum('notes','assignment','past_paper','revision_material') NOT NULL DEFAULT 'notes',
  `status` enum('published','draft','pending','unpublished','archived') NOT NULL DEFAULT 'published',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_materials_school_id_foreign` (`school_id`),
  KEY `learning_materials_school_class_id_foreign` (`school_class_id`),
  KEY `learning_materials_subject_id_foreign` (`subject_id`),
  KEY `learning_materials_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `learning_materials_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_materials_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_materials_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_materials_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_materials`
--

LOCK TABLES `learning_materials` WRITE;
/*!40000 ALTER TABLE `learning_materials` DISABLE KEYS */;
INSERT INTO `learning_materials` VALUES (5,12,19,37,NULL,'Geography','testing',NULL,'revision_material','published','2026-10-27','2026-10-25 07:09:18','2026-10-25 07:09:18');
/*!40000 ALTER TABLE `learning_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_questions`
--

DROP TABLE IF EXISTS `learning_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `learning_chapter_id` bigint(20) unsigned NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `prompt` text NOT NULL,
  `question_type` enum('multiple_choice','short_answer') NOT NULL DEFAULT 'multiple_choice',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text NOT NULL,
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `points` decimal(6,2) NOT NULL DEFAULT 1.00,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_questions_school_id_foreign` (`school_id`),
  KEY `learning_questions_learning_chapter_id_foreign` (`learning_chapter_id`),
  CONSTRAINT `learning_questions_learning_chapter_id_foreign` FOREIGN KEY (`learning_chapter_id`) REFERENCES `learning_chapters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_questions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_questions`
--

LOCK TABLES `learning_questions` WRITE;
/*!40000 ALTER TABLE `learning_questions` DISABLE KEYS */;
INSERT INTO `learning_questions` VALUES (1,12,1,'Basics','Section 1','What is the fundamental unit of study in Computer?','multiple_choice','[\"Option A\",\"Option B\",\"Option C\",\"Option D\"]','Option A','Option A is the correct answer according to the curriculum standards.','easy',1.00,1,'2026-10-25 07:22:19','2026-10-25 07:22:19');
/*!40000 ALTER TABLE `learning_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_documents`
--

DROP TABLE IF EXISTS `legal_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legal_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `version` varchar(32) NOT NULL DEFAULT '1.0',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_documents_type_index` (`type`),
  KEY `legal_documents_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_documents`
--

LOCK TABLES `legal_documents` WRITE;
/*!40000 ALTER TABLE `legal_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_17_000001_create_academics_tables',1),(5,'2026_09_17_000002_create_students_and_guardians_tables',1),(6,'2026_09_17_000003_create_timetable_tables',1),(7,'2026_09_17_000004_create_attendance_tables',1),(8,'2026_09_17_000005_create_finance_and_mpesa_tables',1),(9,'2026_09_17_000006_create_examinations_and_grading_tables',1),(10,'2026_09_17_000007_create_operations_and_resources_tables',1),(11,'2026_09_17_000008_add_parent_user_id_to_students_table',1),(12,'2026_09_18_000001_add_is_demo_to_users_table',1),(13,'2026_09_19_000001_add_timetable_enhancement_tables',1),(14,'2026_09_19_000001_alter_timetable_allocations_make_stream_id_nullable',1),(15,'2026_09_21_000001_create_or_rename_fee_payments_table',1),(16,'2026_09_24_000001_add_status_to_learning_materials_table',1),(17,'2026_09_24_000002_add_scheduling_settings_to_timetable_generation_settings_table',1),(18,'2026_09_24_065610_add_missing_columns_to_timetable_slots_table',1),(19,'2026_09_25_000001_enhance_payment_gateway_tables',1),(20,'2026_09_25_100001_create_packages_table',1),(21,'2026_09_25_100002_extend_subscriptions_table',1),(22,'2026_09_25_100003_create_saas_platform_tables',1),(23,'2026_09_25_100004_expand_schools_status_enum',1),(24,'2026_09_25_100005_expand_subscriptions_status_enum',1),(25,'2026_09_26_000001_create_legal_documents_table',1),(26,'2026_09_26_000002_create_help_articles_table',1),(27,'2026_09_26_000003_create_chatbot_logs_table',1),(28,'2026_09_29_110018_add_is_staff_to_roles_table',1),(29,'2026_10_02_000001_sync_staff_roles_data',1),(30,'2026_10_03_000001_enhance_teacher_allocations_and_cbc_integrity',1),(31,'2026_10_03_000002_create_subject_offerings_and_pathway_hierarchy',1),(32,'2026_10_03_141823_add_is_active_to_subjects_and_classes',1),(33,'2026_10_25_000001_create_student_learning_practice_tables',2),(34,'2026_10_25_000002_add_fee_payment_balance_snapshots',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mpesa_transactions`
--

DROP TABLE IF EXISTS `mpesa_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mpesa_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `mpesa_type` enum('stk','paybill','till') NOT NULL DEFAULT 'stk',
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `merchant_request_id` varchar(255) DEFAULT NULL,
  `checkout_request_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mpesa_receipt_number` varchar(255) DEFAULT NULL,
  `result_code` varchar(255) DEFAULT NULL,
  `result_desc` text DEFAULT NULL,
  `status` enum('initiated','completed','failed') NOT NULL DEFAULT 'initiated',
  `transaction_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mpesa_transactions_school_id_foreign` (`school_id`),
  KEY `mpesa_transactions_student_id_foreign` (`student_id`),
  KEY `mpesa_transactions_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `mpesa_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_transactions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mpesa_transactions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mpesa_transactions`
--

LOCK TABLES `mpesa_transactions` WRITE;
/*!40000 ALTER TABLE `mpesa_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `mpesa_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_features`
--

DROP TABLE IF EXISTS `package_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint(20) unsigned NOT NULL,
  `feature_key` varchar(60) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `package_features_package_id_feature_key_unique` (`package_id`,`feature_key`),
  CONSTRAINT `package_features_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_features`
--

LOCK TABLES `package_features` WRITE;
/*!40000 ALTER TABLE `package_features` DISABLE KEYS */;
INSERT INTO `package_features` VALUES (1,1,'student_management',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(2,1,'teacher_management',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(3,1,'parent_portal',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(4,1,'student_portal',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(5,1,'finance',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(6,1,'mpesa',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(7,1,'examinations',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(8,1,'attendance',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(9,1,'timetable',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(10,1,'library',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(11,1,'transport',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(12,1,'hostel',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(13,1,'sms',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(14,1,'reports',1,'2026-10-25 03:05:52','2026-10-25 03:05:52'),(15,1,'communication',1,'2026-10-25 03:05:52','2026-10-25 03:05:52');
/*!40000 ALTER TABLE `package_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `activation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `annual_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_students` int(10) unsigned DEFAULT NULL COMMENT 'null = unlimited',
  `max_teachers` int(10) unsigned DEFAULT NULL,
  `max_parents` int(10) unsigned DEFAULT NULL,
  `max_admins` int(10) unsigned DEFAULT NULL,
  `trial_days` int(10) unsigned NOT NULL DEFAULT 30,
  `grace_period_days` int(10) unsigned NOT NULL DEFAULT 14,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'Standard (Legacy)','STD-LEGACY','Auto-created legacy package for existing subscriptions.',0.00,30000.00,600,50,600,5,30,14,0,1,0,'2026-10-25 03:05:52','2026-10-25 03:05:52');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
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
-- Table structure for table `payment_reminders`
--

DROP TABLE IF EXISTS `payment_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `school_class_id` bigint(20) unsigned DEFAULT NULL,
  `target_type` enum('all','class','student','arrears') NOT NULL DEFAULT 'arrears',
  `channel` enum('sms','email','system') NOT NULL DEFAULT 'sms',
  `message` text NOT NULL,
  `recipients_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('sent','failed','partial') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_reminders_school_id_foreign` (`school_id`),
  KEY `payment_reminders_sent_by_foreign` (`sent_by`),
  KEY `payment_reminders_student_id_foreign` (`student_id`),
  KEY `payment_reminders_school_class_id_foreign` (`school_class_id`),
  CONSTRAINT `payment_reminders_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_reminders_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_reminders_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_reminders_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_reminders`
--

LOCK TABLES `payment_reminders` WRITE;
/*!40000 ALTER TABLE `payment_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `status` enum('draft','processed','paid') NOT NULL DEFAULT 'paid',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_records_school_id_foreign` (`school_id`),
  KEY `payroll_records_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_records_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'string' COMMENT 'string|integer|boolean|json',
  `group` varchar(60) NOT NULL DEFAULT 'general',
  `label` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_announcement_reads`
--

DROP TABLE IF EXISTS `portal_announcement_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portal_announcement_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `announcement_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `portal_announcement_reads_user_announcement_unique` (`user_id`,`announcement_id`),
  KEY `portal_announcement_reads_announcement_id_foreign` (`announcement_id`),
  CONSTRAINT `portal_announcement_reads_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `portal_announcement_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_announcement_reads`
--

LOCK TABLES `portal_announcement_reads` WRITE;
/*!40000 ALTER TABLE `portal_announcement_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_announcement_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True = appears in Add Staff dropdown. False = system/student/parent role.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Principal / Head Teacher','principal','School Principal & Head of Institution',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(2,'Deputy Principal','deputy-principal','Deputy Principal & Head of Academics/Discipline',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(3,'Teacher','teacher','Subject Teacher',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(4,'Bursar / Accountant','bursar','Finance Officer, Bursar & Accountant',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(5,'Librarian','librarian','School Librarian',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(6,'Examinations Officer','examinations-officer','Examinations & Assessment Coordinator',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(7,'Secretary / Front Office','secretary','School Secretary & Front Office Administrator',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(8,'ICT Administrator','ict-admin','ICT Support & Systems Administrator',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(9,'Discipline / Welfare Officer','discipline-officer','Student Discipline & Welfare Officer',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(10,'Hostel Warden','hostel-warden','Boarding / Hostel Master/Mistress',1,'2026-10-25 03:06:06','2026-10-25 03:06:06'),(23,'Super Admin','super-admin','CloudKazi HQ System Administrator',0,'2026-10-25 03:15:16','2026-10-25 03:15:16'),(24,'School Admin','school-admin','School IT Administrator & System Manager',0,'2026-10-25 03:43:04','2026-10-25 03:43:04'),(25,'Student','student','Enrolled Student',0,'2026-10-25 04:26:03','2026-10-25 04:26:03'),(26,'Parent','parent','Student Guardian / Parent',0,'2026-10-25 04:26:03','2026-10-25 04:26:03');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_types`
--

DROP TABLE IF EXISTS `room_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_types_school_id_foreign` (`school_id`),
  CONSTRAINT `room_types_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_types`
--

LOCK TABLES `room_types` WRITE;
/*!40000 ALTER TABLE `room_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `room_type_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rooms_school_id_foreign` (`school_id`),
  KEY `rooms_room_type_id_foreign` (`room_type_id`),
  CONSTRAINT `rooms_room_type_id_foreign` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rooms_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_bank_accounts`
--

DROP TABLE IF EXISTS `school_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_bank_accounts_school_id_foreign` (`school_id`),
  CONSTRAINT `school_bank_accounts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_bank_accounts`
--

LOCK TABLES `school_bank_accounts` WRITE;
/*!40000 ALTER TABLE `school_bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_classes`
--

DROP TABLE IF EXISTS `school_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` enum('junior_school','senior_school','secondary_844') NOT NULL DEFAULT 'junior_school',
  `curriculum` enum('CBC','8-4-4') NOT NULL DEFAULT 'CBC',
  `numeric_level` int(11) NOT NULL DEFAULT 7,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_classes_school_id_foreign` (`school_id`),
  CONSTRAINT `school_classes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_classes`
--

LOCK TABLES `school_classes` WRITE;
/*!40000 ALTER TABLE `school_classes` DISABLE KEYS */;
INSERT INTO `school_classes` VALUES (19,12,'Grade 7','junior_school','CBC',7,1,'2026-10-25 04:25:08','2026-10-25 04:25:08'),(20,12,'Grade 8','junior_school','CBC',7,1,'2026-10-25 04:25:18','2026-10-25 04:25:18'),(21,12,'Grade 9','junior_school','CBC',7,1,'2026-10-25 04:25:27','2026-10-25 04:25:27'),(22,12,'Grade 10','junior_school','CBC',7,1,'2026-10-25 04:25:36','2026-10-25 04:25:36');
/*!40000 ALTER TABLE `school_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_features`
--

DROP TABLE IF EXISTS `school_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `feature_key` varchar(60) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `source` varchar(30) NOT NULL DEFAULT 'package' COMMENT 'package|superadmin|override',
  `expires_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_features_school_id_feature_key_unique` (`school_id`,`feature_key`),
  CONSTRAINT `school_features_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_features`
--

LOCK TABLES `school_features` WRITE;
/*!40000 ALTER TABLE `school_features` DISABLE KEYS */;
INSERT INTO `school_features` VALUES (76,12,'student_management',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(77,12,'teacher_management',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(78,12,'parent_portal',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(79,12,'student_portal',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(80,12,'finance',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(81,12,'mpesa',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(82,12,'examinations',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(83,12,'attendance',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(84,12,'timetable',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(85,12,'library',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(86,12,'transport',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(87,12,'hostel',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(88,12,'sms',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(89,12,'reports',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30'),(90,12,'communication',1,'package',NULL,NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30');
/*!40000 ALTER TABLE `school_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_population_allocations`
--

DROP TABLE IF EXISTS `school_population_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_population_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_limit` int(10) unsigned DEFAULT NULL,
  `teacher_limit` int(10) unsigned DEFAULT NULL,
  `parent_limit` int(10) unsigned DEFAULT NULL,
  `admin_limit` int(10) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_population_allocations_school_id_foreign` (`school_id`),
  CONSTRAINT `school_population_allocations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_population_allocations`
--

LOCK TABLES `school_population_allocations` WRITE;
/*!40000 ALTER TABLE `school_population_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_population_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_settings`
--

DROP TABLE IF EXISTS `school_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `school_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'KES',
  `timezone` varchar(255) NOT NULL DEFAULT 'Africa/Nairobi',
  `daraja_shortcode` varchar(255) DEFAULT '174379',
  `daraja_consumer_key` varchar(255) DEFAULT NULL,
  `daraja_consumer_secret` varchar(255) DEFAULT NULL,
  `daraja_passkey` varchar(255) DEFAULT NULL,
  `daraja_environment` varchar(255) NOT NULL DEFAULT 'sandbox',
  `daraja_paybill_number` varchar(255) DEFAULT NULL,
  `daraja_till_number` varchar(255) DEFAULT NULL,
  `daraja_business_name` varchar(255) DEFAULT NULL,
  `daraja_callback_url` varchar(255) DEFAULT NULL,
  `enabled_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`enabled_payment_methods`)),
  `preferred_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_payment_methods`)),
  `sms_sender_id` varchar(255) DEFAULT 'CLOUDKAZI',
  `sms_api_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_settings_school_id_foreign` (`school_id`),
  CONSTRAINT `school_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_settings`
--

LOCK TABLES `school_settings` WRITE;
/*!40000 ALTER TABLE `school_settings` DISABLE KEYS */;
INSERT INTO `school_settings` VALUES (10,12,'KES','Africa/Nairobi','174379',NULL,NULL,NULL,'sandbox',NULL,NULL,NULL,NULL,NULL,NULL,'CLOUDKAZI',NULL,'2026-10-25 04:19:30','2026-10-25 04:19:30');
/*!40000 ALTER TABLE `school_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `motto` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT 'Nairobi',
  `logo` varchar(255) DEFAULT NULL,
  `curriculum_type` enum('CBC','8-4-4','Hybrid') NOT NULL DEFAULT 'Hybrid',
  `status` enum('active','trial','pending','grace_period','expired','suspended','cancelled') NOT NULL DEFAULT 'trial',
  `trial_ends_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schools_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` VALUES (12,'Kitui School','KT','kitui@gmail.com','+254705556565','Excellence in Education',NULL,'Nairobi',NULL,'CBC','active','2026-11-24','2026-10-25 04:19:30','2026-10-25 04:19:30');
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
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
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `recipient_phone` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'sent',
  `message_type` varchar(255) NOT NULL DEFAULT 'General',
  `cost` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_school_id_foreign` (`school_id`),
  CONSTRAINT `sms_logs_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streams`
--

DROP TABLE IF EXISTS `streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `streams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `pathway` enum('general','STEM','Social Sciences','Arts & Sports') NOT NULL DEFAULT 'general',
  `class_teacher_id` bigint(20) unsigned DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 45,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `streams_school_id_foreign` (`school_id`),
  KEY `streams_school_class_id_foreign` (`school_class_id`),
  KEY `streams_class_teacher_id_foreign` (`class_teacher_id`),
  CONSTRAINT `streams_class_teacher_id_foreign` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `streams_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `streams_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `streams`
--

LOCK TABLES `streams` WRITE;
/*!40000 ALTER TABLE `streams` DISABLE KEYS */;
/*!40000 ALTER TABLE `streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_admissions`
--

DROP TABLE IF EXISTS `student_admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_admissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `applicant_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female') NOT NULL DEFAULT 'Male',
  `dob` date NOT NULL,
  `target_class_id` bigint(20) unsigned NOT NULL,
  `parent_name` varchar(255) NOT NULL,
  `parent_phone` varchar(255) NOT NULL,
  `parent_email` varchar(255) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `previous_marks` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_admissions_school_id_foreign` (`school_id`),
  KEY `student_admissions_target_class_id_foreign` (`target_class_id`),
  CONSTRAINT `student_admissions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_admissions_target_class_id_foreign` FOREIGN KEY (`target_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_admissions`
--

LOCK TABLES `student_admissions` WRITE;
/*!40000 ALTER TABLE `student_admissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_attendances`
--

DROP TABLE IF EXISTS `student_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_attendances_school_id_student_id_attendance_date_unique` (`school_id`,`student_id`,`attendance_date`),
  KEY `student_attendances_student_id_foreign` (`student_id`),
  KEY `student_attendances_stream_id_foreign` (`stream_id`),
  KEY `student_attendances_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `student_attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_attendances_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_attendances`
--

LOCK TABLES `student_attendances` WRITE;
/*!40000 ALTER TABLE `student_attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_guardian`
--

DROP TABLE IF EXISTS `student_guardian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_guardian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `guardian_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_guardian_student_id_foreign` (`student_id`),
  KEY `student_guardian_guardian_id_foreign` (`guardian_id`),
  CONSTRAINT `student_guardian_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_guardian_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_guardian`
--

LOCK TABLES `student_guardian` WRITE;
/*!40000 ALTER TABLE `student_guardian` DISABLE KEYS */;
INSERT INTO `student_guardian` VALUES (5,15,5,1,'2026-10-25 04:26:05','2026-10-25 04:26:05'),(6,16,6,1,'2026-10-25 04:26:06','2026-10-25 04:26:06'),(7,17,7,1,'2026-10-25 04:26:07','2026-10-25 04:26:07'),(8,18,8,1,'2026-10-25 04:26:09','2026-10-25 04:26:09'),(9,19,9,1,'2026-10-25 04:26:10','2026-10-25 04:26:10');
/*!40000 ALTER TABLE `student_guardian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_hostels`
--

DROP TABLE IF EXISTS `student_hostels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_hostels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `dormitory_id` bigint(20) unsigned NOT NULL,
  `hostel_room_id` bigint(20) unsigned DEFAULT NULL,
  `bed_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_hostels_school_id_foreign` (`school_id`),
  KEY `student_hostels_student_id_foreign` (`student_id`),
  KEY `student_hostels_dormitory_id_foreign` (`dormitory_id`),
  KEY `student_hostels_hostel_room_id_foreign` (`hostel_room_id`),
  CONSTRAINT `student_hostels_dormitory_id_foreign` FOREIGN KEY (`dormitory_id`) REFERENCES `dormitories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_hostels_hostel_room_id_foreign` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_hostels_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_hostels_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_hostels`
--

LOCK TABLES `student_hostels` WRITE;
/*!40000 ALTER TABLE `student_hostels` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_hostels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_invoices`
--

DROP TABLE IF EXISTS `student_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `waiver_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','partially_paid','paid') NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_invoices_school_id_invoice_number_unique` (`school_id`,`invoice_number`),
  KEY `student_invoices_student_id_foreign` (`student_id`),
  KEY `student_invoices_academic_year_id_foreign` (`academic_year_id`),
  KEY `student_invoices_term_id_foreign` (`term_id`),
  CONSTRAINT `student_invoices_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_invoices_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_invoices_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_invoices`
--

LOCK TABLES `student_invoices` WRITE;
/*!40000 ALTER TABLE `student_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_report_summaries`
--

DROP TABLE IF EXISTS `student_report_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_report_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `examination_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned DEFAULT NULL,
  `total_score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `mean_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `mean_grade` varchar(3) DEFAULT NULL,
  `mean_points` decimal(4,2) NOT NULL DEFAULT 0.00,
  `stream_rank` int(11) DEFAULT NULL,
  `class_rank` int(11) DEFAULT NULL,
  `class_teacher_remarks` text DEFAULT NULL,
  `principal_remarks` text DEFAULT NULL,
  `pathway_recommendation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_summary_unique` (`school_id`,`examination_id`,`student_id`),
  KEY `student_report_summaries_examination_id_foreign` (`examination_id`),
  KEY `student_report_summaries_student_id_foreign` (`student_id`),
  KEY `student_report_summaries_school_class_id_foreign` (`school_class_id`),
  KEY `student_report_summaries_stream_id_foreign` (`stream_id`),
  CONSTRAINT `student_report_summaries_examination_id_foreign` FOREIGN KEY (`examination_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_report_summaries_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_report_summaries_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_report_summaries_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_report_summaries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_report_summaries`
--

LOCK TABLES `student_report_summaries` WRITE;
/*!40000 ALTER TABLE `student_report_summaries` DISABLE KEYS */;
INSERT INTO `student_report_summaries` VALUES (11,12,3,15,19,NULL,263.00,87.67,'A',11.67,NULL,1,'Exemplary academic achievement. Keep up the high standards!','Outstanding results. Highly commended for discipline and diligence.','Arts & Sports Science','2026-10-25 04:39:11','2026-10-25 04:39:11'),(12,12,4,15,19,NULL,187.00,93.50,'A',12.00,NULL,1,'Exemplary academic achievement. Keep up the high standards!','Outstanding results. Highly commended for discipline and diligence.','Arts & Sports Science','2026-10-25 04:42:48','2026-10-25 04:43:04');
/*!40000 ALTER TABLE `student_report_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_transports`
--

DROP TABLE IF EXISTS `student_transports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_transports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `transport_route_id` bigint(20) unsigned NOT NULL,
  `pickup_point` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_transports_school_id_foreign` (`school_id`),
  KEY `student_transports_student_id_foreign` (`student_id`),
  KEY `student_transports_transport_route_id_foreign` (`transport_route_id`),
  CONSTRAINT `student_transports_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_transports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_transports_transport_route_id_foreign` FOREIGN KEY (`transport_route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_transports`
--

LOCK TABLES `student_transports` WRITE;
/*!40000 ALTER TABLE `student_transports` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_transports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admission_number` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `other_names` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `dob` date NOT NULL,
  `admission_date` date NOT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned DEFAULT NULL,
  `curriculum` enum('CBC','8-4-4') NOT NULL DEFAULT 'CBC',
  `fee_profile` enum('regular','boarder','transport','boarder_transport') NOT NULL DEFAULT 'regular',
  `pathway` enum('general','STEM','Social Sciences','Arts & Sports') NOT NULL DEFAULT 'general',
  `nemis_upi` varchar(255) DEFAULT NULL,
  `assessment_number` varchar(255) DEFAULT NULL,
  `birth_certificate_no` varchar(255) DEFAULT NULL,
  `kcpe_score` varchar(255) DEFAULT NULL,
  `passport_photo` varchar(255) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `blood_group` varchar(255) DEFAULT NULL,
  `status` enum('active','transferred','suspended','alumni') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_user_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_school_id_admission_number_unique` (`school_id`,`admission_number`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_school_class_id_foreign` (`school_class_id`),
  KEY `students_stream_id_foreign` (`stream_id`),
  KEY `students_parent_user_id_foreign` (`parent_user_id`),
  CONSTRAINT `students_parent_user_id_foreign` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (15,12,39,'ADM20261001','Amina','Hassan',NULL,'Female','2013-03-14','2026-10-25',19,NULL,'CBC','regular','general',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-25 04:26:03','2026-10-25 04:26:05',40),(16,12,41,'ADM20261002','Samuel','Kiptoo',NULL,'Male','2012-08-21','2026-10-25',20,NULL,'CBC','regular','general',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-25 04:26:05','2026-10-25 04:26:06',42),(17,12,43,'ADM20261003','Lydia','Njeri',NULL,'Female','2011-11-05','2026-10-25',21,NULL,'CBC','regular','general',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-25 04:26:06','2026-10-25 04:26:07',44),(18,12,45,'ADM20261004','Brian','Otieno',NULL,'Male','2010-06-17','2026-10-25',22,NULL,'CBC','regular','general',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-25 04:26:08','2026-10-25 04:26:09',46),(19,12,47,'ADM20261005','Zawadi','Mutua',NULL,'Female','2012-01-29','2026-10-25',20,NULL,'CBC','regular','general',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-25 04:26:09','2026-10-25 04:26:10',48);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_offerings`
--

DROP TABLE IF EXISTS `subject_offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_offerings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned DEFAULT NULL,
  `pathway` enum('general','STEM','Social Sciences','Arts & Sports') NOT NULL DEFAULT 'general',
  `subject_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `v_stream_id` int(11) GENERATED ALWAYS AS (coalesce(`stream_id`,0)) VIRTUAL,
  `v_year_id` int(11) GENERATED ALWAYS AS (coalesce(`academic_year_id`,0)) VIRTUAL,
  `v_term_id` int(11) GENERATED ALWAYS AS (coalesce(`term_id`,0)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_offering_unique` (`school_id`,`v_year_id`,`v_term_id`,`school_class_id`,`v_stream_id`,`subject_id`),
  KEY `subject_offerings_school_id_index` (`school_id`),
  KEY `subject_offerings_school_class_id_index` (`school_class_id`),
  KEY `subject_offerings_stream_id_index` (`stream_id`),
  KEY `subject_offerings_subject_id_index` (`subject_id`),
  KEY `subject_offerings_school_id_school_class_id_index` (`school_id`,`school_class_id`),
  KEY `subject_offerings_school_id_academic_year_id_term_id_index` (`school_id`,`academic_year_id`,`term_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_offerings`
--

LOCK TABLES `subject_offerings` WRITE;
/*!40000 ALTER TABLE `subject_offerings` DISABLE KEYS */;
INSERT INTO `subject_offerings` VALUES (1,12,3,8,19,NULL,'general',37,1,'2026-10-25 04:37:41','2026-10-25 04:37:41',0,3,8),(2,12,NULL,NULL,19,NULL,'general',39,1,'2026-10-25 04:38:03','2026-10-25 04:38:03',0,0,0),(3,12,NULL,NULL,19,NULL,'general',34,1,'2026-10-25 04:38:13','2026-10-25 04:38:13',0,0,0);
/*!40000 ALTER TABLE `subject_offerings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_rules`
--

DROP TABLE IF EXISTS `subject_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subject_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `lessons_per_week` int(11) NOT NULL DEFAULT 4,
  `requires_double_lesson` tinyint(1) NOT NULL DEFAULT 0,
  `preferred_periods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_periods`)),
  `avoid_last_lesson` tinyint(1) NOT NULL DEFAULT 0,
  `room_requirement` varchar(255) DEFAULT NULL,
  `priority_level` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_rules_school_id_foreign` (`school_id`),
  KEY `subject_rules_subject_id_foreign` (`subject_id`),
  CONSTRAINT `subject_rules_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_rules_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_rules`
--

LOCK TABLES `subject_rules` WRITE;
/*!40000 ALTER TABLE `subject_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `curriculum` enum('CBC','8-4-4','Both') NOT NULL DEFAULT 'Both',
  `pathway` enum('general','STEM','Social Sciences','Arts & Sports') NOT NULL DEFAULT 'general',
  `is_optional` tinyint(1) NOT NULL DEFAULT 0,
  `lessons_per_week` int(11) NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subjects_school_id_foreign` (`school_id`),
  CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (33,12,'Computer','COMP','CBC','general',0,5,1,'2026-10-25 04:30:35','2026-10-25 04:30:35'),(34,12,'Kiswahili','KIS','CBC','general',0,5,1,'2026-10-25 04:30:53','2026-10-25 04:30:53'),(35,12,'Maths','MAT','CBC','general',0,5,1,'2026-10-25 04:31:14','2026-10-25 04:31:14'),(36,12,'English','ENG','CBC','general',0,5,1,'2026-10-25 04:31:25','2026-10-25 04:31:25'),(37,12,'Agriculture','AGR','CBC','general',0,5,1,'2026-10-25 04:31:39','2026-10-25 04:31:39'),(38,12,'Chemistry','CHEM','CBC','general',0,5,1,'2026-10-25 04:31:54','2026-10-25 04:31:54'),(39,12,'Biology','Bio','CBC','general',0,5,1,'2026-10-25 04:32:06','2026-10-25 04:32:06');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_payments`
--

DROP TABLE IF EXISTS `subscription_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `package_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'mpesa' COMMENT 'mpesa|bank|paystack|manual',
  `reference` varchar(100) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending|processing|successful|failed|cancelled|refunded',
  `mpesa_checkout_request_id` varchar(100) DEFAULT NULL,
  `mpesa_merchant_request_id` varchar(100) DEFAULT NULL,
  `mpesa_receipt` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `failure_reason` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_ref_unique` (`reference`),
  KEY `subscription_payments_school_id_foreign` (`school_id`),
  KEY `subscription_payments_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `subscription_payments_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_payments`
--

LOCK TABLES `subscription_payments` WRITE;
/*!40000 ALTER TABLE `subscription_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `package_id` bigint(20) unsigned DEFAULT NULL,
  `plan_name` varchar(255) NOT NULL DEFAULT 'Standard ERP',
  `amount` decimal(12,2) NOT NULL DEFAULT 35000.00,
  `activation_fee` decimal(10,2) DEFAULT NULL,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `status` enum('active','trial','pending','grace_period','expired','suspended','cancelled') NOT NULL DEFAULT 'trial',
  `payment_status` varchar(30) NOT NULL DEFAULT 'pending',
  `student_limit` int(10) unsigned DEFAULT NULL,
  `teacher_limit` int(10) unsigned DEFAULT NULL,
  `parent_limit` int(10) unsigned DEFAULT NULL,
  `admin_limit` int(10) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `grace_period_ends_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sms_balance` int(11) NOT NULL DEFAULT 1000,
  `storage_limit_mb` int(11) NOT NULL DEFAULT 5120,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_school_id_foreign` (`school_id`),
  CONSTRAINT `subscriptions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (10,12,1,'Standard (Legacy)',30000.00,0.00,30000.00,'active','successful',NULL,NULL,NULL,NULL,'2026-10-25','2026-11-24',NULL,NULL,NULL,NULL,NULL,1000,5120,'2026-10-25 04:19:30','2026-10-25 04:19:30');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_attendances`
--

DROP TABLE IF EXISTS `teacher_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('present','absent','late','on_leave') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_attendances_school_id_teacher_id_attendance_date_unique` (`school_id`,`teacher_id`,`attendance_date`),
  KEY `teacher_attendances_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `teacher_attendances_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_attendances_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_attendances`
--

LOCK TABLES `teacher_attendances` WRITE;
/*!40000 ALTER TABLE `teacher_attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_availabilities`
--

DROP TABLE IF EXISTS `teacher_availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_availabilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `available_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_days`)),
  `available_periods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_periods`)),
  `blocked_periods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`blocked_periods`)),
  `max_workload_per_day` int(11) NOT NULL DEFAULT 6,
  `max_workload_per_week` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_availabilities_school_id_foreign` (`school_id`),
  KEY `teacher_availabilities_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `teacher_availabilities_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_availabilities_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_availabilities`
--

LOCK TABLES `teacher_availabilities` WRITE;
/*!40000 ALTER TABLE `teacher_availabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_availabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subject_allocations`
--

DROP TABLE IF EXISTS `teacher_subject_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_subject_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_offering_id` bigint(20) unsigned DEFAULT NULL,
  `school_class_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `term_id` bigint(20) unsigned DEFAULT NULL,
  `lessons_per_week` int(11) NOT NULL DEFAULT 5,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_stream_scope` int(11) GENERATED ALWAYS AS (if(`is_active` = 1,ifnull(`stream_id`,0),NULL)) VIRTUAL,
  `scope_year` int(11) GENERATED ALWAYS AS (ifnull(`academic_year_id`,0)) VIRTUAL,
  `scope_term` int(11) GENERATED ALWAYS AS (ifnull(`term_id`,0)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_alloc_composite_unique` (`school_id`,`teacher_id`,`subject_id`,`school_class_id`,`active_stream_scope`,`scope_year`,`scope_term`),
  KEY `teacher_subject_allocations_teacher_id_foreign` (`teacher_id`),
  KEY `teacher_subject_allocations_subject_id_foreign` (`subject_id`),
  KEY `teacher_subject_allocations_school_class_id_foreign` (`school_class_id`),
  KEY `teacher_subject_allocations_stream_id_foreign` (`stream_id`),
  KEY `teacher_subject_allocations_academic_year_id_foreign` (`academic_year_id`),
  KEY `teacher_subject_allocations_term_id_foreign` (`term_id`),
  KEY `teacher_subject_allocations_subject_offering_id_index` (`subject_offering_id`),
  CONSTRAINT `teacher_subject_allocations_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `teacher_subject_allocations_school_class_id_foreign` FOREIGN KEY (`school_class_id`) REFERENCES `school_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subject_allocations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subject_allocations_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subject_allocations_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subject_allocations_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_subject_allocations_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subject_allocations`
--

LOCK TABLES `teacher_subject_allocations` WRITE;
/*!40000 ALTER TABLE `teacher_subject_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_subject_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `staff_no` varchar(255) DEFAULT NULL,
  `tsc_number` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `qualification` varchar(255) DEFAULT NULL,
  `designation` varchar(255) NOT NULL DEFAULT 'Teacher',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `max_weekly_lessons` int(11) NOT NULL DEFAULT 28,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teachers_school_id_foreign` (`school_id`),
  KEY `teachers_user_id_foreign` (`user_id`),
  KEY `teachers_department_id_foreign` (`department_id`),
  CONSTRAINT `teachers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `teachers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_allocations`
--

DROP TABLE IF EXISTS `timetable_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned NOT NULL,
  `timetable_slot_id` bigint(20) unsigned NOT NULL,
  `stream_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `classroom_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_allocations_school_id_foreign` (`school_id`),
  KEY `timetable_allocations_timetable_id_foreign` (`timetable_id`),
  KEY `timetable_allocations_timetable_slot_id_foreign` (`timetable_slot_id`),
  KEY `timetable_allocations_stream_id_foreign` (`stream_id`),
  KEY `timetable_allocations_subject_id_foreign` (`subject_id`),
  KEY `timetable_allocations_teacher_id_foreign` (`teacher_id`),
  KEY `timetable_allocations_classroom_id_foreign` (`classroom_id`),
  CONSTRAINT `timetable_allocations_classroom_id_foreign` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timetable_allocations_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_allocations_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_allocations_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_allocations_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timetable_allocations_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_allocations_timetable_slot_id_foreign` FOREIGN KEY (`timetable_slot_id`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_allocations`
--

LOCK TABLES `timetable_allocations` WRITE;
/*!40000 ALTER TABLE `timetable_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_conflicts`
--

DROP TABLE IF EXISTS `timetable_conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_conflicts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `day_of_week` varchar(255) DEFAULT NULL,
  `period_number` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_conflicts_school_id_foreign` (`school_id`),
  KEY `timetable_conflicts_timetable_id_foreign` (`timetable_id`),
  CONSTRAINT `timetable_conflicts_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_conflicts_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_conflicts`
--

LOCK TABLES `timetable_conflicts` WRITE;
/*!40000 ALTER TABLE `timetable_conflicts` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_conflicts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_generation_settings`
--

DROP TABLE IF EXISTS `timetable_generation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_generation_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned DEFAULT NULL,
  `max_lessons_per_teacher_per_day` int(11) NOT NULL DEFAULT 6,
  `max_consecutive_lessons_per_teacher` int(11) NOT NULL DEFAULT 2,
  `max_lessons_per_stream_per_day` int(11) NOT NULL DEFAULT 6,
  `allow_double_lessons` tinyint(1) NOT NULL DEFAULT 1,
  `practical_subjects_consecutive` tinyint(1) NOT NULL DEFAULT 0,
  `core_subjects_distributed` tinyint(1) NOT NULL DEFAULT 1,
  `enforce_teacher_availability` tinyint(1) NOT NULL DEFAULT 1,
  `enforce_room_availability` tinyint(1) NOT NULL DEFAULT 1,
  `conflict_free_generation` tinyint(1) NOT NULL DEFAULT 1,
  `preferred_scheduling_strategy` varchar(255) NOT NULL DEFAULT 'Balanced',
  `generate_draft_only` tinyint(1) NOT NULL DEFAULT 1,
  `allow_incomplete_timetable` tinyint(1) NOT NULL DEFAULT 0,
  `strict_validation` tinyint(1) NOT NULL DEFAULT 1,
  `activity_blocks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_blocks`)),
  `break_duration_minutes` int(11) NOT NULL DEFAULT 30,
  `school_end_time` varchar(20) NOT NULL DEFAULT '16:30:00',
  `school_start_time` varchar(20) NOT NULL DEFAULT '08:00:00',
  `lesson_duration_minutes` int(11) NOT NULL DEFAULT 45,
  `lessons_per_day` int(11) NOT NULL DEFAULT 8,
  `school_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`school_days`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_generation_settings_school_id_foreign` (`school_id`),
  KEY `timetable_generation_settings_timetable_id_foreign` (`timetable_id`),
  CONSTRAINT `timetable_generation_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_generation_settings_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_generation_settings`
--

LOCK TABLES `timetable_generation_settings` WRITE;
/*!40000 ALTER TABLE `timetable_generation_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_generation_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_slots`
--

DROP TABLE IF EXISTS `timetable_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `period_number` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_break` tinyint(1) NOT NULL DEFAULT 0,
  `is_teaching_period` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL DEFAULT 'lesson',
  `duration_minutes` int(11) NOT NULL DEFAULT 45,
  `break_label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_slots_school_id_foreign` (`school_id`),
  KEY `timetable_slots_timetable_id_foreign` (`timetable_id`),
  CONSTRAINT `timetable_slots_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_slots_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_slots`
--

LOCK TABLES `timetable_slots` WRITE;
/*!40000 ALTER TABLE `timetable_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_versions`
--

DROP TABLE IF EXISTS `timetable_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetable_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `timetable_id` bigint(20) unsigned NOT NULL,
  `version_label` varchar(255) NOT NULL DEFAULT 'Draft',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetable_versions_school_id_foreign` (`school_id`),
  KEY `timetable_versions_timetable_id_foreign` (`timetable_id`),
  CONSTRAINT `timetable_versions_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_versions_timetable_id_foreign` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_versions`
--

LOCK TABLES `timetable_versions` WRITE;
/*!40000 ALTER TABLE `timetable_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('class','exam') NOT NULL DEFAULT 'class',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetables_school_id_foreign` (`school_id`),
  KEY `timetables_academic_year_id_foreign` (`academic_year_id`),
  KEY `timetables_term_id_foreign` (`term_id`),
  CONSTRAINT `timetables_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `academic_terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_routes`
--

DROP TABLE IF EXISTS `transport_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `vehicle_registration` varchar(255) NOT NULL,
  `driver_name` varchar(255) NOT NULL,
  `driver_phone` varchar(255) NOT NULL,
  `termly_fare` decimal(10,2) NOT NULL DEFAULT 12000.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transport_routes_school_id_foreign` (`school_id`),
  CONSTRAINT `transport_routes_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_routes`
--

LOCK TABLES `transport_routes` WRITE;
/*!40000 ALTER TABLE `transport_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint(20) unsigned DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_school_id_foreign` (`school_id`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (36,NULL,23,'CloudKazi Super Administrator','info@cloudkazi.co.ke',NULL,NULL,'$2y$12$T/q5TV/dLCOlJq.ZLP0s4u9K8rl3KQJz1AI/yoWUQq6X4bZrD8ZYy',NULL,1,0,'2026-10-25 04:17:58',NULL,'2026-10-25 03:15:16','2026-10-25 04:17:58'),(38,12,24,'Kinyua','kitui@gmail.com','0705556565',NULL,'$2y$12$8qf9dFE2Fo0caepRK3Voce9.JuPMBKJLRwBvl2GfP67eIBGfDHJ6i',NULL,1,0,'2026-10-25 07:27:52',NULL,'2026-10-25 04:19:31','2026-10-25 07:27:52'),(39,12,25,'Amina Hassan','adm20261001-school-12@student.cloudkazi.com',NULL,NULL,'$2y$12$OHXZ1wbgRdt/mRe0I7qPh.6yUUYiPVeYpOpR9TWb506qdbbacNfUy',NULL,1,0,'2026-10-25 07:25:13',NULL,'2026-10-25 04:26:04','2026-10-25 07:25:13'),(40,12,26,'Fatuma Hassan','fatuma.hassan@example.com','0711111111',NULL,'$2y$12$BtJ3sSNcfppzaTEIa6ugG.dKTE.Oi8aaHlhHeL9pcEfehcNeFFQsO',NULL,1,0,'2026-10-25 07:08:04',NULL,'2026-10-25 04:26:05','2026-10-25 07:08:04'),(41,12,25,'Samuel Kiptoo','adm20261002-school-12@student.cloudkazi.com',NULL,NULL,'$2y$12$bdKonAkFP0oIoBfHaVHAtu.WnItnznTCTuYy3E8F5L.CDDI7WQG1W',NULL,1,0,NULL,NULL,'2026-10-25 04:26:05','2026-10-25 04:26:05'),(42,12,26,'David Kiptoo','david.kiptoo@example.com','0722222222',NULL,'$2y$12$UmGlO6FrceVkrNnKvLUgD.tpS.d6LEIsQ9oyZhd6QjbL5jNK2I1vS',NULL,1,0,NULL,NULL,'2026-10-25 04:26:06','2026-10-25 04:26:06'),(43,12,25,'Lydia Njeri','adm20261003-school-12@student.cloudkazi.com',NULL,NULL,'$2y$12$cCsj7fNOAEhWwChuUCRMk.TISms/cMFCeZICX9tVUuyKxdmZzu5.S',NULL,1,0,NULL,NULL,'2026-10-25 04:26:07','2026-10-25 04:26:07'),(44,12,26,'Grace Njeri','grace.njeri@example.com','0733333333',NULL,'$2y$12$0.uLDzYt6xMFZVPSUBEexuVLTuDb7ZM1V6mkY3OMUlUvDDC9j3ASe',NULL,1,0,NULL,NULL,'2026-10-25 04:26:07','2026-10-25 04:26:07'),(45,12,25,'Brian Otieno','adm20261004-school-12@student.cloudkazi.com',NULL,NULL,'$2y$12$gdHfz1yuZjzPRMHiw3VSd.VY9Nhl3.GeDLQn02NZ/tI12rsn6vDB2',NULL,1,0,NULL,NULL,'2026-10-25 04:26:08','2026-10-25 04:26:08'),(46,12,26,'Mary Otieno','mary.otieno@example.com','0744444444',NULL,'$2y$12$IMbd0NMVZX4aAVZVfbFAvee287Cc15tcvyGguFv7IXzTq3JPRYb9.',NULL,1,0,NULL,NULL,'2026-10-25 04:26:09','2026-10-25 04:26:09'),(47,12,25,'Zawadi Mutua','adm20261005-school-12@student.cloudkazi.com',NULL,NULL,'$2y$12$ZlsyIj6natzl1oxSxiUo0eHL8UCsBNFAbG7oWymBgONcvC4JuTe/i',NULL,1,0,NULL,NULL,'2026-10-25 04:26:09','2026-10-25 04:26:09'),(48,12,26,'Peter Mutua','peter.mutua@example.com','0755555555',NULL,'$2y$12$62eexgKxZO8k/gvckHsE9.Ijq91wZEmwFY4NHBGQe9MEHPmdXoMEe',NULL,1,0,NULL,NULL,'2026-10-25 04:26:10','2026-10-25 04:26:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-25 13:46:34
