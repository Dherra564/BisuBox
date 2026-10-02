-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-10-2026 a las 03:32:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bdbisubox`
--
CREATE DATABASE IF NOT EXISTS `bdbisubox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bdbisubox`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbsesion`
--

CREATE TABLE `tbsesion` (
  `tbsesionid` int(11) NOT NULL,
  `tbsesionusuarioid` int(11) DEFAULT NULL,
  `tbsesionusuariotipo` varchar(20) DEFAULT NULL,
  `tbsesionfechainicio` datetime DEFAULT current_timestamp(),
  `tbsesionfechacierre` datetime DEFAULT NULL,
  `tbsesionactivo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbsuperadmin`
--

CREATE TABLE `tbsuperadmin` (
  `tbsuperadminid` int(11) NOT NULL,
  `tbusuarioid` int(11) DEFAULT NULL,
  `tbsuperadminactivo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbusuario`
--

CREATE TABLE `tbusuario` (
  `tbusuarioid` int(11) NOT NULL,
  `tbusuarioidentificacionnumero` varchar(50) DEFAULT NULL,
  `tbusuarionombrecompleto` varchar(100) DEFAULT NULL,
  `tbusuarioperfilimagen` varchar(500) DEFAULT NULL,
  `tbusuariocorreo` varchar(150) DEFAULT NULL,
  `tbusuariocontrasena` varchar(255) DEFAULT NULL,
  `tbusuarioregistrofecha` datetime DEFAULT current_timestamp(),
  `tbusuarioactivo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbvendedor`
--

CREATE TABLE `tbvendedor` (
  `tbvendedorid` int(11) NOT NULL,
  `tbusuarioid` int(11) DEFAULT NULL,
  `tbvendedortelefono` varchar(50) DEFAULT NULL,
  `tbvendedorregistrofecha` datetime DEFAULT current_timestamp(),
  `tbvendedoractivo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbsesion`
--
ALTER TABLE `tbsesion`
  ADD PRIMARY KEY (`tbsesionid`);

--
-- Indices de la tabla `tbsuperadmin`
--
ALTER TABLE `tbsuperadmin`
  ADD PRIMARY KEY (`tbsuperadminid`);

--
-- Indices de la tabla `tbusuario`
--
ALTER TABLE `tbusuario`
  ADD PRIMARY KEY (`tbusuarioid`);

--
-- Indices de la tabla `tbvendedor`
--
ALTER TABLE `tbvendedor`
  ADD PRIMARY KEY (`tbvendedorid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;