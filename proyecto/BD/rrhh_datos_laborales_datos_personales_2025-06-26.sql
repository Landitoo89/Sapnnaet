mysqldump: [Warning] Using a password on the command line interface can be insecure.
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
INSERT INTO `datos_laborales` VALUES (7,1,'jesussapnnaet02@gmail.com','2000-12-25','muchas vainas','T',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-07 01:32:32','2025-06-22 03:21:48','activo',1),(8,16,'eduardosapnnaet@gmail.com','2023-10-28',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-08 22:10:51','2025-05-27 23:34:43','activo',1),(9,17,'oliversapnnaet@gmail.com','2005-05-15',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-15 18:04:01','2025-05-27 23:34:50','activo',1),(10,29,'migueltrabajador@gmail.com','2014-05-12',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-21 22:32:50','2025-05-27 23:34:56','activo',1),(12,33,'trabajador@gmail.com','2005-02-04','Planificador en el Area de Informatica','ACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-28 02:12:36','2025-05-28 22:51:46','inactivo',1),(14,35,'ailberthnavatrabajo@gmail.com','2022-05-30','Trabajante Adminisrativo','NUEVO',2,2,3,2,'No',NULL,NULL,NULL,'2025-05-30 15:50:01','2025-06-21 01:10:52','vacaciones',1),(16,37,'tuliosapnnaet@gmail.com','2026-06-01','Administrador encargado','NUEVO',1,2,1,1,'No',NULL,NULL,NULL,'2025-06-04 13:43:54','2025-06-04 13:56:22','reposo',1),(19,48,'and@gmail.com','2020-02-10','Planificador en el Area de Informatica','Trabajador Eficiente',1,1,2,1,'No',NULL,NULL,NULL,'2025-06-11 02:23:55','2025-06-15 18:30:53','activo',1),(21,13,'betsa@gmail.com','2024-01-15','nada','6734',3,2,1,2,'Sí','Comuna','2009-05-04','2013-10-24','2025-06-16 16:14:31','2025-06-20 03:58:35','activo',1),(22,51,'pedrito@gmail.com','2023-01-30','jhjkk jhjh','6736',3,2,1,3,'Sí','Comuna scial','2020-12-18','2022-11-23','2025-06-20 04:06:34','2025-06-20 04:06:34','activo',1),(23,52,'cheito@gmail.com','2022-12-20','rhyeh','6735',3,2,1,3,'Sí','cafe','2012-06-20','2019-10-20','2025-06-20 04:18:11','2025-06-21 01:10:04','vacaciones',1);
/*!40000 ALTER TABLE `datos_laborales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `datos_personales`
--

DROP TABLE IF EXISTS `datos_personales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `datos_personales` (
  `id_pers` int NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula_identidad` varchar(20) DEFAULT NULL,
  `pasaporte` varchar(20) DEFAULT NULL,
  `rif` varchar(20) DEFAULT NULL,
  `genero` enum('Masculino','Femenino','No binario','Prefiero no decir','Otro') NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nacionalidad` varchar(50) DEFAULT NULL,
  `correo_electronico` varchar(100) DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `telefono_contacto_secundario` varchar(20) DEFAULT NULL,
  `nombre_contacto_emergencia` varchar(100) DEFAULT NULL,
  `apellido_contacto_emergencia` varchar(100) DEFAULT NULL,
  `telefono_contacto_emergencia` varchar(20) DEFAULT NULL,
  `tiene_discapacidad` varchar(50) DEFAULT NULL,
  `tiene_licencia_conducir` varchar(30) DEFAULT NULL,
  `numero_seguro_social` varchar(30) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `direccion` varchar(100) NOT NULL,
  `detalle_discapacidad` varchar(255) DEFAULT 'No',
  `detalle_licencia` varchar(255) DEFAULT 'No',
  PRIMARY KEY (`id_pers`),
  UNIQUE KEY `cedula_identidad` (`cedula_identidad`),
  UNIQUE KEY `correo_electronico` (`correo_electronico`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_personales`
--

LOCK TABLES `datos_personales` WRITE;
/*!40000 ALTER TABLE `datos_personales` DISABLE KEYS */;
INSERT INTO `datos_personales` VALUES (1,'Jesus Francisco','Montilla Olmos','30866991','NO POSEE','308669919','Masculino','2005-03-28','Venezolano','jesus.montillaolm2803@gmail.com','4126714950',NULL,'Arnoldo Jose','Montilla Nuñez','4126711577','No','No','15489765','2025-05-06 00:24:53','2025-05-30 15:39:54','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(13,'Orlando Manuel','Ortega Gonzalez','V-27889926','NO POSEE','V-278899269','Masculino','2000-12-24','Venezolano','orlando33333333@gmail.com','04121588974',NULL,'Juan','Piña','04261748896','No','Sí','15789475','2025-05-06 20:41:32','2025-06-20 03:57:24','Valera, Estado Trujillo, Casa S/N','No aplica','2da'),(14,'Juan Diego','Piña Materan','31.413.623','NO POSEE','314136232','Masculino','2005-10-18','Venezolano','juanpina123@gmail.com','4129875412',NULL,'Jose de Jesus','Piña Pacheco','4267488150','No','Sí','NO POSEE','2025-05-08 20:19:47','2025-05-08 20:19:47','Pampanito, Estado Trujillo, Casa S/N','No aplica','3era'),(15,'Juan Diego','Salcedo Angel','31.008.131','NO POSEE','310081311','Masculino','2005-01-05','Venezolano','juanxzall2009@gmail.com','4124565132',NULL,'Juan Carlos','Salcedo Ramirez','2726711541','No','No','NO POSEE','2025-05-08 20:23:17','2025-05-08 20:23:17','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(16,'Eduardo Jose','Peñaloza Olmos','18.456.345','NO POSEE','184563453','Masculino','1989-04-28','Venezolano','eduardopenalozaolmos@gmail.com','4143797274',NULL,'Luz Marina','Olmos','4147499185','No','Sí','459988174','2025-05-08 22:07:46','2025-05-08 22:07:46','Pampanito, Estado Trujillo, Casa S/N','No aplica','3era'),(17,'Oliver Josue','Rondon Araujo','30.866.964','NO POSEE','308669646','Masculino','2005-05-15','Venezolano','oliverjosue@gmail.com','4148651322',NULL,'Juan Diego','Piña Materan','4121578933','No','No','154788','2025-05-15 18:02:22','2025-05-15 18:02:22','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(27,'Antonio','Lopez','17.188.999','NO POSEE','17188991','Masculino','2005-01-01','Venezolano','correopersona123@gmail.com','4148899111',NULL,'Diego','Veloz','2726715588','No','No','11555','2025-05-19 18:44:24','2025-05-20 00:38:22','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(29,'Miguel Eduardo','Gonzalez Gonzalez','31555888','NO POSEE','315558881','Masculino','2005-05-21','Venezolano','miguelejemplo123@gmail.com','4147788999',NULL,'Yohan ','Estrada','2726725899','No','No','11223','2025-05-21 22:04:22','2025-06-03 18:40:25','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(30,'Guibel','Opening','554477888','NO POSEE','5544778880','Masculino','2006-05-12','Extranjero','correoejem@gmail.com','4148899666',NULL,'Samuel','Machado','4148899666','Sí','No','454545','2025-05-22 00:49:36','2025-05-25 23:46:35','Valera, Estado Trujillo, Casa S/N','TDAH','No aplica'),(32,'Ola','Amigo','5555555','NO POSEE','55555555','Masculino','2007-05-10','Venezolano','correosis@gmail.com','4246699123',NULL,'adios','amigo','4247788123','No','No','1122','2025-05-27 22:13:25','2025-06-03 18:40:06','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(33,'Nuevo Nuevo','Regis Regis','14141150','NO APLICA','141411501','Masculino','2005-05-12','Venezolano','personaekis@gmail.com','4168884422',NULL,'Primero Primero','Pausa Pausa','4163336622','No','No','123444','2025-05-28 01:29:50','2025-06-03 18:39:53','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(35,'Ailberth ','Navas','30738034','NO POSEE','307380340','Masculino','2000-05-30','Venezolano','ailbert@gmail.com','4126774485',NULL,'Juan','Pablo','4246688999','No','No','442255','2025-05-30 15:43:28','2025-05-30 15:43:28','Plata 2','No aplica','No aplica'),(37,'Tulio','Mendez','12045934','NO APLICA','120459348','Masculino','1975-03-21','Venezolano','tulio@gmail.com','4127755888',NULL,'Eduardo','Peñaloza','4246711606','No','No','123456','2025-06-04 13:34:55','2025-06-04 13:34:55','Los Cerrillos','No aplica','No aplica'),(39,'Juan Orlando','Ortega Perez','V-31666997','NO POSEE','V-316669976','Prefiero no decir','2000-10-10','Venezolano','correopersonal@gmail.com','04147589667','04267899654','Miguel','Gonzales Estrada','04169633254','No','No','456688','2025-06-10 19:56:46','2025-06-10 19:56:46','Edificio 2 Pba','No aplica','No aplica'),(42,'Juan Diego','Orlando Ortega','V-131232453','NO POSEE','V-6546554564','Prefiero no decir','2006-01-03','Venezolano','orlando5711667@gmail.com','04121609721',NULL,'Miguel','Orlando Ortega','04121609723','No','No','5654654654','2025-06-10 22:18:12','2025-06-10 22:18:12','Edificio 2 Pba','No aplica','No aplica'),(43,'Juan Orlando','Ortega Perez','V-56453154','NO POSEE','V-456464156','Prefiero no decir','2000-10-10','Venezolano','correopersonl@gmail.com','04147589667',NULL,'Miguel','Gonzales Estrada','04169633254','No','No','456688','2025-06-10 22:39:52','2025-06-10 22:39:52','Edificio 2 Pba','No aplica','No aplica'),(44,'Juan Pablo','Piña Materan','V-6534566','NO POSEE','V-31666988','No binario','2000-10-10','Extranjero','correopedrol@gmail.com','04147589665',NULL,'Miguel','Gonzales Estrada','04169633254','No','No','456588','2025-06-10 22:50:06','2025-06-10 22:50:06','Edificio 3 Pba','No aplica','No aplica'),(45,'Orlando Negro','Manuel Felix','V-32555666','NO POSEE','V-325556667','No binario','2000-05-10','Venezolano','felixcorreo@gmail.com','04126655998',NULL,'Juan','Ramirez','04268899666','No','No','1255668','2025-06-10 22:55:11','2025-06-10 22:55:11','Pampanito','No aplica','No aplica'),(48,'Andrus Jose','Ramirez Rosales','V-31653879','NO POSEE','V-316538795','Masculino','2002-07-09','Venezolano','andruscorreo@gmail.com','04265786399',NULL,'Miguelito','Ortega','04147788654','No','No','4158978','2025-06-11 02:21:34','2025-06-15 06:01:06','Beatriz Valera','No aplica','No aplica'),(49,'Angie margie','Materán','V-13376361','NO POSEE','V-25646354','Femenino','1981-06-09','Extranjero','angiemateran@gmail.com','04269790595',NULL,'jose','roberto','04127895766','No','No','163','2025-06-15 19:26:56','2025-06-15 19:57:53','pampanito','No aplica','No aplica'),(50,'pedro','Materán','V-13376362','NO POSEE','V-25646352','No binario','2000-07-05','Venezolano','angiemateran2@gmail.com','04269790594',NULL,'jose','roberto','04127895763','No','No','163','2025-06-15 21:24:31','2025-06-15 21:24:31','valera','No aplica','No aplica'),(51,'Pedrito','ovejito','V-13375361','NO POSEE','V-25646359','Otro','2005-06-16','Venezolano','pedrito@gmail.com','04269790596',NULL,'jose','roberto','04127895766','No','No','163','2025-06-17 03:42:30','2025-06-17 04:44:37','agua clara','No aplica','No aplica'),(52,'Jose Jesus','Piña Pacheco','11130553','NO POSEE','V-646414584','Masculino','1977-12-05','Venezolano','josejesuspp@hotmail.com','04167714340',NULL,'jose','roberto','04127895766','No','No','163','2025-06-20 04:16:20','2025-06-20 04:19:01','pampanito','No aplica','No aplica');
/*!40000 ALTER TABLE `datos_personales` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 22:22:10
