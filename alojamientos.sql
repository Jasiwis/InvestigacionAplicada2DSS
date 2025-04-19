-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-04-2025 a las 02:09:41
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `alojamientos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospedajes`
--

CREATE TABLE `hospedajes` (
  `id` int(11) NOT NULL,
  `tipo` enum('Hotel','Rancho','Villa de lujo') NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `capacidad` int(11) NOT NULL,
  `precio_noche` decimal(10,2) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_llegada` date DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `tipo_alojamiento` enum('Villa de lujo','Rancho','Hotel') DEFAULT NULL,
  `numero_huespedes` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `enviado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `hospedajes`
--
ALTER TABLE `hospedajes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `hospedajes`
--
ALTER TABLE `hospedajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
