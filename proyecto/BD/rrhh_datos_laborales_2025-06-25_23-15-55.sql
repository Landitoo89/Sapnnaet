-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: rrhh
-- ------------------------------------------------------
-- Server version	9.1.0

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
-- Table structure for table `datos_laborales`
--

DROP TABLE IF EXISTS `datos_laborales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `datos_laborales` (
  `id_laboral` int NOT NULL AUTO_INCREMENT,
  `id_pers` int NOT NULL,
  `correo_institucional` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `descripcion_funciones` text,
  `ficha` varchar(50) DEFAULT NULL,
  `id_departamento` int DEFAULT NULL,
  `id_cargo` int DEFAULT NULL,
  `id_contrato` int DEFAULT NULL,
  `id_coordinacion` int DEFAULT NULL,
  `ha_trabajado_anteriormente` enum('Sí','No') DEFAULT 'No',
  `nombre_empresa_anterior` varchar(255) DEFAULT NULL,
  `ano_ingreso_anterior` date DEFAULT NULL,
  `ano_culminacion_anterior` date DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` enum('activo','vacaciones','inactivo','reposo') DEFAULT 'activo',
  `id_tipo_personal` int DEFAULT NULL,
  PRIMARY KEY (`id_laboral`),
  UNIQUE KEY `correo_institucional` (`correo_institucional`),
  KEY `id_pers` (`id_pers`),
  KEY `id_departamento` (`id_departamento`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_contrato` (`id_contrato`),
  KEY `id_coordinacion` (`id_coordinacion`),
  KEY `fk_datos_laborales_tipos_personal` (`id_tipo_personal`),
  CONSTRAINT `datos_laborales_ibfk_1` FOREIGN KEY (`id_pers`) REFERENCES `datos_personales` (`id_pers`) ON DELETE CASCADE,
  CONSTRAINT `datos_laborales_ibfk_3` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  CONSTRAINT `datos_laborales_ibfk_4` FOREIGN KEY (`id_cargo`) REFERENCES `cargos` (`id_cargo`),
  CONSTRAINT `datos_laborales_ibfk_5` FOREIGN KEY (`id_contrato`) REFERENCES `tipos_contrato` (`id_contrato`),
  CONSTRAINT `datos_laborales_ibfk_6` FOREIGN KEY (`id_coordinacion`) REFERENCES `coordinaciones` (`id_coordinacion`),
  CONSTRAINT `fk_datos_laborales_tipos_personal` FOREIGN KEY (`id_tipo_personal`) REFERENCES `tipos_personal` (`id_tipo_personal`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_laborales`
--

LOCK TABLES `datos_laborales` WRITE;
/*!40000 ALTER TABLE `datos_laborales` DISABLE KEYS */;
INSERT INTO `datos_laborales` VALUES (7,1,'jesussapnnaet02@gmail.com','2000-12-25','muchas vainas','T',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-07 01:32:32','2025-06-22 03:21:48','activo',1),(8,16,'eduardosapnnaet@gmail.com','2023-10-28',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-08 22:10:51','2025-05-27 23:34:43','activo',1),(9,17,'oliversapnnaet@gmail.com','2005-05-15',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-15 18:04:01','2025-05-27 23:34:50','activo',1),(10,29,'migueltrabajador@gmail.com','2014-05-12',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-21 22:32:50','2025-05-27 23:34:56','activo',1),(12,33,'trabajador@gmail.com','2005-02-04','Planificador en el Area de Informatica','ACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-28 02:12:36','2025-05-28 22:51:46','inactivo',1),(14,35,'ailberthnavatrabajo@gmail.com','2022-05-30','Trabajante Adminisrativo','NUEVO',2,2,3,2,'No',NULL,NULL,NULL,'2025-05-30 15:50:01','2025-06-21 01:10:52','vacaciones',1),(16,37,'tuliosapnnaet@gmail.com','2026-06-01','Administrador encargado','NUEVO',1,2,1,1,'No',NULL,NULL,NULL,'2025-06-04 13:43:54','2025-06-04 13:56:22','reposo',1),(19,48,'and@gmail.com','2020-02-10','Planificador en el Area de Informatica','Trabajador Eficiente',1,1,2,1,'No',NULL,NULL,NULL,'2025-06-11 02:23:55','2025-06-15 18:30:53','activo',1),(21,13,'betsa@gmail.com','2024-01-15','nada','6734',3,2,1,2,'Sí','Comuna','2009-05-04','2013-10-24','2025-06-16 16:14:31','2025-06-20 03:58:35','activo',1),(23,52,'cheito@gmail.com','2022-12-20','rhyeh','6735',3,2,1,3,'Sí','cafe','2012-06-20','2019-10-20','2025-06-20 04:18:11','2025-06-21 01:10:04','vacaciones',1);
/*!40000 ALTER TABLE `datos_laborales` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 23:15:56
