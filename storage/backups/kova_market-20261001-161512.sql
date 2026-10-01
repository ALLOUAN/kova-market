-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: kova_market
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `attribute_changes` json DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'default','created','App\\Models\\Category',1,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-basket-shopping\", \"name\": \"Mon Marché\", \"slug\": \"mon-marche\", \"image\": null, \"promo\": {\"label\": \"Mon Marché\", \"title\": \"Le marché livré chez vous\", \"button\": \"Découvrir Mon Marché\", \"subtitle\": \"Produits frais, épicerie et boissons\"}, \"tagline\": \"Le marché livré chez vous\", \"position\": 0, \"parent_id\": null, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(2,'default','created','App\\Models\\Category',2,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-carrot\", \"name\": \"Fruits et légumes\", \"slug\": \"fruits-et-legumes\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(3,'default','created','App\\Models\\Category',3,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Légumes frais\", \"slug\": \"legumes-frais\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 2, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(4,'default','created','App\\Models\\Category',4,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Fruits frais\", \"slug\": \"fruits-frais\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 2, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(5,'default','created','App\\Models\\Category',5,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Tubercules et plantain\", \"slug\": \"tubercules-et-plantain\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 2, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(6,'default','created','App\\Models\\Category',6,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Piments, ail et aromates\", \"slug\": \"piments-ail-et-aromates\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 2, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(7,'default','created','App\\Models\\Category',7,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-drumstick-bite\", \"name\": \"Viandes, volailles et poissons\", \"slug\": \"viandes-volailles-et-poissons\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(8,'default','created','App\\Models\\Category',8,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Bœuf\", \"slug\": \"boeuf\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(9,'default','created','App\\Models\\Category',9,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Mouton et chèvre\", \"slug\": \"mouton-et-chevre\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(10,'default','created','App\\Models\\Category',10,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Porc\", \"slug\": \"porc\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(11,'default','created','App\\Models\\Category',11,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Volaille\", \"slug\": \"volaille\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(12,'default','created','App\\Models\\Category',12,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Poissons frais\", \"slug\": \"poissons-frais\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 4, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(13,'default','created','App\\Models\\Category',13,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Poissons fumés et séchés\", \"slug\": \"poissons-fumes-et-seches\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 5, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(14,'default','created','App\\Models\\Category',14,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Crevettes et fruits de mer\", \"slug\": \"crevettes-et-fruits-de-mer\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 6, \"parent_id\": 7, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(15,'default','created','App\\Models\\Category',15,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-wheat-awn\", \"name\": \"Céréales et légumineuses\", \"slug\": \"cereales-et-legumineuses\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(16,'default','created','App\\Models\\Category',16,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Riz\", \"slug\": \"riz\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(17,'default','created','App\\Models\\Category',17,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Attiéké et semoules\", \"slug\": \"attieke-et-semoules\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(18,'default','created','App\\Models\\Category',18,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Maïs, mil et sorgho\", \"slug\": \"mais-mil-et-sorgho\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(19,'default','created','App\\Models\\Category',19,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Fonio\", \"slug\": \"fonio\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(20,'default','created','App\\Models\\Category',20,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Haricots et niébé\", \"slug\": \"haricots-et-niebe\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 4, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(21,'default','created','App\\Models\\Category',21,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Arachides\", \"slug\": \"arachides\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 5, \"parent_id\": 15, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(22,'default','created','App\\Models\\Category',22,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-jar\", \"name\": \"Épicerie\", \"slug\": \"epicerie\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(23,'default','created','App\\Models\\Category',23,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Huiles\", \"slug\": \"huiles\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(24,'default','created','App\\Models\\Category',24,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Pâtes et farines\", \"slug\": \"pates-et-farines\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(25,'default','created','App\\Models\\Category',25,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Conserves\", \"slug\": \"conserves\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(26,'default','created','App\\Models\\Category',26,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Sucre et sel\", \"slug\": \"sucre-et-sel\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(27,'default','created','App\\Models\\Category',27,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Épices et bouillons\", \"slug\": \"epices-et-bouillons\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 4, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(28,'default','created','App\\Models\\Category',28,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Biscuits et chocolats\", \"slug\": \"biscuits-et-chocolats\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 5, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(29,'default','created','App\\Models\\Category',29,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Céréales du petit-déjeuner\", \"slug\": \"cereales-du-petit-dejeuner\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 6, \"parent_id\": 22, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(30,'default','created','App\\Models\\Category',30,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-egg\", \"name\": \"Produits frais et laitiers\", \"slug\": \"produits-frais-et-laitiers\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 4, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(31,'default','created','App\\Models\\Category',31,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Œufs\", \"slug\": \"oeufs\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 30, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(32,'default','created','App\\Models\\Category',32,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Lait et yaourts\", \"slug\": \"lait-et-yaourts\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 30, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(33,'default','created','App\\Models\\Category',33,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Beurre et fromages\", \"slug\": \"beurre-et-fromages\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 30, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(34,'default','created','App\\Models\\Category',34,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-bread-slice\", \"name\": \"Boulangerie et pâtisserie\", \"slug\": \"boulangerie-et-patisserie\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 5, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(35,'default','created','App\\Models\\Category',35,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Pains\", \"slug\": \"pains\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 34, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(36,'default','created','App\\Models\\Category',36,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Viennoiseries\", \"slug\": \"viennoiseries\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 34, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(37,'default','created','App\\Models\\Category',37,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Gâteaux et pâtisseries\", \"slug\": \"gateaux-et-patisseries\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 34, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(38,'default','created','App\\Models\\Category',38,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-bottle-water\", \"name\": \"Boissons\", \"slug\": \"boissons\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 6, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(39,'default','created','App\\Models\\Category',39,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Eaux\", \"slug\": \"eaux\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 38, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(40,'default','created','App\\Models\\Category',40,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Jus et nectars\", \"slug\": \"jus-et-nectars\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 38, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(41,'default','created','App\\Models\\Category',41,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Boissons locales\", \"slug\": \"boissons-locales\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 38, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(42,'default','created','App\\Models\\Category',42,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Sirops\", \"slug\": \"sirops\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 3, \"parent_id\": 38, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(43,'default','created','App\\Models\\Category',43,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Sodas\", \"slug\": \"sodas\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 4, \"parent_id\": 38, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(44,'default','created','App\\Models\\Category',44,'created',NULL,NULL,'{\"attributes\": {\"icon\": \"fa-regular fa-snowflake\", \"name\": \"Surgelés\", \"slug\": \"surgeles\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 7, \"parent_id\": 1, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(45,'default','created','App\\Models\\Category',45,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Poissons surgelés\", \"slug\": \"poissons-surgeles\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 0, \"parent_id\": 44, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(46,'default','created','App\\Models\\Category',46,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Viandes surgelées\", \"slug\": \"viandes-surgelees\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 1, \"parent_id\": 44, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35'),(47,'default','created','App\\Models\\Category',47,'created',NULL,NULL,'{\"attributes\": {\"icon\": null, \"name\": \"Légumes surgelés\", \"slug\": \"legumes-surgeles\", \"image\": null, \"promo\": null, \"tagline\": null, \"position\": 2, \"parent_id\": 44, \"meta_title\": null, \"badge_label\": null, \"is_featured\": false, \"badge_variant\": null, \"meta_description\": null}}','[]','2026-10-01 16:04:35','2026-10-01 16:04:35');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commune_id` bigint unsigned DEFAULT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `landmark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_commune_id_foreign` (`commune_id`),
  KEY `addresses_user_id_is_default_index` (`user_id`,`is_default`),
  CONSTRAINT `addresses_commune_id_foreign` FOREIGN KEY (`commune_id`) REFERENCES `communes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_value_product_variant`
--

