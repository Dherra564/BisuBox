-- Base de datos de BisuBox
-- Módulo 1: usuarios (vendedores y clientes), tiendas, contactos y sesiones
-- Las contraseñas de los usuarios por defecto están en el README

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
-- Estructura de tabla para la tabla `tbusuario`
-- Datos comunes de vendedores y clientes; el rol es Vendedor o Cliente
-- La identificación solo la tienen los vendedores (en clientes queda vacía)
--

CREATE TABLE `tbusuario` (
    `tbusuarioid` int(11) NOT NULL,
    `tbusuarioidentificaciontipo` varchar(20) DEFAULT NULL,
    `tbusuarioidentificacionnumero` varchar(50) DEFAULT NULL,
    `tbusuarionombrecompleto` varchar(100) DEFAULT NULL,
    `tbusuarioperfilimagen` varchar(500) DEFAULT NULL,
    `tbusuariocorreo` varchar(150) DEFAULT NULL,
    `tbusuariotelefono` varchar(50) DEFAULT NULL,
    `tbusuariocontrasena` varchar(255) DEFAULT NULL,
    `tbusuariorol` varchar(20) DEFAULT NULL,
    `tbusuarioregistrofecha` datetime DEFAULT NULL,
    `tbusuarioactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbvendedor`
-- Los datos de la tienda de cada vendedor
--

CREATE TABLE `tbvendedor` (
    `tbvendedorid` int(11) NOT NULL,
    `tbvendedorusuarioid` int(11) DEFAULT NULL,
    `tbvendedortiendanombre` varchar(100) DEFAULT NULL,
    `tbvendedortiendaenlace` varchar(60) DEFAULT NULL,
    `tbvendedortiendadescripcion` varchar(300) DEFAULT NULL,
    `tbvendedortiendalogo` varchar(500) DEFAULT NULL,
    `tbvendedoractivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbvendedorcontacto`
-- Contactos de la tienda, uno por fila: WhatsApp, Instagram, TikTok, Facebook u Otro
--

CREATE TABLE `tbvendedorcontacto` (
    `tbvendedorcontactoid` int(11) NOT NULL,
    `tbvendedorcontactovendedorid` int(11) DEFAULT NULL,
    `tbvendedorcontactotipo` varchar(20) DEFAULT NULL,
    `tbvendedorcontactovalor` varchar(300) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcliente`
-- Identifica al cliente; la ubicación de entrega se pide en cada pedido
--

CREATE TABLE `tbcliente` (
    `tbclienteid` int(11) NOT NULL,
    `tbclienteusuarioid` int(11) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbsesion`
-- tbsesionusuariotipo guarda el rol que tenía el usuario al iniciar sesión
--

CREATE TABLE `tbsesion` (
    `tbsesionid` int(11) NOT NULL,
    `tbsesionusuarioid` int(11) DEFAULT NULL,
    `tbsesionusuariotipo` varchar(20) DEFAULT NULL,
    `tbsesionfechainicio` datetime DEFAULT NULL,
    `tbsesionfechacierre` datetime DEFAULT NULL,
    `tbsesionactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Datos iniciales: un vendedor con su tienda y un cliente
--

INSERT INTO `tbusuario` (
    `tbusuarioid`, `tbusuarioidentificaciontipo`, `tbusuarioidentificacionnumero`,
    `tbusuarionombrecompleto`, `tbusuarioperfilimagen`, `tbusuariocorreo`, `tbusuariotelefono`,
    `tbusuariocontrasena`, `tbusuariorol`, `tbusuarioregistrofecha`, `tbusuarioactivo`
) VALUES
(1, 'Cedula', '200000002', 'Vendedor de Prueba', NULL, 'vendedor.bisubox@gmail.com', '77777777',
    '$2y$12$cy7xzNTAn0Gl/Ymia8TU1evpuuyjzp4UfwlBRIY0ocJvWYYVhAdzm', 'Vendedor', '2026-10-01 00:00:00', 1),
(2, NULL, NULL, 'Cliente de Prueba', NULL, 'cliente.bisubox@gmail.com', '66666666',
    '$2y$12$Kag4xPDKWvZgDtsJ8uYUqu71BQoosc2NilsEK4OWX/rr7K0vpYkdK', 'Cliente', '2026-10-01 00:00:00', 1);

INSERT INTO `tbvendedor` (
    `tbvendedorid`, `tbvendedorusuarioid`, `tbvendedortiendanombre`, `tbvendedortiendaenlace`,
    `tbvendedortiendadescripcion`, `tbvendedortiendalogo`, `tbvendedoractivo`
) VALUES
(1, 1, 'Tienda de Prueba', 'tienda-de-prueba', 'Bisutería hecha a mano para probar el sistema', NULL, 1);

INSERT INTO `tbvendedorcontacto` (
    `tbvendedorcontactoid`, `tbvendedorcontactovendedorid`, `tbvendedorcontactotipo`, `tbvendedorcontactovalor`
) VALUES
(1, 1, 'WhatsApp', '88451290'),
(2, 1, 'Instagram', 'tiendadeprueba');

INSERT INTO `tbcliente` (`tbclienteid`, `tbclienteusuarioid`) VALUES
(1, 2);

-- --------------------------------------------------------

--
-- Índices
--

ALTER TABLE `tbusuario` ADD PRIMARY KEY (`tbusuarioid`);

ALTER TABLE `tbvendedor` ADD PRIMARY KEY (`tbvendedorid`);

ALTER TABLE `tbvendedorcontacto` ADD PRIMARY KEY (`tbvendedorcontactoid`);

ALTER TABLE `tbcliente` ADD PRIMARY KEY (`tbclienteid`);

ALTER TABLE `tbsesion` ADD PRIMARY KEY (`tbsesionid`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;