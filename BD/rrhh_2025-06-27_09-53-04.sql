-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: rrhh
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `action_logs`
--

DROP TABLE IF EXISTS `action_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `action_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `action_logs`
--

LOCK TABLES `action_logs` WRITE;
/*!40000 ALTER TABLE `action_logs` DISABLE KEYS */;
INSERT INTO `action_logs` VALUES (1,6,'reporte_datos_personales_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 04:34:09','Generación de reporte PDF de datos personales'),(2,6,'view_personal_edit_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 04:44:30','Visualización de formulario para ID: 51'),(3,6,'personal_data_updated','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 04:44:37','Usuario ID: 6 actualizó datos personales ID: 51\nCambios realizados:\napellidos: \'ovejo\' → \'ovejito\'\ncedula_identidad: \'V-13375361\' → \'N/A\'\nrif: \'V-25646359\' → \'N/A\'\nnombre_contacto_emergencia: \'jose\' → \'N/A\'\napellido_contacto_emergencia: \'roberto\' → \'N/A\'\ntelefono_contacto_emergencia: \'04127895766\' → \'N/A\''),(4,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:16:04','Acceso a generador de reportes de talento humano'),(5,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:16:24','Acceso a generador de reportes de talento humano'),(6,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:17:54','Acceso a generador de reportes de talento humano'),(7,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:20:27','Acceso a generador de reportes de talento humano'),(8,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:20:30','Acceso al generador de reportes de talento humano'),(9,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:22:31','Generación de reporte: Listado General de Empleados'),(10,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 19:22:43','Generación de reporte: Empleados por Departamento'),(11,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:29:53','Acceso a generador de reportes de talento humano'),(12,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:32:05','Acceso a generador de reportes de talento humano'),(13,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:35:00','Generación de reporte: Listado General de Empleados'),(14,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:36:35','Acceso a generador de reportes de talento humano'),(15,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:41:27','Acceso a generador de reportes de talento humano'),(16,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:41:49','Generación de reporte: Empleados por Departamento'),(17,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:42:00','Acceso a generador de reportes de talento humano'),(18,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:42:24','Acceso a generador de reportes de talento humano'),(19,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:47:04','Generación de reporte: Listado General de Empleados'),(20,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:49:48','Generación de reporte: Listado General de Empleados'),(21,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:50:25','Generación de reporte: Listado General de Empleados'),(22,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:52:27','Generación de reporte: Listado General de Empleados'),(23,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:53:46','Generación de reporte: Listado General de Empleados'),(24,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:53:59','Generación de reporte: Listado General de Empleados'),(25,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:54:25','Generación de reporte: Listado General de Empleados'),(26,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:54:38','Generación de reporte: Listado General de Empleados'),(27,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:54:48','Generación de reporte: Listado General de Empleados'),(28,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:55:06','Generación de reporte: Listado General de Empleados'),(29,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:55:16','Generación de reporte: Listado General de Empleados'),(30,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:55:25','Generación de reporte: Listado General de Empleados'),(31,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:55:51','Generación de reporte: Listado General de Empleados'),(32,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 21:56:03','Generación de reporte: Listado General de Empleados'),(33,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 02:34:55','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(34,6,'edit_laboral_data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 02:35:48','Usuario ID: 6 editó registro laboral ID: 21\nTrabajador: Orlando Manuel Ortega Gonzalez\nCambios:\nha_trabajado_anteriormente: \'No\' → \'Sí\'\nnombre_empresa_anterior: \'\' → \'Comuna\'\nano_ingreso_anterior: \'\' → \'2024-05-15\'\nano_culminacion_anterior: \'\' → \'2025-02-26\''),(35,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 02:36:21','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(36,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 02:36:55','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(37,6,'edit_laboral_data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:53:29','Usuario ID: 6 editó registro laboral ID: 21\nTrabajador: Orlando Manuel Ortega Gonzalez\nCambios:\nano_ingreso_anterior: \'2024-05-15\' → \'2024-01-09\''),(38,6,'edit_laboral_data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:54:57','Usuario ID: 6 editó registro laboral ID: 21\nTrabajador: Orlando Manuel Ortega Gonzalez\nCambios:\nfecha_ingreso: \'2025-05-14\' → \'2024-01-15\'\nano_ingreso_anterior: \'2024-01-09\' → \'2009-05-04\'\nano_culminacion_anterior: \'2025-02-26\' → \'2013-10-24\''),(39,6,'view_personal_edit_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:56:33','Visualización de formulario para ID: 13'),(40,0,'Se eliminó el registro del personal con CI: 278899','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:56:50',''),(41,6,'view_personal_edit_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:57:00','Visualización de formulario para ID: 13'),(42,6,'personal_data_updated','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:57:24','Usuario ID: 6 actualizó datos personales ID: 13\nCambios realizados:\ncedula_identidad: \'27.889.926\' → \'N/A\'\nrif: \'278899269\' → \'N/A\'\nnombre_contacto_emergencia: \'Juan\' → \'N/A\'\napellido_contacto_emergencia: \'Piña\' → \'N/A\'\ntelefono_contacto_emergencia: \'4261748896\' → \'N/A\''),(43,6,'edit_laboral_data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:58:35','Usuario ID: 6 editó registro laboral ID: 21\nTrabajador: Orlando Manuel Ortega Gonzalez\nCambios:\nestado: \'vacaciones\' → \'activo\''),(44,6,'view_laboral_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:05:12','Usuario: [6]\nAcción: view_laboral_form\nDetalles: Visualización de formulario para ID Persona: 51'),(45,6,'laboral_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:06:34','Usuario: [6]\nAcción: laboral_created\nDetalles: Datos laborales creados para ID Persona: 51 - ID Laboral: 22'),(46,6,'view_socioeconomic_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:09:43','Usuario: [6]\nAcción: view_socioeconomic_form\nDetalles: Visualización de formulario para ID Persona: 51'),(47,6,'socioeconomic_success','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:09:58','Usuario: [6]\nAcción: socioeconomic_success\nDetalles: Operación exitosa (creación) para ID Persona: 51'),(48,6,'view_carga_familiar_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:09:58','Visualización del formulario de cargas familiares para ID Persona: 51, Trabajador: Pedrito ovejito'),(49,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:14:45','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(50,6,'personal_data_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:20','Usuario: [6]\nAcción: personal_data_created\nDetalles: Nuevo registro creado con ID: 52\nNombre: Jose Jesus Piña Pacheco\nCédula: V-11130553\nRIF: V-646414584'),(51,6,'view_socioeconomic_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:20','Usuario: [6]\nAcción: view_socioeconomic_form\nDetalles: Visualización de formulario para ID Persona: 52'),(52,6,'socioeconomic_success','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:51','Usuario: [6]\nAcción: socioeconomic_success\nDetalles: Operación exitosa (creación) para ID Persona: 52'),(53,6,'view_carga_familiar_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:51','Visualización del formulario de cargas familiares para ID Persona: 52, Trabajador: Jose Jesus Piña Pacheco'),(54,6,'carga_familiar_skipped','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:57','Se omitió el registro de cargas familiares para ID Persona: 52, Trabajador: Jose Jesus Piña Pacheco'),(55,6,'view_laboral_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:16:57','Usuario: [6]\nAcción: view_laboral_form\nDetalles: Visualización de formulario para ID Persona: 52'),(56,6,'laboral_validation_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:18:02','Usuario: [6]\nAcción: laboral_validation_failed\nDetalles: Errores de validación para ID Persona: 52 - La ficha laboral ya existe. Por favor, introduzca una ficha única.'),(57,6,'laboral_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 04:18:11','Usuario: [6]\nAcción: laboral_created\nDetalles: Datos laborales creados para ID Persona: 52 - ID Laboral: 23'),(58,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 10:08:55','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(59,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 10:14:34','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(60,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:18:11','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(61,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:22:06','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(62,6,'personal_data_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:25:22','Usuario: [6]\nAcción: personal_data_created\nDetalles: Nuevo registro creado con ID: 53\nNombre: Karina Gutierrez\nCédula: 20789378\nRIF: 207893780'),(63,6,'view_socioeconomic_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:25:22','Usuario: [6]\nAcción: view_socioeconomic_form\nDetalles: Visualización de formulario para ID Persona: 53'),(64,6,'socioeconomic_success','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:26:09','Usuario: [6]\nAcción: socioeconomic_success\nDetalles: Operación exitosa (creación) para ID Persona: 53'),(65,6,'view_carga_familiar_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:26:09','Visualización del formulario de cargas familiares para ID Persona: 53, Trabajador: Karina Gutierrez'),(66,6,'carga_familiar_skipped','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:29:28','Se omitió el registro de cargas familiares para ID Persona: 53, Trabajador: Karina Gutierrez'),(67,6,'view_laboral_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:29:28','Usuario: [6]\nAcción: view_laboral_form\nDetalles: Visualización de formulario para ID Persona: 53'),(68,6,'laboral_validation_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:32:09','Usuario: [6]\nAcción: laboral_validation_failed\nDetalles: Errores de validación para ID Persona: 53 - El correo institucional no es válido.'),(69,6,'laboral_insert_error','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:32:29','Usuario: [6]\nAcción: laboral_insert_error\nDetalles: Error al registrar datos para ID Persona: 53 - Hubo un error al registrar los datos laborales: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'fecha_ingreso_anterior\' in \'field list\''),(70,6,'laboral_validation_failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:36:01','Usuario: [6]\nAcción: laboral_validation_failed\nDetalles: Errores de validación para ID Persona: 53 - Si ha trabajado anteriormente, debe completar todos los campos de experiencia previa.'),(71,6,'laboral_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:37:01','Usuario: [6]\nAcción: laboral_created\nDetalles: Datos laborales creados para ID Persona: 53 - ID Laboral: 24'),(72,6,'view_personal_data_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:13:06','Usuario: [6]\nAcción: view_personal_data_form\nDetalles: Visualización del formulario de registro de datos personales'),(73,6,'personal_data_created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:18:04','Usuario: [6]\nAcción: personal_data_created\nDetalles: Nuevo registro creado con ID: 54\nNombre: Karina Gutierrez\nCédula: 17321336\nRIF: 173213365'),(74,6,'view_socioeconomic_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:18:04','Usuario: [6]\nAcción: view_socioeconomic_form\nDetalles: Visualización de formulario para ID Persona: 54'),(75,6,'view_socioeconomic_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:18:34','Usuario: [6]\nAcción: view_socioeconomic_form\nDetalles: Visualización de formulario para ID Persona: 54'),(76,6,'socioeconomic_success','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:20:21','Usuario: [6]\nAcción: socioeconomic_success\nDetalles: Operación exitosa (creación) para ID Persona: 54'),(77,6,'view_carga_familiar_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:20:21','Visualización del formulario de cargas familiares para ID Persona: 54, Trabajador: Karina Gutierrez'),(78,6,'carga_familiar_saved','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:22:02','Registro de carga familiar para ID Persona: 54, Trabajador: Karina Gutierrez. Familiar: Laura simancas (Parentesco: Hijo/a, Discapacidad: No, Cédula: 367535462)'),(79,6,'view_laboral_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:22:02','Usuario: [6]\nAcción: view_laboral_form\nDetalles: Visualización de formulario para ID Persona: 54'),(80,6,'view_laboral_form','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:22:29','Usuario: [6]\nAcción: view_laboral_form\nDetalles: Visualización de formulario para ID Persona: 54'),(81,6,'acceso_generador_reportes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:32:28','Acceso a generador de reportes de talento humano'),(82,6,'generacion_reporte_pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:32:53','Generación de reporte: Listado General de Empleados');
/*!40000 ALTER TABLE `action_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archivo_ubicacion`
--

DROP TABLE IF EXISTS `archivo_ubicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archivo_ubicacion` (
  `archivo_id` int NOT NULL,
  `cajon_id` int NOT NULL,
  PRIMARY KEY (`archivo_id`,`cajon_id`),
  KEY `cajon_id` (`cajon_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivo_ubicacion`
--

LOCK TABLES `archivo_ubicacion` WRITE;
/*!40000 ALTER TABLE `archivo_ubicacion` DISABLE KEYS */;
INSERT INTO `archivo_ubicacion` VALUES (4,4),(5,5),(7,9),(9,7),(10,3),(11,3);
/*!40000 ALTER TABLE `archivo_ubicacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archivos`
--

DROP TABLE IF EXISTS `archivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fecha_creacion` date NOT NULL,
  `descripcion` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivos`
--

LOCK TABLES `archivos` WRITE;
/*!40000 ALTER TABLE `archivos` DISABLE KEYS */;
INSERT INTO `archivos` VALUES (1,'A-001','Informe anual 2025','Carpeta','2025-02-03','dehtd hd df'),(3,'B-001','Informe anual 2026','PDF','2025-02-03','fdghdfh dfdfhdfh'),(5,'CAJ-D1-001','Informe anual 2025','Documento','2025-04-16','ugkjhkjk'),(7,'CAJ-B1-001','Informe anual 2025','PDF','2025-04-17','gsfghsfhhfsfh'),(9,'CAJ-A1-001','Cosas','Documento','2025-06-17','ifhdj'),(10,'CAJ-HR1-001','Informe deporte','Libro','2025-06-17','dhdfh'),(11,'CAJ-HR1-002','Informe deporte','Libro','2025-06-17','dhdfh');
/*!40000 ALTER TABLE `archivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cajones`
--

DROP TABLE IF EXISTS `cajones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estante_id` int NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` text,
  PRIMARY KEY (`id`),
  KEY `estante_id` (`estante_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajones`
--

LOCK TABLES `cajones` WRITE;
/*!40000 ALTER TABLE `cajones` DISABLE KEYS */;
INSERT INTO `cajones` VALUES (1,1,'CAJ-R1','Documentos de visitantes'),(2,1,'CAJ-R2','Formularios varios'),(3,2,'CAJ-HR1','Expedientes activos'),(4,2,'CAJ-HR2','Contratos laborales'),(5,3,'CAJ-HH1','Expedientes antiguos (2010-2020)'),(6,4,'CAJ-D1','Documentos estratégicos'),(7,5,'CAJ-A1','Archivo A-D'),(8,5,'CAJ-A2','Archivo E-H'),(9,6,'CAJ-B1','Archivo I-M'),(11,1,'ALM','jgyjfhj');
/*!40000 ALTER TABLE `cajones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carga_familiar`
--

DROP TABLE IF EXISTS `carga_familiar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carga_familiar` (
  `id_carga` int NOT NULL AUTO_INCREMENT,
  `id_socioeconomico` int NOT NULL,
  `parentesco` enum('Cónyuge','Hijo/a','Padre','Madre','Hermano/a','Otro') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nombres_familiar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `apellidos_familiar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `fecha_nacimiento_familiar` date NOT NULL,
  `edad_familiar` int NOT NULL,
  `cedula_familiar` varchar(20) DEFAULT NULL,
  `genero_familiar` enum('Masculino','Femenino','Otro') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `archivo_deficit` varchar(255) DEFAULT NULL,
  `tiene_discapacidad` enum('Sí','No') DEFAULT 'No',
  `detalle_discapacidad` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_carga`),
  KEY `id_socioeconomico` (`id_socioeconomico`),
  CONSTRAINT `carga_familiar_ibfk_1` FOREIGN KEY (`id_socioeconomico`) REFERENCES `datos_socioeconomicos` (`id_socioeconomico`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carga_familiar`
--

LOCK TABLES `carga_familiar` WRITE;
/*!40000 ALTER TABLE `carga_familiar` DISABLE KEYS */;
INSERT INTO `carga_familiar` VALUES (3,3,'Hermano/a','Jesus Francisco','Montilla Olmos','0000-00-00',0,'30.866.991','Masculino',NULL,'No',NULL),(4,3,'Madre','Luz Marina','Olmos','0000-00-00',0,'9.315.506','Femenino',NULL,'No',NULL),(5,4,'Madre','Luisa','Rondon','0000-00-00',0,'15.758.364','Femenino',NULL,'No',NULL),(6,11,'Padre','Andrus Ramirez','Gonzalez','0000-00-00',0,'18.455.222','Masculino',NULL,'No',NULL),(7,11,'Padre','Andrus Ramirez','Gonzalez','0000-00-00',0,'18.455.222','Masculino',NULL,'No',NULL),(8,12,'Hijo/a','Luis','Andres','0000-00-00',0,'36.999.888','Masculino',NULL,'No',NULL),(9,12,'Hijo/a','Antonidas','Medin','0000-00-00',0,'NO POSEE','Masculino','uploads/cargas/682e7b081bd20_002.jpg','No','Ninguna'),(11,15,'Hijo/a','Hijo','Nuevo','0000-00-00',0,'NO POSEE','Masculino',NULL,'No',NULL),(12,15,'Hijo/a','Papu','Dos','0000-00-00',0,'NO POSEE','Masculino',NULL,'No',NULL),(14,17,'Hijo/a','Orlando ','Montilla','0000-00-00',0,'11222333','Masculino',NULL,'No',NULL),(15,17,'Cónyuge','Oliver','Araujo','0000-00-00',0,'33666999','Femenino',NULL,'No',NULL),(29,1,'Hijo/a','Pedro','Pablo','2016-01-13',9,'12130553','Masculino','../uploads/cargas/6850d693c10b0_cumple_16.jpg','Sí','Trastorno de personalidad'),(30,37,'Hijo/a','Laura','simancas','2015-07-06',9,'367535462','Femenino',NULL,'No',NULL);
/*!40000 ALTER TABLE `carga_familiar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cargos`
--

DROP TABLE IF EXISTS `cargos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cargos` (
  `id_cargo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `nivel` enum('Junior','Senior','Gerencial','Directivo') DEFAULT NULL,
  `sueldo` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cargos`
--

LOCK TABLES `cargos` WRITE;
/*!40000 ALTER TABLE `cargos` DISABLE KEYS */;
INSERT INTO `cargos` VALUES (1,'Planificador I','Junior',110.00),(2,'Administrativo','Junior',110.00),(4,'Nadador','Junior',150.00);
/*!40000 ALTER TABLE `cargos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coordinaciones`
--

DROP TABLE IF EXISTS `coordinaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coordinaciones` (
  `id_coordinacion` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `id_departamento` int DEFAULT NULL,
  PRIMARY KEY (`id_coordinacion`),
  KEY `id_departamento` (`id_departamento`),
  CONSTRAINT `coordinaciones_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coordinaciones`
--

LOCK TABLES `coordinaciones` WRITE;
/*!40000 ALTER TABLE `coordinaciones` DISABLE KEYS */;
INSERT INTO `coordinaciones` VALUES (1,'Coordinación Informatica',1),(2,'Coordinacion de Testiles',2),(3,'Coordinacion General de Talento Humano',3);
/*!40000 ALTER TABLE `coordinaciones` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_laborales`
--

LOCK TABLES `datos_laborales` WRITE;
/*!40000 ALTER TABLE `datos_laborales` DISABLE KEYS */;
INSERT INTO `datos_laborales` VALUES (7,1,'jesussapnnaet02@gmail.com','2000-12-25',NULL,'T',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-07 01:32:32','2025-05-31 16:57:20','activo',1),(8,16,'eduardosapnnaet@gmail.com','2023-10-28',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-08 22:10:51','2025-05-27 23:34:43','activo',1),(9,17,'oliversapnnaet@gmail.com','2005-05-15',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-15 18:04:01','2025-05-27 23:34:50','activo',1),(10,29,'migueltrabajador@gmail.com','2014-05-12',NULL,'PERSONALACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-21 22:32:50','2025-05-27 23:34:56','activo',1),(12,33,'trabajador@gmail.com','2005-02-04','Planificador en el Area de Informatica','ACTIVO',1,1,1,1,'No',NULL,NULL,NULL,'2025-05-28 02:12:36','2025-05-28 22:51:46','inactivo',1),(14,35,'ailberthnavatrabajo@gmail.com','2022-05-30','Trabajante Adminisrativo','NUEVO',2,2,3,2,'No',NULL,NULL,NULL,'2025-05-30 15:50:01','2025-06-27 01:31:31','reposo',1),(16,37,'tuliosapnnaet@gmail.com','2026-06-01','Administrador encargado','NUEVO',1,2,1,1,'No',NULL,NULL,NULL,'2025-06-04 13:43:54','2025-06-04 13:56:22','reposo',1),(19,48,'and@gmail.com','2020-02-10','Planificador en el Area de Informatica','Trabajador Eficiente',1,1,2,1,'No',NULL,NULL,NULL,'2025-06-11 02:23:55','2025-06-15 18:30:53','activo',1),(21,13,'betsa@gmail.com','2024-01-15','nada','6734',3,2,1,2,'Sí','Comuna','2009-05-04','2013-10-24','2025-06-16 16:14:31','2025-06-20 03:58:35','activo',1),(22,51,'pedrito@gmail.com','2023-01-30','jhjkk jhjh','6736',3,2,1,3,'Sí','Comuna scial','2020-12-18','2022-11-23','2025-06-20 04:06:34','2025-06-20 04:06:34','activo',1),(23,52,'cheito@gmail.com','2022-12-20','rhyeh','6735',3,2,1,3,'Sí','cafe','2012-06-20','2019-10-20','2025-06-20 04:18:11','2025-06-21 01:10:04','vacaciones',1),(24,53,'karinauppt@gmail.com','2022-03-01','trabajar','123456',1,4,7,3,'Sí','Gobernación del Estado Trujillo','2016-03-16','2018-07-19','2025-06-21 17:37:01','2025-06-21 18:25:53','activo',8);
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_personales`
--

LOCK TABLES `datos_personales` WRITE;
/*!40000 ALTER TABLE `datos_personales` DISABLE KEYS */;
INSERT INTO `datos_personales` VALUES (1,'Jesus Francisco','Montilla Olmos','30866991','NO POSEE','308669919','Masculino','2005-03-28','Venezolano','jesus.montillaolm2803@gmail.com','4126714950',NULL,'Arnoldo Jose','Montilla Nuñez','4126711577','No','No','15489765','2025-05-06 00:24:53','2025-05-30 15:39:54','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(13,'Orlando Manuel','Ortega Gonzalez','V-27889926','NO POSEE','V-278899269','Masculino','2000-12-24','Venezolano','orlando33333333@gmail.com','04121588974',NULL,'Juan','Piña','04261748896','No','Sí','15789475','2025-05-06 20:41:32','2025-06-20 03:57:24','Valera, Estado Trujillo, Casa S/N','No aplica','2da'),(14,'Juan Diego','Piña Materan','31.413.623','NO POSEE','314136232','Masculino','2005-10-18','Venezolano','juanpina123@gmail.com','4129875412',NULL,'Jose de Jesus','Piña Pacheco','4267488150','No','Sí','NO POSEE','2025-05-08 20:19:47','2025-05-08 20:19:47','Pampanito, Estado Trujillo, Casa S/N','No aplica','3era'),(15,'Juan Diego','Salcedo Angel','31.008.131','NO POSEE','310081311','Masculino','2005-01-05','Venezolano','juanxzall2009@gmail.com','4124565132',NULL,'Juan Carlos','Salcedo Ramirez','2726711541','No','No','NO POSEE','2025-05-08 20:23:17','2025-05-08 20:23:17','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(16,'Eduardo Jose','Peñaloza Olmos','18.456.345','NO POSEE','184563453','Masculino','1989-04-28','Venezolano','eduardopenalozaolmos@gmail.com','4143797274',NULL,'Luz Marina','Olmos','4147499185','No','Sí','459988174','2025-05-08 22:07:46','2025-05-08 22:07:46','Pampanito, Estado Trujillo, Casa S/N','No aplica','3era'),(17,'Oliver Josue','Rondon Araujo','30.866.964','NO POSEE','308669646','Masculino','2005-05-15','Venezolano','oliverjosue@gmail.com','4148651322',NULL,'Juan Diego','Piña Materan','4121578933','No','No','154788','2025-05-15 18:02:22','2025-05-15 18:02:22','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(27,'Antonio','Lopez','17.188.999','NO POSEE','17188991','Masculino','2005-01-01','Venezolano','correopersona123@gmail.com','4148899111',NULL,'Diego','Veloz','2726715588','No','No','11555','2025-05-19 18:44:24','2025-05-20 00:38:22','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(29,'Miguel Eduardo','Gonzalez Gonzalez','31555888','NO POSEE','315558881','Masculino','2005-05-21','Venezolano','miguelejemplo123@gmail.com','4147788999',NULL,'Yohan ','Estrada','2726725899','No','No','11223','2025-05-21 22:04:22','2025-06-03 18:40:25','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(30,'Guibel','Opening','554477888','NO POSEE','5544778880','Masculino','2006-05-12','Extranjero','correoejem@gmail.com','4148899666',NULL,'Samuel','Machado','4148899666','Sí','No','454545','2025-05-22 00:49:36','2025-05-25 23:46:35','Valera, Estado Trujillo, Casa S/N','TDAH','No aplica'),(32,'Ola','Amigo','5555555','NO POSEE','55555555','Masculino','2007-05-10','Venezolano','correosis@gmail.com','4246699123',NULL,'adios','amigo','4247788123','No','No','1122','2025-05-27 22:13:25','2025-06-03 18:40:06','Pampanito, Estado Trujillo, Casa S/N','No aplica','No aplica'),(33,'Nuevo Nuevo','Regis Regis','14141150','NO APLICA','141411501','Masculino','2005-05-12','Venezolano','personaekis@gmail.com','4168884422',NULL,'Primero Primero','Pausa Pausa','4163336622','No','No','123444','2025-05-28 01:29:50','2025-06-03 18:39:53','Valera, Estado Trujillo, Casa S/N','No aplica','No aplica'),(35,'Ailberth ','Navas','30738034','NO POSEE','307380340','Masculino','2000-05-30','Venezolano','ailbert@gmail.com','4126774485',NULL,'Juan','Pablo','4246688999','No','No','442255','2025-05-30 15:43:28','2025-05-30 15:43:28','Plata 2','No aplica','No aplica'),(37,'Tulio','Mendez','12045934','NO APLICA','120459348','Masculino','1975-03-21','Venezolano','tulio@gmail.com','4127755888',NULL,'Eduardo','Peñaloza','4246711606','No','No','123456','2025-06-04 13:34:55','2025-06-04 13:34:55','Los Cerrillos','No aplica','No aplica'),(39,'Juan Orlando','Ortega Perez','V-31666997','NO POSEE','V-316669976','Prefiero no decir','2000-10-10','Venezolano','correopersonal@gmail.com','04147589667','04267899654','Miguel','Gonzales Estrada','04169633254','No','No','456688','2025-06-10 19:56:46','2025-06-10 19:56:46','Edificio 2 Pba','No aplica','No aplica'),(42,'Juan Diego','Orlando Ortega','V-131232453','NO POSEE','V-6546554564','Prefiero no decir','2006-01-03','Venezolano','orlando5711667@gmail.com','04121609721',NULL,'Miguel','Orlando Ortega','04121609723','No','No','5654654654','2025-06-10 22:18:12','2025-06-10 22:18:12','Edificio 2 Pba','No aplica','No aplica'),(43,'Juan Orlando','Ortega Perez','V-56453154','NO POSEE','V-456464156','Prefiero no decir','2000-10-10','Venezolano','correopersonl@gmail.com','04147589667',NULL,'Miguel','Gonzales Estrada','04169633254','No','No','456688','2025-06-10 22:39:52','2025-06-10 22:39:52','Edificio 2 Pba','No aplica','No aplica'),(44,'Juan Pablo','Piña Materan','V-6534566','NO POSEE','V-31666988','No binario','2000-10-10','Extranjero','correopedrol@gmail.com','04147589665',NULL,'Miguel','Gonzales Estrada','04169633254','No','No','456588','2025-06-10 22:50:06','2025-06-10 22:50:06','Edificio 3 Pba','No aplica','No aplica'),(45,'Orlando Negro','Manuel Felix','V-32555666','NO POSEE','V-325556667','No binario','2000-05-10','Venezolano','felixcorreo@gmail.com','04126655998',NULL,'Juan','Ramirez','04268899666','No','No','1255668','2025-06-10 22:55:11','2025-06-10 22:55:11','Pampanito','No aplica','No aplica'),(48,'Andrus Jose','Ramirez Rosales','V-31653879','NO POSEE','V-316538795','Masculino','2002-07-09','Venezolano','andruscorreo@gmail.com','04265786399',NULL,'Miguelito','Ortega','04147788654','No','No','4158978','2025-06-11 02:21:34','2025-06-15 06:01:06','Beatriz Valera','No aplica','No aplica'),(49,'Angie margie','Materán','V-13376361','NO POSEE','V-25646354','Femenino','1981-06-09','Extranjero','angiemateran@gmail.com','04269790595',NULL,'jose','roberto','04127895766','No','No','163','2025-06-15 19:26:56','2025-06-15 19:57:53','pampanito','No aplica','No aplica'),(50,'pedro','Materán','V-13376362','NO POSEE','V-25646352','No binario','2000-07-05','Venezolano','angiemateran2@gmail.com','04269790594',NULL,'jose','roberto','04127895763','No','No','163','2025-06-15 21:24:31','2025-06-15 21:24:31','valera','No aplica','No aplica'),(51,'Pedrito','ovejito','V-13375361','NO POSEE','V-25646359','Otro','2005-06-16','Venezolano','pedrito@gmail.com','04269790596',NULL,'jose','roberto','04127895766','No','No','163','2025-06-17 03:42:30','2025-06-17 04:44:37','agua clara','No aplica','No aplica'),(52,'Jose Jesus','Piña Pacheco','11130553','NO POSEE','V-646414584','Masculino','1977-12-05','Venezolano','josejesuspp@hotmail.com','04167714340',NULL,'jose','roberto','04127895766','No','No','163','2025-06-20 04:16:20','2025-06-20 04:19:01','pampanito','No aplica','No aplica'),(53,'Karina','Gutierrez','20789378','NO POSEE','207893780','Femenino','1994-06-21','Venezolano','karina@upttmbi.com','04265786398',NULL,'Jose de Jesus','Orlando Ortega','04121609723','No','No','123456','2025-06-21 17:25:22','2025-06-21 17:25:22','Valera','No aplica','No aplica'),(54,'Karina','Gutierrez','17321336','NO POSEE','173213365','Femenino','1987-03-05','Venezolano','pinin@gmail.com','04127886465',NULL,'Juan','Orlando Ortega','04121609722','No','No','4655454564','2025-06-21 18:18:04','2025-06-21 18:18:04','Valera','No aplica','No aplica');
/*!40000 ALTER TABLE `datos_personales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `datos_socioeconomicos`
--

DROP TABLE IF EXISTS `datos_socioeconomicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `datos_socioeconomicos` (
  `id_socioeconomico` int NOT NULL AUTO_INCREMENT,
  `id_pers` int NOT NULL,
  `estado_civil` enum('Soltero/a','Casado/a','Divorciado/a','Viudo/a','Unión Libre') NOT NULL,
  `nivel_academico` varchar(255) NOT NULL DEFAULT 'Ninguno',
  `mencion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `tipo_vivienda` enum('Propia','Alquilada','Prestada','Invadida','Otro') NOT NULL DEFAULT 'Propia',
  `servicios_agua` enum('Sí','No') NOT NULL DEFAULT 'No',
  `servicios_electricidad` enum('Sí','No') NOT NULL DEFAULT 'No',
  `servicios_internet` enum('Sí','No') NOT NULL DEFAULT 'No',
  `servicios_gas` enum('Sí','No') NOT NULL DEFAULT 'No',
  `tecnologia_computadora` enum('Sí','No') NOT NULL DEFAULT 'No',
  `tecnologia_smartphone` enum('Sí','No') NOT NULL DEFAULT 'No',
  `tecnologia_tablet` enum('Sí','No') NOT NULL DEFAULT 'No',
  `carnet_patria` enum('Sí','No') NOT NULL DEFAULT 'No',
  `codigo_patria` varchar(50) DEFAULT NULL,
  `serial_patria` varchar(50) DEFAULT NULL,
  `carnet_psuv` enum('Sí','No') NOT NULL DEFAULT 'No',
  `codigo_psuv` varchar(50) DEFAULT NULL,
  `serial_psuv` varchar(50) DEFAULT NULL,
  `instituciones_academicas` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_socioeconomico`),
  KEY `id_pers` (`id_pers`),
  CONSTRAINT `datos_socioeconomicos_ibfk_1` FOREIGN KEY (`id_pers`) REFERENCES `datos_personales` (`id_pers`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `datos_socioeconomicos`
--

LOCK TABLES `datos_socioeconomicos` WRITE;
/*!40000 ALTER TABLE `datos_socioeconomicos` DISABLE KEYS */;
INSERT INTO `datos_socioeconomicos` VALUES (1,1,'Casado/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(2,14,'Viudo/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(3,16,'Soltero/a','Universitario',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(4,17,'Soltero/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(11,29,'Soltero/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','No','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(12,30,'Soltero/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,NULL,'2025-06-15 21:49:30'),(15,33,'Soltero/a','Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','Sí','100998877',NULL,'Sí','992233889',NULL,'UPTTMBI','2025-06-15 21:49:30'),(17,35,'Soltero/a','Técnico|Técnico',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,'UPTT MBI|ULA','2025-06-15 21:49:30'),(19,37,'Soltero/a','Universitario|Universitario',NULL,'Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','Sí','465646',NULL,'Sí','445566',NULL,'UPTT MBI|ULA','2025-06-15 21:49:30'),(20,39,'Divorciado/a','Técnico','Informatica','Propia','Sí','Sí','No','No','Sí','No','No','No',NULL,NULL,'No',NULL,NULL,'UPTT','2025-06-15 21:49:30'),(21,42,'Casado/a','Técnico','administracion','Alquilada','Sí','No','No','No','Sí','No','No','No',NULL,NULL,'No',NULL,NULL,'ula','2025-06-15 21:49:30'),(22,43,'Divorciado/a','Postgrado','administracion','Prestada','Sí','No','No','No','Sí','No','No','No',NULL,NULL,'No',NULL,NULL,'ula','2025-06-15 21:49:30'),(23,44,'Unión Libre','Primaria Incompleta','','Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,'Santiago Sánchez','2025-06-15 21:49:30'),(25,45,'Casado/a','Universitario','Informatica','Alquilada','Sí','No','No','No','Sí','No','No','No',NULL,NULL,'No',NULL,NULL,'UPTT','2025-06-15 21:49:30'),(28,48,'Soltero/a','Técnico','Informatica','Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,'UPTT MBI','2025-06-15 21:49:30'),(29,49,'Casado/a','Postgrado','Geografía','Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,'ULA','2025-06-15 21:49:30'),(30,13,'Viudo/a','Secundaria Incompleta','','Invadida','No','No','Sí','No','No','No','No','No',NULL,NULL,'No',NULL,NULL,'liceo','2025-06-15 21:49:30'),(31,32,'Casado/a','Primaria Completa','','Prestada','No','No','No','No','No','No','No','No',NULL,NULL,'No',NULL,NULL,'liceo','2025-06-15 21:49:30'),(34,51,'Casado/a','Secundaria Incompleta','Geografía','Prestada','No','Sí','No','No','No','Sí','No','No',NULL,NULL,'No',NULL,NULL,'ULA','2025-06-20 04:09:58'),(35,52,'Casado/a','Técnico','electricidad','Propia','Sí','Sí','Sí','Sí','Sí','Sí','No','No',NULL,NULL,'No',NULL,NULL,'liceo','2025-06-20 04:16:51'),(36,53,'Casado/a','Universitario','Sistemas','Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,'ULA','2025-06-21 17:26:09'),(37,54,'Casado/a','Universitario','Gestion de Proyectos','Propia','Sí','Sí','Sí','Sí','Sí','Sí','Sí','No',NULL,NULL,'No',NULL,NULL,'ULA','2025-06-21 18:20:21');
/*!40000 ALTER TABLE `datos_socioeconomicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departamentos`
--

DROP TABLE IF EXISTS `departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamentos` (
  `id_departamento` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departamentos`
--

LOCK TABLES `departamentos` WRITE;
/*!40000 ALTER TABLE `departamentos` DISABLE KEYS */;
INSERT INTO `departamentos` VALUES (1,'Informatica'),(2,'Testiles'),(3,'Talento Humano');
/*!40000 ALTER TABLE `departamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edificios`
--

DROP TABLE IF EXISTS `edificios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edificios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `direccion` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edificios`
--

LOCK TABLES `edificios` WRITE;
/*!40000 ALTER TABLE `edificios` DISABLE KEYS */;
INSERT INTO `edificios` VALUES (1,'Edificio Principal','Av. Central #100'),(2,'Edificio Norte','Calle 5 #23-45'),(7,'Domo Bolivariano','Agua clara');
/*!40000 ALTER TABLE `edificios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleado_primas`
--

DROP TABLE IF EXISTS `empleado_primas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado_primas` (
  `id_laboral` int NOT NULL,
  `id_prima` int NOT NULL,
  `fecha_asignacion` date DEFAULT (curdate()),
  PRIMARY KEY (`id_laboral`,`id_prima`),
  KEY `id_prima` (`id_prima`),
  CONSTRAINT `empleado_primas_ibfk_1` FOREIGN KEY (`id_laboral`) REFERENCES `datos_laborales` (`id_laboral`) ON DELETE CASCADE,
  CONSTRAINT `empleado_primas_ibfk_2` FOREIGN KEY (`id_prima`) REFERENCES `primas` (`id_prima`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleado_primas`
--

LOCK TABLES `empleado_primas` WRITE;
/*!40000 ALTER TABLE `empleado_primas` DISABLE KEYS */;
INSERT INTO `empleado_primas` VALUES (14,1,'2025-05-30'),(14,2,'2025-05-30'),(14,3,'2025-05-30'),(14,4,'2025-05-30'),(16,1,'2025-06-04');
/*!40000 ALTER TABLE `empleado_primas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estantes`
--

DROP TABLE IF EXISTS `estantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `oficina_id` int NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oficina_id` (`oficina_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estantes`
--

LOCK TABLES `estantes` WRITE;
/*!40000 ALTER TABLE `estantes` DISABLE KEYS */;
INSERT INTO `estantes` VALUES (1,1,'EST-R1','Estante Recepción - Documentos frecuentes'),(2,2,'EST-RRHH','Estante Recursos Humanos'),(3,2,'EST-RRHH2','Estante RRHH - Archivo histórico'),(4,4,'EST-DIR','Estante Dirección - Documentos confidenciales'),(5,5,'EST-AR1','Estante Archivo #1'),(6,5,'EST-AR2','Estante Archivo #2'),(8,1,'HTS','Historiales de vida');
/*!40000 ALTER TABLE `estantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oficinas`
--

DROP TABLE IF EXISTS `oficinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oficinas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `piso_id` int NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `piso_id` (`piso_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oficinas`
--

LOCK TABLES `oficinas` WRITE;
/*!40000 ALTER TABLE `oficinas` DISABLE KEYS */;
INSERT INTO `oficinas` VALUES (1,1,'RCP','Recepción Principal'),(2,2,'OF2-1','Oficina RRHH'),(3,2,'OF2-2','Oficina Contabilidad'),(4,3,'OF3-1','Dirección General'),(5,4,'AR1','Archivo Central'),(6,5,'OFN1','Oficina Ventas');
/*!40000 ALTER TABLE `oficinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `periodos_vacaciones`
--

DROP TABLE IF EXISTS `periodos_vacaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periodos_vacaciones` (
  `id_periodo` int NOT NULL AUTO_INCREMENT,
  `id_pers` int NOT NULL,
  `fecha_inicio_periodo` date NOT NULL,
  `fecha_fin_periodo` date NOT NULL,
  `dias_asignados` int NOT NULL,
  `dias_usados` int DEFAULT '0',
  `estado` enum('activo','inactivo','usado') DEFAULT 'activo',
  `institucion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_periodo`),
  UNIQUE KEY `unq_periodo` (`id_pers`,`fecha_inicio_periodo`,`fecha_fin_periodo`,`institucion`),
  KEY `id_pers` (`id_pers`),
  CONSTRAINT `periodos_vacaciones_ibfk_1` FOREIGN KEY (`id_pers`) REFERENCES `datos_personales` (`id_pers`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `periodos_vacaciones`
--

LOCK TABLES `periodos_vacaciones` WRITE;
/*!40000 ALTER TABLE `periodos_vacaciones` DISABLE KEYS */;
INSERT INTO `periodos_vacaciones` VALUES (1,52,'2012-06-20','2013-06-19',15,15,'usado','cafe'),(2,52,'2013-06-20','2014-06-19',16,16,'usado','cafe'),(3,52,'2014-06-20','2015-06-19',17,17,'usado','cafe'),(4,52,'2015-06-20','2016-06-19',18,18,'usado','cafe'),(5,52,'2016-06-20','2017-06-19',19,19,'usado','cafe'),(6,52,'2017-06-20','2018-06-19',20,20,'usado','cafe'),(7,52,'2018-06-20','2019-06-19',21,21,'usado','cafe'),(8,52,'2022-12-20','2023-12-19',22,22,'usado','Sapnnaet'),(9,52,'2023-12-20','2024-12-19',23,23,'usado','Sapnnaet'),(19,16,'2023-10-28','2024-10-27',15,0,'activo','Sapnnaet'),(38,35,'2022-05-30','2023-05-29',15,15,'usado','Sapnnaet'),(39,35,'2023-05-30','2024-05-29',16,16,'usado','Sapnnaet'),(40,35,'2024-05-30','2025-05-29',17,0,'activo','Sapnnaet'),(41,13,'2009-05-04','2010-05-03',15,15,'usado','Comuna'),(42,13,'2010-05-04','2011-05-03',16,16,'usado','Comuna'),(43,13,'2011-05-04','2012-05-03',17,17,'usado','Comuna'),(44,13,'2012-05-04','2013-05-03',18,18,'usado','Comuna'),(45,13,'2024-01-15','2025-01-14',19,0,'activo','Sapnnaet');
/*!40000 ALTER TABLE `periodos_vacaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pisos`
--

DROP TABLE IF EXISTS `pisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pisos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `edificio_id` int NOT NULL,
  `numero` varchar(10) NOT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edificio_id` (`edificio_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pisos`
--

LOCK TABLES `pisos` WRITE;
/*!40000 ALTER TABLE `pisos` DISABLE KEYS */;
INSERT INTO `pisos` VALUES (1,1,'1','Planta Baja - Recepción'),(2,1,'2','Oficinas Nivel 2 :)'),(3,1,'3','Oficinas Nivel 3'),(4,2,'1','Sótano - Archivos'),(5,2,'2','Oficinas Norte');
/*!40000 ALTER TABLE `pisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `primas`
--

DROP TABLE IF EXISTS `primas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `primas` (
  `id_prima` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `monto` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_prima`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `primas`
--

LOCK TABLES `primas` WRITE;
/*!40000 ALTER TABLE `primas` DISABLE KEYS */;
INSERT INTO `primas` VALUES (1,'Transporte','Ayuda para gastos de transporte',50.00),(2,'Profesión','Incentivo por ejercicio de profesión crítica',100.00),(3,'Antigüedad','Bono por años de servicio',300.00),(4,'Productividad','Incentivo por cumplimiento de metas',150.00);
/*!40000 ALTER TABLE `primas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reposos`
--

DROP TABLE IF EXISTS `reposos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reposos` (
  `id_reposo` int NOT NULL AUTO_INCREMENT,
  `id_pers` int NOT NULL,
  `tipo_concesion` enum('obligatoria','potestativa') NOT NULL,
  `motivo_reposo` varchar(255) NOT NULL,
  `dias_otorgados` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activo','cumplido','pendiente') DEFAULT 'pendiente',
  `observaciones` text,
  `ruta_archivo_adjunto` varchar(255) DEFAULT NULL,
  `vacacion_interrumpida_id` int DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_reposo`),
  KEY `id_pers` (`id_pers`),
  CONSTRAINT `reposos_ibfk_1` FOREIGN KEY (`id_pers`) REFERENCES `datos_personales` (`id_pers`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reposos`
--

LOCK TABLES `reposos` WRITE;
/*!40000 ALTER TABLE `reposos` DISABLE KEYS */;
INSERT INTO `reposos` VALUES (8,37,'obligatoria','Matrimonio del trabajador',5,'2025-06-05','2025-06-12','activo','',NULL,NULL,'2025-06-04 13:56:22','2025-06-04 13:56:22'),(9,35,'obligatoria','Fallecimiento de cónyuges, padres o hijos',6,'2025-06-27','2025-07-07','activo','',NULL,19,'2025-06-27 01:31:31','2025-06-27 01:31:31');
/*!40000 ALTER TABLE `reposos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `respaldos_tablas`
--

DROP TABLE IF EXISTS `respaldos_tablas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `respaldos_tablas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabla` varchar(120) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `respaldos_tablas`
--

LOCK TABLES `respaldos_tablas` WRITE;
/*!40000 ALTER TABLE `respaldos_tablas` DISABLE KEYS */;
INSERT INTO `respaldos_tablas` VALUES (1,'completa','BD/rrhh_2025-06-25_22-53-25.sql','Juan Pina','2025-06-25 22:53:27'),(2,'datos_laborales','BD/rrhh_datos_laborales_2025-06-25_22-54-53.sql','Juan Pina','2025-06-25 22:54:53'),(3,'datos_personales','BD/rrhh_datos_personales_2025-06-25_22-54-53.sql','Juan Pina','2025-06-25 22:54:54'),(4,'completa','BD/rrhh_2025-06-25_23-08-02.sql','Juan Pina','2025-06-25 23:08:03'),(5,'datos_laborales','BD/rrhh_datos_laborales_2025-06-25_23-15-55.sql','Juan Pina','2025-06-25 23:15:56'),(6,'datos_personales','BD/rrhh_datos_personales_2025-06-25_23-16-56.sql','Juan Pina','2025-06-25 23:16:56'),(7,'session_logs','BD/rrhh_session_logs_2025-06-25_23-27-30.sql','Juan Pina','2025-06-25 23:27:31'),(8,'session_logs','BD/rrhh_session_logs_2025-06-25_23-28-07.sql','Juan Pina','2025-06-25 23:28:07'),(9,'session_logs','BD/rrhh_session_logs_2025-06-25_23-28-28.sql','Juan Pina','2025-06-25 23:28:28');
/*!40000 ALTER TABLE `respaldos_tablas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_logs`
--

DROP TABLE IF EXISTS `session_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `event_type` enum('login','logout','session_expired','access_denied') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_logs`
--

LOCK TABLES `session_logs` WRITE;
/*!40000 ALTER TABLE `session_logs` DISABLE KEYS */;
INSERT INTO `session_logs` VALUES (1,0,'','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:22:00','Intento fallido para: ju4npin414@gmail.com'),(2,6,'','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:23:09','Nuevo usuario creado: ju4npin414@gmail.com (admin)'),(3,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:23:45','Inicio de sesión exitoso'),(4,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:25:24','Inicio de sesión exitoso'),(5,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:36:07','Inicio de sesión exitoso'),(6,6,'','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:37:01','Nuevo usuario creado: angiemateran@gmail.com (empleado)'),(7,7,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:37:48','Inicio de sesión exitoso'),(8,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:40:23','Inicio de sesión exitoso'),(9,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:40:33','Inicio de sesión exitoso'),(10,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:49:09','Inicio de sesión exitoso'),(11,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:50:46','Inicio de sesión exitoso'),(12,7,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:53:18','Inicio de sesión exitoso'),(13,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 03:59:43','Inicio de sesión exitoso'),(14,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 05:25:25','Inicio de sesión exitoso'),(15,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 05:25:51','Inicio de sesión exitoso'),(16,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 17:27:24','Inicio de sesión exitoso'),(17,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 17:27:48','Inicio de sesión exitoso'),(18,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 17:27:59','Inicio de sesión exitoso'),(19,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 17:51:37','Inicio de sesión exitoso'),(20,6,'','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 18:02:42','Datos laborales actualizados para C.I.: N/A (ID Laboral: 19).'),(21,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 19:38:23','Inicio de sesión exitoso'),(22,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 19:50:04','Inicio de sesión exitoso'),(23,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-15 19:50:26','Inicio de sesión exitoso'),(24,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-16 16:00:33','Inicio de sesión exitoso'),(25,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-16 22:59:19','Inicio de sesión exitoso'),(26,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-16 23:49:10','Inicio de sesión exitoso'),(27,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-17 16:53:14','Inicio de sesión exitoso'),(28,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-18 02:50:32','Inicio de sesión exitoso'),(29,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-18 02:51:07','Inicio de sesión exitoso'),(30,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 01:41:20','Inicio de sesión exitoso'),(31,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:43:26','Inicio de sesión exitoso'),(32,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 03:59:49','Inicio de sesión exitoso'),(33,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-20 22:43:33','Inicio de sesión exitoso'),(34,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0','2025-06-21 00:56:31','Inicio de sesión exitoso'),(35,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 09:41:22','Inicio de sesión exitoso'),(36,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 10:08:33','Inicio de sesión exitoso'),(37,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 17:16:11','Inicio de sesión exitoso'),(38,8,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-21 18:40:05','Inicio de sesión exitoso'),(39,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 01:28:11','Inicio de sesión exitoso'),(40,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:14:09','Inicio de sesión exitoso'),(41,6,'logout','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:17:07','Cierre de sesión manual'),(42,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:21:42','Inicio de sesión exitoso'),(43,6,'logout','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:58:24','Cierre de sesión manual'),(44,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:59:15','Inicio de sesión exitoso'),(45,6,'logout','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:59:36','Cierre de sesión manual'),(46,8,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 10:59:50','Inicio de sesión exitoso'),(47,6,'login','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','2025-06-27 13:41:14','Inicio de sesión exitoso');
/*!40000 ALTER TABLE `session_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_contrato`
--

DROP TABLE IF EXISTS `tipos_contrato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_contrato` (
  `id_contrato` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id_contrato`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_contrato`
--

LOCK TABLES `tipos_contrato` WRITE;
/*!40000 ALTER TABLE `tipos_contrato` DISABLE KEYS */;
INSERT INTO `tipos_contrato` VALUES (1,'Indefinido'),(2,'Temporal'),(3,'Por Obra'),(7,'Pasante');
/*!40000 ALTER TABLE `tipos_contrato` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_personal`
--

DROP TABLE IF EXISTS `tipos_personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_personal` (
  `id_tipo_personal` int NOT NULL AUTO_INCREMENT,
  `nombre` enum('Jefe de Direccion','Coordinador','Empleado Contratado','Empleado Fijo','Obrero Contratado','Obrero Fijo') NOT NULL,
  PRIMARY KEY (`id_tipo_personal`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_personal`
--

LOCK TABLES `tipos_personal` WRITE;
/*!40000 ALTER TABLE `tipos_personal` DISABLE KEYS */;
INSERT INTO `tipos_personal` VALUES (1,'Empleado Fijo'),(2,'Obrero Fijo'),(3,'Empleado Contratado'),(4,'Obrero Contratado'),(7,'Jefe de Direccion'),(8,'Coordinador');
/*!40000 ALTER TABLE `tipos_personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','supervisor','empleado') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'empleado',
  `token_reset` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) DEFAULT NULL,
  `avatar` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (3,'Jose','Pérez','admin@empresa.com','$2y$10$G8fz5rjibT6h0s4pFDXAQeCNwLjPV9uRVAtbFOMIjbRr9LavsZihS','admin',NULL,NULL,'2025-04-23 20:04:28',NULL,NULL),(2,'Juan','Pina','juanopinon@gmail.com','$2y$10$dNtqQny9sVf4kuC08uwX8OocPKAFqCsv/JGRY2qjlnVtza/muzb8O','empleado',NULL,NULL,'2025-04-17 23:20:53',NULL,NULL),(4,'Oliver','Pérez','oliver@gmail.com','$2y$10$w0v9EYQV3u32QHmtvu.xj.JkLfaR/RKrIJbA1/IBaH1ibsV3PGWXG','admin',NULL,NULL,'2025-04-23 21:04:03',NULL,NULL),(5,'Juan','Pina','pinin@gmail.com','$2y$10$SHU2QONQlxsjs0PqYgPGruM2E5RnirHEW974lk0.b0RgiochnZNEW','admin',NULL,NULL,'2025-04-23 21:24:01','562399e4cb1fca73c9136cf232aa24b5a3e8321e60f9137d060eab2b65684690',NULL),(6,'Juan','Pina','ju4npin414@gmail.com','$2y$10$So320GiUMa3VDW.RBnPdjuI9lxe4SLUNjq.FLlKQKOyP5R8t51qJW','admin',NULL,'2025-07-27 10:21:42','2025-06-14 23:23:09','cd96cd5ec176331f935f565d233c1e130f57243edc8e3e4485149c296eb2992d','/proyecto/inicio/img/avatar/mujer.png'),(7,'Angie','materan','angiemateran@gmail.com','$2y$10$gelVrM1nZpRhg1k4QExwkOp1vphTm6q0GAOx8Zbh0NTZ4GRMUlMNm','empleado',NULL,NULL,'2025-06-14 23:37:01',NULL,NULL),(8,'ORLANDO MANUEL','Ortega','orlando5711666@gmail.com','$2y$10$KXv/nPSl0PK19CSrcOM/3euykoar32aKvhWZLLNfXCs9WiBxbRomy','admin',NULL,NULL,'2025-06-21 13:48:18',NULL,'/proyecto/inicio/img/avatar/hombre3.png');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacaciones`
--

DROP TABLE IF EXISTS `vacaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacaciones` (
  `id_vacaciones` int NOT NULL AUTO_INCREMENT,
  `id_pers` int NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('vacaciones','interrumpida','pendiente_reposo') DEFAULT 'vacaciones',
  `vacacion_original_id` int DEFAULT NULL,
  PRIMARY KEY (`id_vacaciones`),
  KEY `id_pers` (`id_pers`),
  CONSTRAINT `vacaciones_ibfk_1` FOREIGN KEY (`id_pers`) REFERENCES `datos_personales` (`id_pers`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacaciones`
--

LOCK TABLES `vacaciones` WRITE;
/*!40000 ALTER TABLE `vacaciones` DISABLE KEYS */;
INSERT INTO `vacaciones` VALUES (18,52,'2025-06-21','2025-08-04','vacaciones',NULL),(19,35,'2025-06-21','2025-06-26','interrumpida',NULL);
/*!40000 ALTER TABLE `vacaciones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-27  9:53:05