DROP TABLE IF EXISTS `attribute_value_product_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_value_product_variant` (
  `product_variant_id` bigint unsigned NOT NULL,
  `attribute_value_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`product_variant_id`,`attribute_value_id`),
  KEY `attribute_value_product_variant_attribute_value_id_foreign` (`attribute_value_id`),
  CONSTRAINT `attribute_value_product_variant_attribute_value_id_foreign` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `attribute_value_product_variant_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_value_product_variant`
--

LOCK TABLES `attribute_value_product_variant` WRITE;
/*!40000 ALTER TABLE `attribute_value_product_variant` DISABLE KEYS */;
/*!40000 ALTER TABLE `attribute_value_product_variant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_values`
--

DROP TABLE IF EXISTS `attribute_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_hex` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_values_attribute_id_value_unique` (`attribute_id`,`value`),
  CONSTRAINT `attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_values`
--

LOCK TABLES `attribute_values` WRITE;
/*!40000 ALTER TABLE `attribute_values` DISABLE KEYS */;
INSERT INTO `attribute_values` VALUES (1,1,'Noir','#2B2B2B',0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,1,'Blanc','#FFFFFF',1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,1,'Bleu','#215ADA',2,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,1,'Rouge','#E0301E',3,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,2,'64 Go',NULL,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,2,'128 Go',NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,2,'256 Go',NULL,2,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `attribute_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributes`
--

DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attributes_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributes`
--

LOCK TABLES `attributes` WRITE;
/*!40000 ALTER TABLE `attributes` DISABLE KEYS */;
INSERT INTO `attributes` VALUES (1,'Couleur','couleur',0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,'Capacité','capacite',1,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `placement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `highlight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int unsigned DEFAULT NULL,
  `compare_at_price` int unsigned DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_label` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_placement_is_visible_position_index` (`placement`,`is_visible`,`position`),
  KEY `banners_product_id_foreign` (`product_id`),
  CONSTRAINT `banners_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'hero',NULL,'assets/images/product-banner/product-banner-img-17.webp','Offre exclusive en cours','GOPRO','HERO 10',NULL,'-30 %',113500,162000,NULL,NULL,0,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,'hero',NULL,'assets/images/product-banner/product-banner-img-18.webp','Offre du week-end','OSMO MINI','PRO',NULL,'-30 %',149500,213500,NULL,NULL,1,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,'hero',NULL,'assets/images/product-banner/product-banner-img-20.webp','Offre du week-end','AIRPODS','PRO',NULL,'-30 %',108000,154000,NULL,NULL,2,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,'hero',NULL,'assets/images/product-banner/product-banner-img-19.webp','Offre exclusive en cours','REFLEX','NUMÉRIQUE',NULL,'-30 %',108000,154000,NULL,NULL,3,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,'hero',NULL,'assets/images/product-banner/product-banner-img-21.webp','Offre exclusive en cours','IPAD','PRO M1',NULL,'-30 %',108000,154000,NULL,NULL,4,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,'hero',NULL,'assets/images/product-banner/product-banner-img-22.webp','Offre du week-end','MACBOOK','PRO M1',NULL,'-30 %',108000,154000,NULL,NULL,5,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,'categories',NULL,'assets/images/catagory-img/banner-cat-01.webp','Offre du week-end','DJI Ronin','Action','Filmez comme un pro',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(8,'best_deals',NULL,'assets/images/product-banner/product-banner-img-01.webp','Offres chocs','Nouvel appareil','bientôt disponible','Des prix imbattables',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(9,'highlights',NULL,'assets/images/product-banner/product-banner-img-02.webp','Offres chocs','Caméra rouge','Plus','Offre de saison',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(10,'closing',NULL,'assets/images/product-banner/product-banner-img-03.webp','Remise exclusive du week-end','jusqu’à -50 % sur une sélection','Faites-vous plaisir','Des designs incroyablement fins.',NULL,108000,154000,NULL,NULL,0,NULL,NULL,1,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promo_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'AMD','amd','assets/images/brands/brand-a-01.webp','Jusqu’à -20 %',0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(2,'Qualcomm','qualcomm','assets/images/brands/brand-a-02.webp','Jusqu’à -10 %',1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(3,'Sony','sony','assets/images/brands/brand-a-03.webp','Jusqu’à -15 %',2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(4,'Asus','asus','assets/images/brands/brand-a-04.webp','Jusqu’à -25 %',3,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(5,'Huawei','huawei','assets/images/brands/brand-a-05.webp','Jusqu’à -20 %',4,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(6,'Bose','bose','assets/images/brands/brand-a-06.webp','Jusqu’à -15 %',5,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(7,'Samsung','samsung','assets/images/brands/brand-a-07.webp','Jusqu’à -12 %',6,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(8,'Lenovo','lenovo','assets/images/brands/brand-a-08.webp','Jusqu’à -16 %',7,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(9,'Intel','intel','assets/images/brands/brand-a-09.webp','Jusqu’à -10 %',8,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(10,'NVIDIA GeForce','nvidia-geforce','assets/images/brands/brand-a-10.webp','Jusqu’à -14 %',9,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bundle_items`
--

DROP TABLE IF EXISTS `bundle_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bundle_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` smallint unsigned NOT NULL DEFAULT '1',
  `position` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bundle_items_bundle_id_product_variant_id_unique` (`bundle_id`,`product_variant_id`),
  KEY `bundle_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `bundle_items_bundle_id_foreign` FOREIGN KEY (`bundle_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bundle_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bundle_items`
--

LOCK TABLES `bundle_items` WRITE;
/*!40000 ALTER TABLE `bundle_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bundle_items` ENABLE KEYS */;
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
INSERT INTO `cache` VALUES ('kova-market-cache-settings','a:1:{s:32:\"delivery.free_shipping_threshold\";s:6:\"100000\";}',2106230684);
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
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_items_cart_id_product_variant_id_unique` (`cart_id`,`product_variant_id`),
  KEY `cart_items_product_variant_id_foreign` (`product_variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `commune_id` bigint unsigned DEFAULT NULL,
  `coupon_id` bigint unsigned DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_token_unique` (`token`),
  UNIQUE KEY `carts_user_id_unique` (`user_id`),
  KEY `carts_commune_id_foreign` (`commune_id`),
  KEY `carts_expires_at_index` (`expires_at`),
  KEY `carts_coupon_id_foreign` (`coupon_id`),
  CONSTRAINT `carts_commune_id_foreign` FOREIGN KEY (`commune_id`) REFERENCES `communes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `carts_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_variant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo` json DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_position_index` (`parent_id`,`position`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Mon Marché','mon-marche','fa-regular fa-basket-shopping','Le marché livré chez vous',NULL,NULL,NULL,'{\"label\": \"Mon Marché\", \"title\": \"Le marché livré chez vous\", \"button\": \"Découvrir Mon Marché\", \"subtitle\": \"Produits frais, épicerie et boissons\"}',0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(2,1,'Fruits et légumes','fruits-et-legumes','fa-regular fa-carrot',NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(3,2,'Légumes frais','legumes-frais',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(4,2,'Fruits frais','fruits-frais',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(5,2,'Tubercules et plantain','tubercules-et-plantain',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(6,2,'Piments, ail et aromates','piments-ail-et-aromates',NULL,NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(7,1,'Viandes, volailles et poissons','viandes-volailles-et-poissons','fa-regular fa-drumstick-bite',NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(8,7,'Bœuf','boeuf',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(9,7,'Mouton et chèvre','mouton-et-chevre',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(10,7,'Porc','porc',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(11,7,'Volaille','volaille',NULL,NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(12,7,'Poissons frais','poissons-frais',NULL,NULL,NULL,NULL,NULL,NULL,0,4,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(13,7,'Poissons fumés et séchés','poissons-fumes-et-seches',NULL,NULL,NULL,NULL,NULL,NULL,0,5,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(14,7,'Crevettes et fruits de mer','crevettes-et-fruits-de-mer',NULL,NULL,NULL,NULL,NULL,NULL,0,6,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(15,1,'Céréales et légumineuses','cereales-et-legumineuses','fa-regular fa-wheat-awn',NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(16,15,'Riz','riz',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(17,15,'Attiéké et semoules','attieke-et-semoules',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(18,15,'Maïs, mil et sorgho','mais-mil-et-sorgho',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(19,15,'Fonio','fonio',NULL,NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(20,15,'Haricots et niébé','haricots-et-niebe',NULL,NULL,NULL,NULL,NULL,NULL,0,4,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(21,15,'Arachides','arachides',NULL,NULL,NULL,NULL,NULL,NULL,0,5,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(22,1,'Épicerie','epicerie','fa-regular fa-jar',NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(23,22,'Huiles','huiles',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(24,22,'Pâtes et farines','pates-et-farines',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(25,22,'Conserves','conserves',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(26,22,'Sucre et sel','sucre-et-sel',NULL,NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(27,22,'Épices et bouillons','epices-et-bouillons',NULL,NULL,NULL,NULL,NULL,NULL,0,4,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(28,22,'Biscuits et chocolats','biscuits-et-chocolats',NULL,NULL,NULL,NULL,NULL,NULL,0,5,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(29,22,'Céréales du petit-déjeuner','cereales-du-petit-dejeuner',NULL,NULL,NULL,NULL,NULL,NULL,0,6,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(30,1,'Produits frais et laitiers','produits-frais-et-laitiers','fa-regular fa-egg',NULL,NULL,NULL,NULL,NULL,0,4,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(31,30,'Œufs','oeufs',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(32,30,'Lait et yaourts','lait-et-yaourts',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(33,30,'Beurre et fromages','beurre-et-fromages',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(34,1,'Boulangerie et pâtisserie','boulangerie-et-patisserie','fa-regular fa-bread-slice',NULL,NULL,NULL,NULL,NULL,0,5,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(35,34,'Pains','pains',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(36,34,'Viennoiseries','viennoiseries',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(37,34,'Gâteaux et pâtisseries','gateaux-et-patisseries',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(38,1,'Boissons','boissons','fa-regular fa-bottle-water',NULL,NULL,NULL,NULL,NULL,0,6,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(39,38,'Eaux','eaux',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(40,38,'Jus et nectars','jus-et-nectars',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(41,38,'Boissons locales','boissons-locales',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(42,38,'Sirops','sirops',NULL,NULL,NULL,NULL,NULL,NULL,0,3,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(43,38,'Sodas','sodas',NULL,NULL,NULL,NULL,NULL,NULL,0,4,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(44,1,'Surgelés','surgeles','fa-regular fa-snowflake',NULL,NULL,NULL,NULL,NULL,0,7,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(45,44,'Poissons surgelés','poissons-surgeles',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(46,44,'Viandes surgelées','viandes-surgelees',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(47,44,'Légumes surgelés','legumes-surgeles',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:35','2026-10-01 16:04:35',NULL,NULL),(48,NULL,'Photo et caméras','photo-et-cameras','fa-regular fa-camera','Les accessoires photo les plus demandés',NULL,NULL,'assets/images/catagory-img/cat-transp-img-07.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"Accessoires photo\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(49,48,'Caméra d’action','photo-et-cameras-camera-daction',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-7.webp',NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(50,49,'Sports Cameras','photo-et-cameras-camera-daction-sports-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(51,49,'Underwater Cameras','photo-et-cameras-camera-daction-underwater-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(52,49,'360 Cameras','photo-et-cameras-camera-daction-360-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(53,48,'Objectifs','photo-et-cameras-objectifs',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-8.webp',NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(54,53,'VR Cameras','photo-et-cameras-objectifs-vr-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(55,53,'Panoramic Cameras','photo-et-cameras-objectifs-panoramic-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(56,53,'3D Cameras','photo-et-cameras-objectifs-3d-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(57,48,'Appareil photo numérique','photo-et-cameras-appareil-photo-numerique',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-9.webp',NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(58,57,'Drone Cameras','photo-et-cameras-appareil-photo-numerique-drone-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(59,57,'Helmet Cameras','photo-et-cameras-appareil-photo-numerique-helmet-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(60,57,'Dual-Lens Cameras','photo-et-cameras-appareil-photo-numerique-dual-lens-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(61,48,'Reflex','photo-et-cameras-reflex',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-10.webp',NULL,0,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(62,61,'Compact 360 Cameras','photo-et-cameras-reflex-compact-360-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(63,61,'DSLR Cameras','photo-et-cameras-reflex-dslr-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(64,61,'Mirrorless Cameras','photo-et-cameras-reflex-mirrorless-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(65,48,'Caméscope compact','photo-et-cameras-camescope-compact',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-11.webp',NULL,0,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(66,65,'Point-and-Shoot Cameras','photo-et-cameras-camescope-compact-point-and-shoot-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(67,65,'Bridge Cameras','photo-et-cameras-camescope-compact-bridge-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(68,65,'Compact Cameras','photo-et-cameras-camescope-compact-compact-cameras',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(69,48,'Hybride','photo-et-cameras-hybride',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-12.webp',NULL,0,5,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(70,69,'Full-Frame Mirrorless','photo-et-cameras-hybride-full-frame-mirrorless',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(71,69,'APS-C Mirrorless','photo-et-cameras-hybride-aps-c-mirrorless',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(72,69,'Micro Four Thirds Mirrorless','photo-et-cameras-hybride-micro-four-thirds-mirrorless',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(73,48,'Caméra embarquée','photo-et-cameras-camera-embarquee',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-13.webp',NULL,0,6,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(74,73,'Compact Mirrorless','photo-et-cameras-camera-embarquee-compact-mirrorless',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(75,73,'Medium Format Mirrorless','photo-et-cameras-camera-embarquee-medium-format-mirrorless',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(76,73,'Panoramic','photo-et-cameras-camera-embarquee-panoramic',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(77,48,'Caméscope','photo-et-cameras-camescope',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-14.webp',NULL,0,7,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(78,77,'Digital Camcorders','photo-et-cameras-camescope-digital-camcorders',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(79,77,'Professional Camcorders','photo-et-cameras-camescope-professional-camcorders',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(80,77,'4K Camcorders','photo-et-cameras-camescope-4k-camcorders',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(81,48,'Appareil instantané','photo-et-cameras-appareil-instantane',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-15.webp',NULL,0,8,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(82,81,'Compact Camcorders','photo-et-cameras-appareil-instantane-compact-camcorders',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(83,81,'High Definition (HD) Camcorders','photo-et-cameras-appareil-instantane-high-definition-hd-camcorders',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(84,81,'Panoramic','photo-et-cameras-appareil-instantane-panoramic',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(85,48,'Accessoires photo','photo-et-cameras-accessoires-photo',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-16.webp',NULL,0,9,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(86,85,'SD Cards (High-Speed)','photo-et-cameras-accessoires-photo-sd-cards-high-speed',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(87,85,'MicroSD Cards','photo-et-cameras-accessoires-photo-microsd-cards',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(88,85,'External Hard Drives','photo-et-cameras-accessoires-photo-external-hard-drives',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(89,48,'Trépied','photo-et-cameras-trepied',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-17.webp',NULL,0,10,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(90,89,'Travel Tripods','photo-et-cameras-trepied-travel-tripods',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(91,89,'Tabletop Tripods','photo-et-cameras-trepied-tabletop-tripods',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(92,89,'Monopods','photo-et-cameras-trepied-monopods',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(93,NULL,'Montres connectées','montres-connectees','fa-regular fa-watch-apple','Toutes nos montres connectées','EXCLUSIVE','primary','assets/images/catagory-img/cat-transp-img-08.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(94,93,'Bracelet d’activité','montres-connectees-bracelet-dactivite',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-1.webp',NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(95,94,'Smart Bands','montres-connectees-bracelet-dactivite-smart-bands',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(96,94,'Heart Rate Monitors','montres-connectees-bracelet-dactivite-heart-rate-monitors',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(97,94,'Sleep Trackers','montres-connectees-bracelet-dactivite-sleep-trackers',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(98,93,'Bluetooth','montres-connectees-bluetooth',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-2.webp',NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(99,98,'Luxury Bluetooth Watches','montres-connectees-bluetooth-luxury-bluetooth-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(100,98,'Hybrid Smartwatches','montres-connectees-bluetooth-hybrid-smartwatches',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(101,98,'Kids\' Smartwatches','montres-connectees-bluetooth-kids-smartwatches',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(102,93,'Hybride','montres-connectees-hybride',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-3.webp',NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(103,102,'Fitness Hybrid Watches','montres-connectees-hybride-fitness-hybrid-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(104,102,'Smart Hybrid Watches','montres-connectees-hybride-smart-hybrid-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(105,102,'Classic Hybrid Watches','montres-connectees-hybride-classic-hybrid-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(106,93,'Classique','montres-connectees-classique',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-4.webp',NULL,0,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(107,106,'Analog Watches','montres-connectees-classique-analog-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(108,106,'Digital Watches','montres-connectees-classique-digital-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(109,106,'Dress Watches','montres-connectees-classique-dress-watches',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(110,93,'Écran tactile','montres-connectees-ecran-tactile',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-5.webp',NULL,0,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(111,110,'Smartwatches','montres-connectees-ecran-tactile-smartwatches',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(112,110,'Fitness Trackers','montres-connectees-ecran-tactile-fitness-trackers',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(113,110,'Hybrid Smartwatches','montres-connectees-ecran-tactile-hybrid-smartwatches',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(114,NULL,'TV, audio et vidéo','tv-audio-et-video','fa-sharp fa-regular fa-camcorder','TV et audio-vidéo des plus grandes marques',NULL,NULL,'assets/images/catagory-img/cat-transp-img-09.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(115,114,'QLED TV','tv-audio-et-video-qled-tv',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-18.webp',NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(116,114,'Smart TV','tv-audio-et-video-smart-tv',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-19.webp',NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(117,114,'TV UHD','tv-audio-et-video-tv-uhd',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-20.webp',NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(118,114,'TV HD','tv-audio-et-video-tv-hd',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-21.webp',NULL,0,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(119,114,'TV LED','tv-audio-et-video-tv-led',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-22.webp',NULL,0,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(120,114,'TV 4K','tv-audio-et-video-tv-4k',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-23.webp',NULL,0,5,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(121,NULL,'Jeux vidéo','jeux-video','fa-light fa-game-console-handheld','Accessoires de jeu des meilleures marques','TRENDING','green','assets/images/catagory-img/cat-transp-img-12.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(122,121,'Clavier gaming','jeux-video-clavier-gaming',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-24.webp',NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(123,122,'Apex Gamer Pro','jeux-video-clavier-gaming-apex-gamer-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(124,122,'Stealth Strike Keyboard','jeux-video-clavier-gaming-stealth-strike-keyboard',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(125,122,'Rapid Fire RGB','jeux-video-clavier-gaming-rapid-fire-rgb',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(126,121,'Casque gaming','jeux-video-casque-gaming',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-25.webp',NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(127,126,'SoundStorm Pro','jeux-video-casque-gaming-soundstorm-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(128,126,'EchoMaster Elite','jeux-video-casque-gaming-echomaster-elite',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(129,126,'BattleTune 360','jeux-video-casque-gaming-battletune-360',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(130,121,'Fauteuil gaming','jeux-video-fauteuil-gaming',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-26.webp',NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(131,130,'Elite Gamer Throne','jeux-video-fauteuil-gaming-elite-gamer-throne',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(132,130,'Turbo Comfort Seat','jeux-video-fauteuil-gaming-turbo-comfort-seat',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(133,130,'Pro Series Gaming Chair','jeux-video-fauteuil-gaming-pro-series-gaming-chair',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(134,121,'Tapis de souris','jeux-video-tapis-de-souris',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-27.webp',NULL,0,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(135,134,'GlidePro Mouse Pad','jeux-video-tapis-de-souris-glidepro-mouse-pad',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(136,134,'PixelPerfect Pad','jeux-video-tapis-de-souris-pixelperfect-pad',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(137,134,'EagleEye Mouse Mat','jeux-video-tapis-de-souris-eagleeye-mouse-mat',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(138,121,'Manette','jeux-video-manette',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-28.webp',NULL,0,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(139,138,'ProGamer Joystick','jeux-video-manette-progamer-joystick',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(140,138,'Precision Play Controller','jeux-video-manette-precision-play-controller',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(141,138,'TurboGrip Joystick','jeux-video-manette-turbogrip-joystick',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(142,121,'Casque VR','jeux-video-casque-vr',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-29.webp',NULL,0,5,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(143,142,'VisionSphere VR Headset','jeux-video-casque-vr-visionsphere-vr-headset',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(144,142,'ImmersiveEye VR Goggles','jeux-video-casque-vr-immersiveeye-vr-goggles',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(145,142,'RealityFusion Headset','jeux-video-casque-vr-realityfusion-headset',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(146,121,'Accessoires PlayStation','jeux-video-accessoires-playstation',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-30.webp',NULL,0,6,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(147,146,'Crystal Clear Faceplate','jeux-video-accessoires-playstation-crystal-clear-faceplate',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(148,146,'ComfortFit Chair','jeux-video-accessoires-playstation-comfortfit-chair',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(149,146,'Dynamic RGB LED','jeux-video-accessoires-playstation-dynamic-rgb-led',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(150,121,'Bureau gaming','jeux-video-bureau-gaming',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-31.webp',NULL,0,7,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(151,150,'ProGamer Desk','jeux-video-bureau-gaming-progamer-desk',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(152,150,'Titan Gaming Station','jeux-video-bureau-gaming-titan-gaming-station',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(153,150,'Arcade Pro Desk','jeux-video-bureau-gaming-arcade-pro-desk',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(154,121,'Canapé gaming','jeux-video-canape-gaming',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-32.webp',NULL,0,8,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(155,154,'Victory Lounge','jeux-video-canape-gaming-victory-lounge',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(156,154,'Pixel Perch','jeux-video-canape-gaming-pixel-perch',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(157,154,'Gamer\'s Retreat','jeux-video-canape-gaming-gamers-retreat',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(158,NULL,'Casques et musique','casques-et-musique','fa-sharp fa-regular fa-headphones','Les meilleurs casques et produits audio',NULL,NULL,'assets/images/catagory-img/cat-transp-img-10.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(159,158,'Casque Bluetooth','casques-et-musique-casque-bluetooth',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-33.webp',NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(160,159,'SoundWave Pro','casques-et-musique-casque-bluetooth-soundwave-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(161,159,'AeroSound Bluetooth','casques-et-musique-casque-bluetooth-aerosound-bluetooth',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(162,159,'PulseBeats Wireless','casques-et-musique-casque-bluetooth-pulsebeats-wireless',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(163,158,'Support de casque','casques-et-musique-support-de-casque',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-34.webp',NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(164,163,'Audio Aegis','casques-et-musique-support-de-casque-audio-aegis',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(165,163,'Harmonic Holder','casques-et-musique-support-de-casque-harmonic-holder',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(166,163,'Headset Haven','casques-et-musique-support-de-casque-headset-haven',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(167,158,'Home cinéma','casques-et-musique-home-cinema',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-35.webp',NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(168,167,'Cinematic Sound Bar','casques-et-musique-home-cinema-cinematic-sound-bar',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(169,167,'Ultra HD Projector','casques-et-musique-home-cinema-ultra-hd-projector',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(170,167,'4K Smart TV','casques-et-musique-home-cinema-4k-smart-tv',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(171,158,'Enceinte Bluetooth','casques-et-musique-enceinte-bluetooth',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-36.webp',NULL,0,3,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(172,171,'SoundWave Pro','casques-et-musique-enceinte-bluetooth-soundwave-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(173,171,'BassBlaster 360','casques-et-musique-enceinte-bluetooth-bassblaster-360',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(174,171,'AeroSound Compact','casques-et-musique-enceinte-bluetooth-aerosound-compact',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(175,158,'Barre de son','casques-et-musique-barre-de-son',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-37.webp',NULL,0,4,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(176,175,'Versatile Soundbar','casques-et-musique-barre-de-son-versatile-soundbar',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(177,175,'Signature Series Soundbar','casques-et-musique-barre-de-son-signature-series-soundbar',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(178,175,'ProSound Soundbar','casques-et-musique-barre-de-son-prosound-soundbar',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(179,158,'Microphone','casques-et-musique-microphone',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-38.webp',NULL,0,5,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(180,179,'SoundWave Pro','casques-et-musique-microphone-soundwave-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(181,179,'EchoSphere Mic','casques-et-musique-microphone-echosphere-mic',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(182,179,'ClearCast 3000','casques-et-musique-microphone-clearcast-3000',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(183,158,'Dictaphone','casques-et-musique-dictaphone',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-39.webp',NULL,0,6,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(184,183,'EchoNote Pro','casques-et-musique-dictaphone-echonote-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(185,183,'VoxCapture 3000','casques-et-musique-dictaphone-voxcapture-3000',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(186,183,'SoundScribe','casques-et-musique-dictaphone-soundscribe',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(187,158,'Carte son','casques-et-musique-carte-son',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-40.webp',NULL,0,7,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(188,187,'AeroSound Pro','casques-et-musique-carte-son-aerosound-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(189,187,'EchoMaster FX','casques-et-musique-carte-son-echomaster-fx',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(190,187,'Vortex SoundBlaster','casques-et-musique-carte-son-vortex-soundblaster',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(191,NULL,'Électroménager','electromenager','fa-sharp fa-regular fa-blender-phone','Tout l’électroménager de la maison','HOT','danger','assets/images/catagory-img/cat-transp-img-11.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',1,5,'2026-10-01 16:04:37','2026-10-01 16:04:37',NULL,NULL),(192,191,'Climatiseur','electromenager-climatiseur',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-41.webp',NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(193,192,'CoolBreeze Pro','electromenager-climatiseur-coolbreeze-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(194,192,'ChillMaster Elite','electromenager-climatiseur-chillmaster-elite',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(195,192,'AirFlow Genius','electromenager-climatiseur-airflow-genius',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(196,191,'Chauffe-eau','electromenager-chauffe-eau',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-42.webp',NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(197,196,'AquaFlow Geysers','electromenager-chauffe-eau-aquaflow-geysers',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(198,196,'TurboHeat Geysers','electromenager-chauffe-eau-turboheat-geysers',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(199,196,'EcoHeat Geysers','electromenager-chauffe-eau-ecoheat-geysers',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(200,191,'Four','electromenager-four',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-43.webp',NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(201,200,'CrispBake Oven','electromenager-four-crispbake-oven',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(202,200,'QuickHeat Convection Oven','electromenager-four-quickheat-convection-oven',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(203,200,'PerfectBake Electric Oven','electromenager-four-perfectbake-electric-oven',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(204,191,'Friteuse sans huile','electromenager-friteuse-sans-huile',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-44.webp',NULL,0,3,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(205,204,'CrispMaster Air Fryer','electromenager-friteuse-sans-huile-crispmaster-air-fryer',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(206,204,'Healthy Fry Pro','electromenager-friteuse-sans-huile-healthy-fry-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(207,204,'QuickCrisp Air Fryer','electromenager-friteuse-sans-huile-quickcrisp-air-fryer',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(208,191,'Lave-linge','electromenager-lave-linge',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-45.webp',NULL,0,4,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(209,208,'EcoClean Pro','electromenager-lave-linge-ecoclean-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(210,208,'UltraWash 360','electromenager-lave-linge-ultrawash-360',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(211,208,'QuickSpin Deluxe','electromenager-lave-linge-quickspin-deluxe',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(212,191,'Machine à coudre','electromenager-machine-a-coudre',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-46.webp',NULL,0,5,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(213,212,'StitchPro 300','electromenager-machine-a-coudre-stitchpro-300',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(214,212,'SewMaster Deluxe','electromenager-machine-a-coudre-sewmaster-deluxe',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(215,212,'QuiltCraft Elite','electromenager-machine-a-coudre-quiltcraft-elite',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(216,191,'Purificateur d’air','electromenager-purificateur-dair',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-47.webp',NULL,0,6,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(217,216,'PureAir Breeze','electromenager-purificateur-dair-pureair-breeze',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(218,216,'FreshFlow Purifier','electromenager-purificateur-dair-freshflow-purifier',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(219,216,'BreatheEasy Pro','electromenager-purificateur-dair-breatheeasy-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(220,191,'Aspirateur','electromenager-aspirateur',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-48.webp',NULL,0,7,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(221,220,'PowerSweep Pro','electromenager-aspirateur-powersweep-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(222,220,'UltraClean Cyclone','electromenager-aspirateur-ultraclean-cyclone',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(223,220,'DustBuster Max','electromenager-aspirateur-dustbuster-max',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(224,191,'Mixeur','electromenager-mixeur',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-49.webp',NULL,0,8,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(225,224,'Smoothie Master Pro','electromenager-mixeur-smoothie-master-pro',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(226,224,'NutriBlend Ultra','electromenager-mixeur-nutriblend-ultra',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(227,224,'EcoBlend Portable Blender','electromenager-mixeur-ecoblend-portable-blender',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(228,191,'Cuisinière','electromenager-cuisiniere',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-50.webp',NULL,0,9,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(229,228,'PowerMix 3000','electromenager-cuisiniere-powermix-3000',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(230,228,'Frozen Fusion Blender','electromenager-cuisiniere-frozen-fusion-blender',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(231,228,'UltraSmooth Blender','electromenager-cuisiniere-ultrasmooth-blender',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(232,191,'Fer à repasser','electromenager-fer-a-repasser',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-51.webp',NULL,0,10,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(233,232,'Blender & Chop Duo','electromenager-fer-a-repasser-blender-chop-duo',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(234,232,'TurboMix Professional','electromenager-fer-a-repasser-turbomix-professional',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(235,232,'BlendSmart 2-in-1','electromenager-fer-a-repasser-blendsmart-2-in-1',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(236,191,'Chauffage d’appoint','electromenager-chauffage-dappoint',NULL,NULL,NULL,NULL,'assets/images/product-img/sidebar-category/category-product-52.webp',NULL,0,11,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(237,236,'HeatWave Blanket','electromenager-chauffage-dappoint-heatwave-blanket',NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(238,236,'ThermoCushion','electromenager-chauffage-dappoint-thermocushion',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(239,236,'SootheHeat Massager','electromenager-chauffage-dappoint-sootheheat-massager',NULL,NULL,NULL,NULL,NULL,NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(240,NULL,'Informatique et mobiles','informatique-et-mobiles','fa-regular fa-laptop','Ordinateurs portables, tablettes, téléphones et accessoires',NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp','{\"image\": \"assets/images/product-img/sidebar-category/product-banner.webp\", \"label\": \"À partir de\", \"title\": \"Jusqu’à -40 %\", \"subtitle\": \"Sur toutes les marques\", \"highlight\": \"11 décembre\"}',0,6,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(241,240,'Ordinateurs portables','informatique-et-mobiles-ordinateurs-portables',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp',NULL,0,0,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(242,240,'Smartphones','informatique-et-mobiles-smartphones',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-11-a-1.webp',NULL,0,1,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(243,240,'Tablettes','informatique-et-mobiles-tablettes',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp',NULL,0,2,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(244,240,'Accessoires informatiques','informatique-et-mobiles-accessoires-informatiques',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-05-a-1.webp',NULL,0,3,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(245,240,'Accessoires mobiles','informatique-et-mobiles-accessoires-mobiles',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-13-a-1.webp',NULL,0,4,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL),(246,240,'Webcams et streaming','informatique-et-mobiles-webcams-et-streaming',NULL,NULL,NULL,NULL,'assets/images/product-img/electronics/electronics-bg-trans-06-a-1.webp',NULL,0,5,'2026-10-01 16:04:38','2026-10-01 16:04:38',NULL,NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_coupon`
--

DROP TABLE IF EXISTS `category_coupon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_coupon` (
  `coupon_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`category_id`),
  KEY `category_coupon_category_id_foreign` (`category_id`),
  CONSTRAINT `category_coupon_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_coupon_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_coupon`
--

LOCK TABLES `category_coupon` WRITE;
/*!40000 ALTER TABLE `category_coupon` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_coupon` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_product`
--

DROP TABLE IF EXISTS `collection_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_product` (
  `collection_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`collection_id`,`product_id`),
  KEY `collection_product_product_id_foreign` (`product_id`),
  CONSTRAINT `collection_product_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collection_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_product`
--

LOCK TABLES `collection_product` WRITE;
/*!40000 ALTER TABLE `collection_product` DISABLE KEYS */;
INSERT INTO `collection_product` VALUES (3,1,0),(3,2,1),(3,3,2),(3,4,3),(3,5,4),(3,6,5),(3,7,6),(3,8,7),(4,9,0),(4,10,1),(4,11,2),(4,12,3),(5,13,0),(5,14,1),(5,15,2),(5,16,3),(5,17,4),(5,18,5),(6,19,0),(6,20,1),(6,21,2),(7,9,0),(7,10,1),(7,11,2),(7,12,3);
/*!40000 ALTER TABLE `collection_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collections_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collections`
--

LOCK TABLES `collections` WRITE;
/*!40000 ALTER TABLE `collections` DISABLE KEYS */;
INSERT INTO `collections` VALUES (1,'Nouveautés','new-arrivals',NULL,NULL,'2026-10-01 16:04:34','2026-10-01 16:04:34'),(2,'Populaires','popular-products',NULL,NULL,'2026-10-01 16:04:34','2026-10-01 16:04:34'),(3,'Offres du jour','deals-of-the-day',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,'Les meilleures offres du jour','todays-best-deals',NULL,'2026-12-27 16:04:38','2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,'Les incontournables de la semaine','weekly-highlights',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,'Produits vedettes','featured-products',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,'Produits tendance','trending-searches',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communes`
--

DROP TABLE IF EXISTS `communes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_zone_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communes_name_unique` (`name`),
  KEY `communes_delivery_zone_id_foreign` (`delivery_zone_id`),
  CONSTRAINT `communes_delivery_zone_id_foreign` FOREIGN KEY (`delivery_zone_id`) REFERENCES `delivery_zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communes`
--

LOCK TABLES `communes` WRITE;
/*!40000 ALTER TABLE `communes` DISABLE KEYS */;
INSERT INTO `communes` VALUES (1,1,'Cocody',0,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(2,1,'Plateau',1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(3,1,'Marcory',2,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(4,1,'Treichville',3,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(5,1,'Adjamé',4,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(6,2,'Yopougon',0,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(7,2,'Abobo',1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(8,2,'Koumassi',2,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(9,2,'Port-Bouët',3,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(10,2,'Attécoubé',4,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(11,3,'Bingerville',0,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(12,3,'Anyama',1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(13,3,'Songon',2,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(14,3,'Grand-Bassam',3,'2026-10-01 16:04:36','2026-10-01 16:04:36');
/*!40000 ALTER TABLE `communes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `handled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_messages_user_id_foreign` (`user_id`),
  KEY `contact_messages_handled_by_foreign` (`handled_by`),
  KEY `contact_messages_handled_at_index` (`handled_at`),
  CONSTRAINT `contact_messages_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contact_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_product`
--

DROP TABLE IF EXISTS `coupon_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_product` (
  `coupon_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`product_id`),
  KEY `coupon_product_product_id_foreign` (`product_id`),
  CONSTRAINT `coupon_product_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_product`
--

LOCK TABLES `coupon_product` WRITE;
/*!40000 ALTER TABLE `coupon_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usages`
--

DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_usages_order_id_unique` (`order_id`),
  KEY `coupon_usages_user_id_foreign` (`user_id`),
  KEY `coupon_usages_coupon_id_phone_index` (`coupon_id`,`phone`),
  CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `coupon_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usages`
--

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int unsigned NOT NULL DEFAULT '0',
  `minimum_subtotal` int unsigned DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `usage_limit` int unsigned DEFAULT NULL,
  `usage_limit_per_customer` int unsigned DEFAULT NULL,
  `times_used` int unsigned NOT NULL DEFAULT '0',
  `target` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tout',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'BIENVENUE10','10 % sur votre première commande','pourcentage',10,NULL,NULL,NULL,NULL,1,0,'tout',1,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,'LIVRAISON','Livraison offerte dès 20 000 FCFA','livraison_offerte',0,20000,NULL,NULL,NULL,NULL,0,'tout',1,1,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,'MOINS5000','5 000 FCFA de remise dès 50 000 FCFA','montant',5000,50000,NULL,NULL,100,NULL,0,'tout',1,1,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courier_delivery_zone`
--

DROP TABLE IF EXISTS `courier_delivery_zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courier_delivery_zone` (
  `courier_id` bigint unsigned NOT NULL,
  `delivery_zone_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`courier_id`,`delivery_zone_id`),
  KEY `courier_delivery_zone_delivery_zone_id_foreign` (`delivery_zone_id`),
  CONSTRAINT `courier_delivery_zone_courier_id_foreign` FOREIGN KEY (`courier_id`) REFERENCES `couriers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courier_delivery_zone_delivery_zone_id_foreign` FOREIGN KEY (`delivery_zone_id`) REFERENCES `delivery_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courier_delivery_zone`
--

LOCK TABLES `courier_delivery_zone` WRITE;
/*!40000 ALTER TABLE `courier_delivery_zone` DISABLE KEYS */;
/*!40000 ALTER TABLE `courier_delivery_zone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `couriers`
--

DROP TABLE IF EXISTS `couriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `couriers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `transport` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `couriers_user_id_unique` (`user_id`),
  CONSTRAINT `couriers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `couriers`
--

LOCK TABLES `couriers` WRITE;
/*!40000 ALTER TABLE `couriers` DISABLE KEYS */;
/*!40000 ALTER TABLE `couriers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!50001 DROP VIEW IF EXISTS `customers`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `customers` AS SELECT 
 1 AS `id`,
 1 AS `user_id`,
 1 AS `name`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `created_at`,
 1 AS `orders_count`,
 1 AS `total_spent`,
 1 AS `last_order_at`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `delivery_zones`
--

DROP TABLE IF EXISTS `delivery_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee` int unsigned DEFAULT NULL,
  `delay_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_zones`
--

LOCK TABLES `delivery_zones` WRITE;
/*!40000 ALTER TABLE `delivery_zones` DISABLE KEYS */;
INSERT INTO `delivery_zones` VALUES (1,'Zone 1',1500,'J+1',1,0,'2026-10-01 16:04:36','2026-10-01 16:04:38'),(2,'Zone 2',2000,'J+1 à J+2',1,1,'2026-10-01 16:04:36','2026-10-01 16:04:38'),(3,'Zone 3',3000,'J+2',1,2,'2026-10-01 16:04:36','2026-10-01 16:04:38'),(4,'Intérieur du pays',5000,'J+2 à J+5',1,3,'2026-10-01 16:04:36','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `delivery_zones` ENABLE KEYS */;
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
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faqs_is_published_topic_position_index` (`is_published`,`topic`,`position`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'Commande','Dois-je créer un compte pour commander ?','Non. Vous pouvez commander en tant qu’invité avec votre nom, votre numéro de téléphone et votre adresse de livraison. Un compte vous permet en plus d’enregistrer vos adresses et de retrouver vos commandes.',0,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(2,'Commande','Comment suivre ma commande ?','Avec votre numéro de commande (par exemple KM-260927-0042) et votre numéro de téléphone, depuis la page « Suivre ma commande ». Vous recevez aussi un SMS à chaque étape.',1,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(3,'Paiement','Quels moyens de paiement acceptez-vous ?','Orange Money, MTN MoMo, Moov Money et Wave, ainsi que le paiement à la livraison.',2,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(4,'Livraison','Combien coûte la livraison ?','Les frais dépendent de votre commune de livraison. Ils sont calculés automatiquement dans le panier dès que vous choisissez votre commune.',3,1,'2026-10-01 16:04:36','2026-10-01 16:04:36');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
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
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_25_093955_create_categories_table',1),(5,'2026_09_25_093956_create_brands_table',1),(6,'2026_09_25_093957_create_products_table',1),(7,'2026_09_25_093958_create_collections_table',1),(8,'2026_09_25_093959_create_collection_product_table',1),(9,'2026_09_25_094000_create_promotions_table',1),(10,'2026_09_27_154511_create_personal_access_tokens_table',1),(11,'2026_09_27_154512_add_two_factor_columns_to_users_table',1),(12,'2026_09_27_154656_create_permission_tables',1),(13,'2026_09_27_154657_create_activity_log_table',1),(14,'2026_09_27_154752_create_media_table',1),(15,'2026_09_27_170000_add_app_authentication_columns_to_users_table',1),(16,'2026_09_27_180000_create_banners_table',1),(17,'2026_09_27_180100_create_pages_table',1),(18,'2026_09_27_180200_create_faqs_table',1),(19,'2026_09_27_180300_create_settings_table',1),(20,'2026_09_27_190000_convert_product_prices_to_whole_fcfa',1),(21,'2026_09_27_200000_add_phone_to_users_table',1),(22,'2026_09_27_212415_create_notifications_table',1),(23,'2026_09_28_100000_create_attributes_tables',1),(24,'2026_09_28_100100_create_product_variants_table',1),(25,'2026_09_28_100200_create_stock_movements_table',1),(26,'2026_09_28_100300_create_default_variants_for_existing_products',1),(27,'2026_09_28_110000_add_description_and_seo_to_catalog_tables',1),(28,'2026_09_28_120000_create_delivery_zones_and_communes_tables',1),(29,'2026_09_28_120100_create_carts_tables',1),(30,'2026_09_28_130000_create_orders_tables',1),(31,'2026_09_28_140000_create_addresses_table',1),(32,'2026_09_28_150000_create_coupons_tables',1),(33,'2026_09_28_160000_create_stock_alerts_table',1),(34,'2026_09_28_170000_add_sale_window_to_product_variants',1),(35,'2026_09_28_180000_create_customers_view',1),(36,'2026_09_28_190000_create_contact_messages_table',1),(37,'2026_09_28_200000_create_bundle_items_table',1),(38,'2026_09_28_210000_create_couriers_tables',1),(39,'2026_09_28_231556_add_soft_deletes_to_users_table',1),(40,'2026_09_28_233714_create_slug_redirects_table',1),(41,'2026_09_29_002714_add_new_arrivals_and_popular_collections',1),(42,'2026_09_29_080804_add_url_to_promotions_table',1),(43,'2026_09_29_094720_create_payments_table',1),(44,'2026_09_29_160000_create_testimonials_table',1),(45,'2026_09_29_170000_create_product_reviews_table',1),(46,'2026_09_29_180000_create_product_viewers_table',1),(47,'2026_09_29_190000_add_visibility_and_position_to_promotions_table',1),(48,'2026_09_29_200000_add_home_page_settings_columns',1),(49,'2026_09_29_210000_add_product_id_to_banners_table',1),(50,'2026_09_29_220000_create_newsletter_subscribers_table',1),(51,'2026_09_29_230000_create_wishlist_items_table',1),(52,'2026_09_30_120000_create_mon_marche_categories',1),(53,'2026_09_30_130000_add_sale_units',1),(54,'2026_09_30_150000_add_weigh_in_to_order_items',1);
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
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribed_at` timestamp NOT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  UNIQUE KEY `newsletter_subscribers_token_unique` (`token`),
  KEY `newsletter_subscribers_unsubscribed_at_index` (`unsubscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bundle_contents` json DEFAULT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `ordered_quantity` int unsigned DEFAULT NULL,
  `sale_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'piece',
  `unit_label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weighed_at` timestamp NULL DEFAULT NULL,
  `line_total` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_variant_id_foreign` (`product_variant_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_number_sequences`
--

DROP TABLE IF EXISTS `order_number_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_number_sequences` (
  `day` date NOT NULL,
  `last_value` int unsigned NOT NULL,
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_number_sequences`
--

LOCK TABLES `order_number_sequences` WRITE;
/*!40000 ALTER TABLE `order_number_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_number_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_histories`
--

DROP TABLE IF EXISTS `order_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `from_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_status_histories_order_id_foreign` (`order_id`),
  KEY `order_status_histories_user_id_foreign` (`user_id`),
  CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_status_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_histories`
--

LOCK TABLES `order_status_histories` WRITE;
/*!40000 ALTER TABLE `order_status_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `courier_id` bigint unsigned DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commune_id` bigint unsigned DEFAULT NULL,
  `commune_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `landmark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `subtotal` int unsigned NOT NULL,
  `shipping_fee` int unsigned NOT NULL,
  `discount` int unsigned NOT NULL DEFAULT '0',
  `coupon_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` int unsigned NOT NULL,
  `cash_collected` int unsigned DEFAULT NULL,
  `cash_settled_at` datetime DEFAULT NULL,
  `cash_settled_by` bigint unsigned DEFAULT NULL,
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT '0',
  `terms_accepted_at` datetime NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_number_unique` (`number`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_commune_id_foreign` (`commune_id`),
  KEY `orders_created_at_index` (`created_at`),
  KEY `orders_status_index` (`status`),
  KEY `orders_payment_status_index` (`payment_status`),
  KEY `orders_phone_index` (`phone`),
  KEY `orders_courier_id_foreign` (`courier_id`),
  KEY `orders_cash_settled_by_foreign` (`cash_settled_by`),
  CONSTRAINT `orders_cash_settled_by_foreign` FOREIGN KEY (`cash_settled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_commune_id_foreign` FOREIGN KEY (`commune_id`) REFERENCES `communes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_courier_id_foreign` FOREIGN KEY (`courier_id`) REFERENCES `couriers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Conditions générales de vente','conditions-generales-de-vente','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(2,'Politique de confidentialité','politique-de-confidentialite','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(3,'Livraison','livraison','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(4,'Retours et remboursements','retours-et-remboursements','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(5,'Moyens de paiement','moyens-de-paiement','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36'),(6,'Qui sommes-nous ?','qui-sommes-nous','<p>Cette page est en cours de rédaction. Pour toute question, contactez notre service client.</p>',NULL,NULL,1,'2026-10-01 16:04:36','2026-10-01 16:04:36');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
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
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cinetpay',
  `merchant_transaction_id` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notify_token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int unsigned NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `events` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refunded_by` bigint unsigned DEFAULT NULL,
  `refund_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_merchant_transaction_id_unique` (`merchant_transaction_id`),
  KEY `payments_order_id_foreign` (`order_id`),
  KEY `payments_refunded_by_foreign` (`refunded_by`),
  KEY `payments_gateway_transaction_id_index` (`gateway_transaction_id`),
  KEY `payments_status_index` (`status`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `payments_refunded_by_foreign` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'catalogue.consulter','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(2,'catalogue.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(3,'promotions.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(4,'contenus.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(5,'livraison.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(6,'commandes.consulter','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(7,'commandes.preparer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(8,'commandes.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(9,'parametres.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(10,'utilisateurs.gerer','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(11,'journal.consulter','web','2026-10-01 16:04:36','2026-10-01 16:04:36');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `order_item_id` bigint unsigned DEFAULT NULL,
  `author_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `moderated_by` bigint unsigned DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_reviews_order_item_id_unique` (`order_item_id`),
  KEY `product_reviews_user_id_foreign` (`user_id`),
  KEY `product_reviews_moderated_by_foreign` (`moderated_by`),
  KEY `product_reviews_product_id_status_index` (`product_id`,`status`),
  KEY `product_reviews_status_index` (`status`),
  CONSTRAINT `product_reviews_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_reviews_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int unsigned NOT NULL,
  `compare_at_price` int unsigned DEFAULT NULL,
  `sale_starts_at` datetime DEFAULT NULL,
  `sale_ends_at` datetime DEFAULT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  `low_stock_threshold` smallint unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_position_index` (`product_id`,`position`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,'KM-000001',30000,NULL,NULL,NULL,9,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,2,'KM-000002',108000,177000,NULL,NULL,12,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,3,'KM-000003',60000,NULL,NULL,NULL,4,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,4,'KM-000004',108000,177000,NULL,'2026-12-27 16:04:38',2,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,5,'KM-000005',108000,177000,NULL,NULL,0,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,6,'KM-000006',108000,177000,NULL,NULL,5,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,7,'KM-000007',108000,177000,NULL,NULL,16,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(8,8,'KM-000008',108000,177000,NULL,NULL,9,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(9,9,'KM-000009',108000,177000,NULL,NULL,97,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(10,10,'KM-000010',108000,177000,NULL,NULL,97,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(11,11,'KM-000011',108000,177000,NULL,NULL,97,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(12,12,'KM-000012',108000,177000,NULL,NULL,97,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(13,13,'KM-000013',42000,153000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(14,14,'KM-000014',15500,33500,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(15,15,'KM-000015',42000,70000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(16,16,'KM-000016',36000,58000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(17,17,'KM-000017',42000,70000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(18,18,'KM-000018',60000,131500,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(19,19,'KM-000019',108000,177000,NULL,'2026-12-27 16:04:38',97,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(20,20,'KM-000020',108000,177000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(21,21,'KM-000021',108000,177000,NULL,NULL,25,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_viewers`
--

DROP TABLE IF EXISTS `product_viewers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_viewers` (
  `product_id` bigint unsigned NOT NULL,
  `visitor` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seen_at` timestamp NOT NULL,
  PRIMARY KEY (`product_id`,`visitor`),
  KEY `product_viewers_seen_at_index` (`seen_at`),
  CONSTRAINT `product_viewers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_viewers`
--

LOCK TABLES `product_viewers` WRITE;
/*!40000 ALTER TABLE `product_viewers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_viewers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int unsigned NOT NULL DEFAULT '0',
  `price_max` int unsigned DEFAULT NULL,
  `compare_at_price` int unsigned DEFAULT NULL,
  `sale_starts_at` datetime DEFAULT NULL,
  `price_legacy` decimal(10,2) DEFAULT NULL,
  `price_max_legacy` decimal(10,2) DEFAULT NULL,
  `compare_at_price_legacy` decimal(10,2) DEFAULT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  `sale_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'piece',
  `unit_label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_quantity` int unsigned DEFAULT NULL,
  `quantity_step` int unsigned DEFAULT NULL,
  `max_quantity` int unsigned DEFAULT NULL,
  `sold_count` int unsigned NOT NULL DEFAULT '0',
  `rating` decimal(2,1) NOT NULL DEFAULT '0.0',
  `reviews_count` int unsigned NOT NULL DEFAULT '0',
  `watchers_count` int unsigned DEFAULT NULL,
  `free_shipping` tinyint(1) NOT NULL DEFAULT '0',
  `return_days` smallint unsigned DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hover_video` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badges` json DEFAULT NULL,
  `colors` json DEFAULT NULL,
  `variants_count` int unsigned NOT NULL DEFAULT '0',
  `specifications` json DEFAULT NULL,
  `sale_ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_bundle` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,241,8,'Ultra-Thin Modern Tech Quiet Noise Cancelling Laptop','ultra-thin-modern-tech-quiet-noise-cancelling-laptop',NULL,NULL,NULL,30000,108000,NULL,NULL,NULL,NULL,NULL,9,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,1,7,'assets/images/product-img/electronics/electronics-bg-trans-10-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-10-a-1-hover.webp',NULL,'[{\"label\": \"NOUVEAU\", \"variant\": \"green\"}, {\"label\": \"Meilleure vente\", \"variant\": \"secondary-gradient\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,49,3,'Keurig Polaroid 4K Waterproof Smart Action Camera','keurig-polaroid-4k-waterproof-smart-action-camera',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,12,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,1,NULL,'assets/images/product-img/electronics/electronics-bg-trans-12-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-12-a-1-hover.webp',NULL,'[{\"label\": \"Promo\", \"variant\": \"secondary\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,94,5,'Cubitt Smart Watch CTS Waterproof Fitness Tracker Watch PRO','cubitt-smart-watch-cts-waterproof-fitness-tracker-watch-pro',NULL,NULL,NULL,60000,NULL,NULL,NULL,NULL,NULL,NULL,4,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,1,NULL,'assets/images/product-img/electronics/electronics-bg-trans-03-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-03-a-1-hover.webp',NULL,NULL,NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,242,2,'Apple IPhone 16 PRO max 6200U with 12GB RAM Phone','apple-iphone-16-pro-max-6200u-with-12gb-ram-phone',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,2,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,1,7,'assets/images/product-img/electronics/electronics-bg-trans-11-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-11-a-1-hover.webp',NULL,'[{\"label\": \"Promo\", \"variant\": \"secondary\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]','2026-12-27 16:04:38',1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,244,NULL,'Logitech Precision 9 Button Ergonomic Diital Wireless Mouse','logitech-precision-9-button-ergonomic-diital-wireless-mouse',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,0,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-05-a-2.webp','assets/images/product-img/electronics/electronics-bg-trans-05-a-1-hover.webp',NULL,NULL,NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,77,3,'Keurig K-Duo 4K Waterproof Action Video Camera','keurig-k-duo-4k-waterproof-action-video-camera',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,5,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-08-a-1-hover.webp',NULL,'[{\"label\": \"Nouveau\", \"variant\": \"green\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,245,5,'Cubitt Smart Wireless Apple 16 PRO Charging Case Set','cubitt-smart-wireless-apple-16-pro-charging-case-set',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,16,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,1,7,'assets/images/product-img/electronics/electronics-bg-trans-13-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-13-a-1-hover.webp',NULL,'[{\"label\": \"Top\", \"variant\": \"danger\"}, {\"label\": \"Tendance\", \"variant\": \"yellow\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(8,246,4,'Full Amoled HD Streaming Webcam with Mic Pink webcam','full-amoled-hd-streaming-webcam-with-mic-pink-webcam',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,9,'piece',NULL,NULL,NULL,NULL,95,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-06-a-3.webp','assets/images/product-img/electronics/electronics-bg-trans-06-a-1-hover.webp',NULL,'[{\"label\": \"Tendance\", \"variant\": \"yellow\"}]',NULL,0,'[{\"label\": \"Marque\", \"value\": \"Sony Corporation Ltd\"}, {\"label\": \"Résolution\", \"value\": \"3840×2160\"}, {\"label\": \"Année de sortie\", \"value\": \"Jan 2022\"}, {\"label\": \"Carte mère\", \"value\": \"Samsung\\nATX, ITX, microATX, Mini-ITX\"}]',NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(9,159,7,'Samsung Quiet Comfort Noise Cancelling Earbuds - Black','samsung-quiet-comfort-noise-cancelling-earbuds-black',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,97,'piece',NULL,NULL,NULL,NULL,97,0.0,0,NULL,1,7,'assets/images/product-img/electronics/electronics-bg-trans-01-a-1.webp',NULL,'assets/videos/vedio-review-1.mp4','[{\"label\": \"Top\", \"variant\": \"danger\"}, {\"label\": \"Meilleure vente\", \"variant\": \"secondary-gradient\"}]','[{\"hex\": \"#2B2B2B\", \"name\": \"Noir\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-01-a-1.webp\"}, {\"hex\": \"#a09fa4\", \"name\": \"Rouge\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-01-a-2.webp\"}, {\"hex\": \"#cc999d\", \"name\": \"Rose\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-01-a-3.webp\"}]',15,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(10,159,6,'Keurig K-Duo Bose Noise Cancelling Headphones 700','keurig-k-duo-bose-noise-cancelling-headphones-700',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,97,'piece',NULL,NULL,NULL,NULL,97,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-04-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-04-a-1-hover.webp',NULL,'[{\"label\": \"Meilleure vente\", \"variant\": \"secondary-gradient\"}]','[{\"hex\": \"#bdb6d6\", \"name\": \"Violet\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-04-a-1.webp\"}, {\"hex\": \"#486788\", \"name\": \"Bleu\", \"image\": null}, {\"hex\": \"#1a1a1a\", \"name\": \"Noir\", \"image\": null}]',15,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(11,49,NULL,'GoPro HERO 11 4K Action Camera with SD Card','gopro-hero-11-4k-action-camera-with-sd-card',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,97,'piece',NULL,NULL,NULL,NULL,97,0.0,0,NULL,1,7,'assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-08-a-1-hover.webp',NULL,'[{\"label\": \"Nouveau\", \"variant\": \"green\"}]','[{\"hex\": \"#202020\", \"name\": \"Noir\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-08-a-1.webp\"}, {\"hex\": \"#9e9e9e\", \"name\": \"Gris\", \"image\": null}, {\"hex\": \"#171717\", \"name\": \"Noir clair\", \"image\": null}]',15,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(12,243,7,'Samsung Galaxy N-569 Tab S7 with Stylish – 8GB/128GB','samsung-galaxy-n-569-tab-s7-with-stylish-8gb128gb',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,97,'piece',NULL,NULL,NULL,NULL,97,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp','assets/images/product-img/electronics/electronics-bg-trans-07-a-1-hover.webp',NULL,'[{\"label\": \"Tendance\", \"variant\": \"yellow\"}]','[{\"hex\": \"#afb1b3\", \"name\": \"Gris\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-07-a-1.webp\"}, {\"hex\": \"#7796b9\", \"name\": \"Bleu ciel\", \"image\": null}, {\"hex\": \"#b84a5f\", \"name\": \"Rose rouge\", \"image\": null}]',15,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(13,159,NULL,'Beats Studio Pro Wireless Earbuds – Black','beats-studio-pro-wireless-earbuds-black',NULL,NULL,NULL,42000,NULL,153000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-01.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(14,243,NULL,'Apple 12.9-inch iPad Pro Wi-Fi 512GB Gray Space','apple-129-inch-ipad-pro-wi-fi-512gb-gray-space',NULL,NULL,NULL,15500,NULL,33500,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-02.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(15,85,NULL,'DJI OM 5 Handheld Smartphone Gimbal','dji-om-5-handheld-smartphone-gimbal',NULL,NULL,NULL,42000,NULL,70000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-03.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(16,110,NULL,'Apple Watch Ultra 2 – Titanium Case','apple-watch-ultra-2-titanium-case',NULL,NULL,NULL,36000,NULL,58000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-04.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(17,241,9,'Apple MacBook Pro 16-inch – M2 Chip','apple-macbook-pro-16-inch-m2-chip',NULL,NULL,NULL,42000,NULL,70000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-05.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(18,243,NULL,'Apple iPad Air 10.9-inch – Wi-Fi 256GB','apple-ipad-air-109-inch-wi-fi-256gb',NULL,NULL,NULL,60000,NULL,131500,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-06.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(19,98,7,'Samsung Galaxy Watch 4 Aluminum Smartwatch 44MM Bluetooth','samsung-galaxy-watch-4-aluminum-smartwatch-44mm-bluetooth',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,97,'piece',NULL,NULL,NULL,NULL,97,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-lg-01.webp',NULL,NULL,'[{\"label\": \"Nouveau\", \"variant\": \"green\"}]','[{\"hex\": \"#ed9951\", \"name\": \"Orange\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-list-lg-01.webp\"}, {\"hex\": \"#fffdfc\", \"name\": \"Blanc\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-list-lg-03.webp\"}, {\"hex\": \"#4c5f7b\", \"name\": \"Bleu\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-list-lg-04.webp\"}, {\"hex\": \"#f0f0f0\", \"name\": \"Blanc clair\", \"image\": \"assets/images/product-img/electronics/electronics-bg-trans-list-lg-02.webp\"}]',16,NULL,'2026-12-27 16:04:38',1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(20,243,NULL,'2021 Apple 12.9-inch iPad 512GB Gray Space','2021-apple-129-inch-ipad-512gb-gray-space',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-list-02.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(21,228,NULL,'Nespresso Vertuo Plus Coffee Maker – Black','nespresso-vertuo-plus-coffee-maker-black',NULL,NULL,NULL,108000,NULL,177000,NULL,NULL,NULL,NULL,25,'piece',NULL,NULL,NULL,NULL,20,0.0,0,NULL,0,NULL,'assets/images/product-img/electronics/electronics-bg-trans-02.webp',NULL,NULL,NULL,NULL,0,NULL,NULL,1,0,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'All Outlet',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `position` int unsigned NOT NULL DEFAULT '0',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotions_starts_at_ends_at_index` (`starts_at`,`ends_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,'Méga fête du smartphone','Les smartphones des grandes marques à prix imbattables.','assets/images/offer-list/offer-card-image-1.webp','Sur tout le site',NULL,1,0,'2026-10-29 00:00:00','2026-11-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,'Fiesta des gadgets','Les derniers gadgets à prix cassés.','assets/images/offer-list/offer-card-image-2.webp','Sur tout le site',NULL,1,0,'2026-09-30 00:00:00','2026-10-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,'Festival high-tech','Équipez-vous avec des offres imbattables !','assets/images/offer-list/offer-card-image-3.webp','Sur tout le site',NULL,1,0,'2026-10-03 00:00:00','2026-10-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,'Carnaval de l’électronique','Le meilleur de l’électronique à prix électrisants.','assets/images/offer-list/offer-card-image-4.webp','Sur tout le site',NULL,1,0,'2026-11-27 00:00:00','2026-12-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,'Galaxie des gadgets','Des gadgets à des prix hors du commun !','assets/images/offer-list/offer-card-image-5.webp','Sur tout le site',NULL,1,0,'2026-12-29 00:00:00','2027-01-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,'Univers numérique','Plongez dans nos offres sur les gadgets incontournables !','assets/images/offer-list/offer-card-image-6.webp','Sur tout le site',NULL,1,0,'2026-11-29 00:00:00','2026-12-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,'Salon des technologies','Découvrez les technologies de demain !','assets/images/offer-list/offer-card-image-7.webp','Sur tout le site',NULL,1,0,'2026-12-29 00:00:00','2027-01-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(8,'Festival du mobile','Offres chocs sur les derniers smartphones, pour une durée limitée !','assets/images/offer-list/offer-card-image-8.webp','Sur tout le site',NULL,1,0,'2026-10-01 00:00:00','2026-11-18 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38'),(9,'Méga fête du smartphone','Les smartphones du moment à prix imbattables !','assets/images/offer-list/offer-card-image-9.webp','Sur tout le site',NULL,1,0,'2026-11-28 00:00:00','2027-01-19 23:59:59','2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
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
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(1,2),(2,2),(3,2),(4,2),(5,2),(6,2),(8,2),(1,3),(6,3),(7,3);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super-admin','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(2,'gestionnaire','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(3,'preparateur','web','2026-10-01 16:04:36','2026-10-01 16:04:36'),(4,'livreur','web','2026-10-01 16:04:36','2026-10-01 16:04:36');
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
INSERT INTO `sessions` VALUES ('ARux31IOeestxWEcGdMFtiXU7jPtBn4BPUsWCySv',NULL,'192.168.1.100','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJRdFI0Zjl2ZmE3QXUxUUI4RHJQMmFuYklES0g1c2JhVUtLNlUwaWJXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4xMDA6ODAwMCIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1790870867),('d9yIlEthgyaxJqdSFmaxnfay6fJ5dqagfOwXm9cR',NULL,'192.168.1.100','curl/8.14.1','eyJfdG9rZW4iOiJYTUp4Y3JhU1Vrd1R6UXlaRHRTcWJRa0pIWmw1MVQ2djBIZXZNZ0dsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4xMDA6ODA4MlwvYWRtaW5cL2xvZ2luIiwicm91dGUiOiJmaWxhbWVudC5hZG1pbi5hdXRoLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1790870685),('kkKgnX4R9CNH4TMJYTLPUMYzmKzxJWl50tXV1XaC',NULL,'192.168.1.100','curl/8.14.1','eyJfdG9rZW4iOiJpam1CTjlHTUl3bnBkWmxQRHBwT0ZFWU9SM1A0RzdUVkVZSkdIVVI5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4xMDA6ODA4MiIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1790870685),('pYHEZTXgZMMnOJIuIGdvJF46TVGqKXGaqQxrsU67',NULL,'192.168.1.100','curl/8.14.1','eyJfdG9rZW4iOiJ6QTZjQVhoVjFLek9pVTNPZWVrRktiNFBBdE00cmE1T0dZTGRnS05wIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzE5Mi4xNjguMS4xMDA6ODA4MlwvYm91dGlxdWUiLCJyb3V0ZSI6InNob3AuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1790870687);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('delivery.free_shipping_threshold','100000','2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slug_redirects`
--

DROP TABLE IF EXISTS `slug_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slug_redirects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `redirectable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirectable_id` bigint unsigned NOT NULL,
  `old_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_redirects_redirectable_type_old_slug_unique` (`redirectable_type`,`old_slug`),
  KEY `slug_redirects_redirectable_type_redirectable_id_index` (`redirectable_type`,`redirectable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slug_redirects`
--

LOCK TABLES `slug_redirects` WRITE;
/*!40000 ALTER TABLE `slug_redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `slug_redirects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_alerts`
--

DROP TABLE IF EXISTS `stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_alerts_product_variant_id_foreign` (`product_variant_id`),
  KEY `stock_alerts_user_id_foreign` (`user_id`),
  KEY `stock_alerts_product_id_notified_at_index` (`product_id`,`notified_at`),
  CONSTRAINT `stock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_alerts_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_alerts`
--

LOCK TABLES `stock_alerts` WRITE;
/*!40000 ALTER TABLE `stock_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `stock_after` int unsigned NOT NULL,
  `reason` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  KEY `stock_movements_product_variant_id_created_at_index` (`product_variant_id`,`created_at`),
  CONSTRAINT `stock_movements_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,9,9,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(2,2,12,12,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(3,3,4,4,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(4,4,2,2,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(5,5,0,0,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(6,6,5,5,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(7,7,16,16,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(8,8,9,9,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(9,9,97,97,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(10,10,97,97,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(11,11,97,97,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(12,12,97,97,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(13,13,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(14,14,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(15,15,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(16,16,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(17,17,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(18,18,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(19,19,97,97,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(20,20,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38'),(21,21,25,25,'initial',NULL,NULL,'2026-10-01 16:04:38','2026-10-01 16:04:38');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint unsigned NOT NULL DEFAULT '5',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `position` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT '0',
  `suspended_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `app_authentication_secret` text COLLATE utf8mb4_unicode_ci,
  `app_authentication_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test User','test@example.com',NULL,0,NULL,0,'2026-10-01 16:04:36','$2y$12$M0jkGiC4AJXT/f96PjpXW.VkjYeKARCqCVKBdt/MP0/ESmUUSOt6e',NULL,NULL,NULL,NULL,NULL,'cSKJedE6FG','2026-10-01 16:04:37','2026-10-01 16:04:37',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist_items`
--

DROP TABLE IF EXISTS `wishlist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `visitor` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_items_user_id_product_id_unique` (`user_id`,`product_id`),
  UNIQUE KEY `wishlist_items_visitor_product_id_unique` (`visitor`,`product_id`),
  KEY `wishlist_items_product_id_foreign` (`product_id`),
  KEY `wishlist_items_visitor_index` (`visitor`),
  CONSTRAINT `wishlist_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist_items`
--

LOCK TABLES `wishlist_items` WRITE;
/*!40000 ALTER TABLE `wishlist_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'kova_market'
--

--
-- Final view structure for view `customers`
--

/*!50001 DROP VIEW IF EXISTS `customers`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `customers` AS select `u`.`id` AS `id`,`u`.`id` AS `user_id`,`u`.`name` AS `name`,`u`.`phone` AS `phone`,`u`.`email` AS `email`,`u`.`created_at` AS `created_at`,(select count(0) from `orders` `o` where (((`o`.`user_id` = `u`.`id`) or ((`u`.`phone` is not null) and (`o`.`phone` = `u`.`phone`))) and (`o`.`deleted_at` is null))) AS `orders_count`,(select coalesce(sum(`o`.`total`),0) from `orders` `o` where (((`o`.`user_id` = `u`.`id`) or ((`u`.`phone` is not null) and (`o`.`phone` = `u`.`phone`))) and (`o`.`deleted_at` is null) and (`o`.`payment_status` = 'paye'))) AS `total_spent`,(select max(`o`.`created_at`) from `orders` `o` where (((`o`.`user_id` = `u`.`id`) or ((`u`.`phone` is not null) and (`o`.`phone` = `u`.`phone`))) and (`o`.`deleted_at` is null))) AS `last_order_at` from `users` `u` where (exists(select 1 from `model_has_roles` `r` where (`r`.`model_id` = `u`.`id`)) is false and ((`u`.`phone` is not null) or (`u`.`email` is not null))) union all select -(min(`o`.`id`)) AS `id`,NULL AS `user_id`,max(`o`.`customer_name`) AS `name`,`o`.`phone` AS `phone`,max(`o`.`email`) AS `email`,min(`o`.`created_at`) AS `created_at`,count(0) AS `orders_count`,coalesce(sum((case when (`o`.`payment_status` = 'paye') then `o`.`total` else 0 end)),0) AS `total_spent`,max(`o`.`created_at`) AS `last_order_at` from `orders` `o` where ((`o`.`user_id` is null) and (`o`.`deleted_at` is null) and (`o`.`phone` <> '') and exists(select 1 from `users` `u` where (`u`.`phone` = `o`.`phone`)) is false) group by `o`.`phone` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 16:15:13
