

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Base de datos: `bdbisubox`
--
CREATE DATABASE IF NOT EXISTS `bdbisubox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bdbisubox`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbsesion`
-- tbsesionusuariotipo guarda el rol que tenia el usuario al iniciar sesion
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
-- Estructura de tabla para la tabla `tbusuario`
-- Administrador y Vendedor estan unificados: el rol es la columna tbusuariorol
-- (Administrador o Vendedor)
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
-- Datos iniciales: administrador y vendedor por defecto
-- (las contrasenas estan en el README)
--

INSERT INTO
    `tbusuario` (
        `tbusuarioid`,
        `tbusuarioidentificaciontipo`,
        `tbusuarioidentificacionnumero`,
        `tbusuarionombrecompleto`,
        `tbusuarioperfilimagen`,
        `tbusuariocorreo`,
        `tbusuariotelefono`,
        `tbusuariocontrasena`,
        `tbusuariorol`,
        `tbusuarioregistrofecha`,
        `tbusuarioactivo`
    )
VALUES (
        1,
        'Cedula',
        '100000001',
        'Administrador General',