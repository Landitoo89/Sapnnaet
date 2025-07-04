-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 27-06-2025 a las 00:01:16
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rrhh`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos_tablas`
--

DROP TABLE IF EXISTS `respaldos_tablas`;
CREATE TABLE IF NOT EXISTS `respaldos_tablas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabla` varchar(120) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `respaldos_tablas`
--

INSERT INTO `respaldos_tablas` (`id`, `tabla`, `archivo`, `usuario`, `fecha`) VALUES
(1, 'completa', 'BD/rrhh_2025-06-25_22-53-25.sql', 'Juan Pina', '2025-06-25 22:53:27'),
(2, 'datos_laborales', 'BD/rrhh_datos_laborales_2025-06-25_22-54-53.sql', 'Juan Pina', '2025-06-25 22:54:53'),
(3, 'datos_personales', 'BD/rrhh_datos_personales_2025-06-25_22-54-53.sql', 'Juan Pina', '2025-06-25 22:54:54'),
(4, 'completa', 'BD/rrhh_2025-06-25_23-08-02.sql', 'Juan Pina', '2025-06-25 23:08:03'),
(5, 'datos_laborales', 'BD/rrhh_datos_laborales_2025-06-25_23-15-55.sql', 'Juan Pina', '2025-06-25 23:15:56'),
(6, 'datos_personales', 'BD/rrhh_datos_personales_2025-06-25_23-16-56.sql', 'Juan Pina', '2025-06-25 23:16:56'),
(7, 'session_logs', 'BD/rrhh_session_logs_2025-06-25_23-27-30.sql', 'Juan Pina', '2025-06-25 23:27:31'),
(8, 'session_logs', 'BD/rrhh_session_logs_2025-06-25_23-28-07.sql', 'Juan Pina', '2025-06-25 23:28:07'),
(9, 'session_logs', 'BD/rrhh_session_logs_2025-06-25_23-28-28.sql', 'Juan Pina', '2025-06-25 23:28:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','supervisor','empleado') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'empleado',
  `token_reset` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `avatar`, `password`, `rol`, `token_reset`, `token_expira`, `creado_en`, `remember_token`) VALUES
(3, 'Jose', 'Pérez', 'admin@empresa.com', '/proyecto/inicio/img/avatar/hombre2.png', '$2y$10$G8fz5rjibT6h0s4pFDXAQeCNwLjPV9uRVAtbFOMIjbRr9LavsZihS', 'admin', NULL, NULL, '2025-04-23 20:04:28', NULL),
(6, 'Juan', 'Pina', 'ju4npin414@gmail.com', '/proyecto/inicio/img/avatar/hombre.png', '$2y$10$mOrEIE2q7d8Y2njlmFD7w.mZ8wdpRegBrA8UknUOQWZlpcwTiIv4e', 'admin', NULL, NULL, '2025-06-14 23:23:09', '2f040a5a5b3f2ce3a10c2f772b154b83191d562ad1162de3d6134101a45a48a4'),
(7, 'Angie', 'materan', 'angiemateran@gmail.com', '/proyecto/inicio/img/avatar/mujer4.png', '$2y$10$gelVrM1nZpRhg1k4QExwkOp1vphTm6q0GAOx8Zbh0NTZ4GRMUlMNm', 'empleado', NULL, NULL, '2025-06-14 23:37:01', NULL),
(9, 'Angie', 'Materan', 'angiemateran@hotmail.com', '/proyecto/inicio/img/avatar/mujer2.png', '$2y$10$X5O/VXelkRhG/RiFaK2xduc.eON/kbbR2RtEJsq29xjpcmP51p69W', 'supervisor', NULL, NULL, '2025-06-23 23:39:11', NULL),
(11, 'Cheo', 'Piña', 'josejesuspp@hotmail.com', '/proyecto/inicio/img/avatar/hombre.png', '$2y$10$EWoA83rvK9hy.XLaRtt0v.3kIlWdyqf8MWYpvhxk1duyLj1IguF9u', 'supervisor', NULL, NULL, '2025-06-25 23:07:37', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
