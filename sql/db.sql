-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: cryptogate
-- ------------------------------------------------------
-- Server version	10.6.12-MariaDB-0ubuntu0.22.04.1

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
-- Table structure for table `deal`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deal` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(250) NOT NULL DEFAULT '',
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `count_order` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_binance`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_binance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `parent_id` int(11) unsigned NOT NULL DEFAULT 0,
  `preorder_id` int(11) unsigned NOT NULL DEFAULT 0,
  `stock_id` int(11) unsigned NOT NULL DEFAULT 0,
  `data` longtext NOT NULL DEFAULT '',
  `stock_order_id_1` varchar(250) NOT NULL DEFAULT '',
  `stock_order_id_2` varchar(250) NOT NULL DEFAULT '',
  `state` varchar(250) NOT NULL DEFAULT '',
  `dt_upd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_upd` int(11) unsigned NOT NULL DEFAULT 0,
  `dt_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_check` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_binance_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_binance_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `stock_id` int(11) unsigned NOT NULL DEFAULT 0,
  `action` varchar(250) NOT NULL DEFAULT '',
  `request` longtext NOT NULL DEFAULT '',
  `data` longtext NOT NULL DEFAULT '',
  `stock_order_id_1` varchar(250) NOT NULL DEFAULT '',
  `stock_order_id_2` varchar(250) NOT NULL DEFAULT '',
  `state` int(11) unsigned NOT NULL DEFAULT 0,
  `weight_ip` int(11) unsigned NOT NULL DEFAULT 0,
  `weight_uid` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preorder`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preorder` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(250) NOT NULL DEFAULT '',
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `deal_id` int(11) NOT NULL DEFAULT 0,
  `stock_id` int(11) unsigned NOT NULL DEFAULT 0,
  `type` varchar(250) NOT NULL DEFAULT '',
  `side` varchar(250) NOT NULL DEFAULT '',
  `positionSide` varchar(250) NOT NULL DEFAULT '',
  `pair` varchar(250) NOT NULL DEFAULT '',
  `data` longtext NOT NULL DEFAULT '',
  `state` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `deal_id` (`deal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `stock` varchar(250) NOT NULL DEFAULT '',
  `apikey` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `stock` (`stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `preorder_id` int(11) unsigned NOT NULL DEFAULT 0,
  `action` varchar(250) NOT NULL DEFAULT '',
  `mode` varchar(250) NOT NULL DEFAULT '',
  `state` int(11) unsigned NOT NULL DEFAULT 0,
  `dt_upd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_upd` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dt_ins` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ts_ins` int(11) unsigned NOT NULL DEFAULT 0,
  `token` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

