-- Base de datos de BisuBox

DROP DATABASE IF EXISTS `bdbisubox`;

CREATE DATABASE `bdbisubox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bdbisubox`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbusuario`
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
    `tbusuarioactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbusuarioid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbvendedor`
--

CREATE TABLE `tbvendedor` (
    `tbvendedorid` int(11) NOT NULL,
    `tbvendedorusuarioid` int(11) DEFAULT NULL,
    `tbvendedortiendanombre` varchar(100) DEFAULT NULL,
    `tbvendedortiendaenlace` varchar(60) DEFAULT NULL,
    `tbvendedortiendadescripcion` varchar(300) DEFAULT NULL,
    `tbvendedortiendalogo` varchar(500) DEFAULT NULL,
    `tbvendedoractivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbvendedorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbvendedorcontacto`
--

CREATE TABLE `tbvendedorcontacto` (
    `tbvendedorcontactoid` int(11) NOT NULL,
    `tbvendedorcontactovendedorid` int(11) DEFAULT NULL,
    `tbvendedorcontactotipo` varchar(20) DEFAULT NULL,
    `tbvendedorcontactovalor` varchar(300) DEFAULT NULL,
    PRIMARY KEY (`tbvendedorcontactoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcliente`
--

CREATE TABLE `tbcliente` (
    `tbclienteid` int(11) NOT NULL,
    `tbclienteusuarioid` int(11) DEFAULT NULL,
    PRIMARY KEY (`tbclienteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbsesion`
--

CREATE TABLE `tbsesion` (
    `tbsesionid` int(11) NOT NULL,
    `tbsesionusuarioid` int(11) DEFAULT NULL,
    `tbsesionusuariotipo` varchar(20) DEFAULT NULL,
    `tbsesionfechainicio` datetime DEFAULT NULL,
    `tbsesionfechacierre` datetime DEFAULT NULL,
    `tbsesionactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbsesionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbtipocomponente`
--

CREATE TABLE `tbtipocomponente` (
    `tbtipocomponenteid` int(11) NOT NULL,
    `tbtipocomponentevendedorid` int(11) DEFAULT NULL,
    `tbtipocomponentenombre` varchar(60) DEFAULT NULL,
    `tbtipocomponenteactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbtipocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbtipocomponenteatributo`
--

CREATE TABLE `tbtipocomponenteatributo` (
    `tbtipocomponenteatributoid` int(11) NOT NULL,
    `tbtipocomponenteatributotipocomponenteid` int(11) DEFAULT NULL,
    `tbtipocomponenteatributonombre` varchar(60) DEFAULT NULL,
    `tbtipocomponenteatributoclase` varchar(20) DEFAULT NULL,
    `tbtipocomponenteatributounidad` varchar(20) DEFAULT NULL,
    `tbtipocomponenteatributoobligatorio` tinyint(4) DEFAULT NULL,
    `tbtipocomponenteatributoorden` int(11) DEFAULT NULL,
    `tbtipocomponenteatributoactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbtipocomponenteatributoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbtipocomponenteatributoopcion`
--

CREATE TABLE `tbtipocomponenteatributoopcion` (
    `tbtipocomponenteatributoopcionid` int(11) NOT NULL,
    `tbtipocomponenteatributoopciontipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbtipocomponenteatributoopcionvalor` varchar(60) DEFAULT NULL,
    `tbtipocomponenteatributoopcionactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbtipocomponenteatributoopcionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcomponente`
--

CREATE TABLE `tbcomponente` (
    `tbcomponenteid` int(11) NOT NULL,
    `tbcomponentevendedorid` int(11) DEFAULT NULL,
    `tbcomponentetipocomponenteid` int(11) DEFAULT NULL,
    `tbcomponentenombre` varchar(100) DEFAULT NULL,
    `tbcomponenteunidad` varchar(20) DEFAULT NULL,
    `tbcomponenteexistencia` decimal(12, 2) DEFAULT NULL,
    `tbcomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbcomponenteexistenciaminima` decimal(12, 2) DEFAULT NULL,
    `tbcomponenteactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbcomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcomponentevalor`
--

CREATE TABLE `tbcomponentevalor` (
    `tbcomponentevalorid` int(11) NOT NULL,
    `tbcomponentevalorcomponenteid` int(11) DEFAULT NULL,
    `tbcomponentevalortipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbcomponentevalorvalor` varchar(300) DEFAULT NULL,
    PRIMARY KEY (`tbcomponentevalorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproceso`
--

CREATE TABLE `tbproceso` (
    `tbprocesoid` int(11) NOT NULL,
    `tbprocesovendedorid` int(11) DEFAULT NULL,
    `tbprocesonombre` varchar(60) DEFAULT NULL,
    `tbprocesodescripcion` varchar(200) DEFAULT NULL,
    `tbprocesofecha` date DEFAULT NULL,
    `tbprocesominutos` int(11) DEFAULT NULL,
    `tbprocesotarifahora` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostocomponentes` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostoextras` decimal(12, 2) DEFAULT NULL,
    `tbprocesoprecio` decimal(12, 2) DEFAULT NULL,
    `tbprocesoestado` varchar(20) DEFAULT NULL,
    `tbprocesoregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbprocesoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbprocesocomponente`
--

CREATE TABLE `tbprocesocomponente` (
    `tbprocesocomponenteid` int(11) NOT NULL,
    `tbprocesocomponenteprocesoid` int(11) DEFAULT NULL,
    `tbprocesocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbprocesocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbprocesocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbprocesocomponentesubtotal` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbprocesocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbprocesoextra`
--

CREATE TABLE `tbprocesoextra` (
    `tbprocesoextraid` int(11) NOT NULL,
    `tbprocesoextraprocesoid` int(11) DEFAULT NULL,
    `tbprocesoextraconcepto` varchar(60) DEFAULT NULL,
    `tbprocesoextramonto` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbprocesoextraid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproveedor`
--

CREATE TABLE `tbproveedor` (
    `tbproveedorid` int(11) NOT NULL,
    `tbproveedorvendedorid` int(11) DEFAULT NULL,
    `tbproveedornombre` varchar(50) DEFAULT NULL,
    `tbproveedortelefono` varchar(8) DEFAULT NULL,
    `tbproveedorcorreo` varchar(150) DEFAULT NULL,
    `tbproveedorregistrofecha` datetime DEFAULT NULL,
    `tbproveedoractivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbproveedorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcompraproveedor`
--

CREATE TABLE `tbcompraproveedor` (
    `tbcompraproveedorid` int(11) NOT NULL,
    `tbcompraproveedorvendedorid` int(11) DEFAULT NULL,
    `tbcompraproveedorproveedorid` int(11) DEFAULT NULL,
    `tbcompraproveedorfecha` date DEFAULT NULL,
    `tbcompraproveedorsubtotal` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedorcostoenvio` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedortotal` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedorestado` varchar(20) DEFAULT NULL,
    `tbcompraproveedorregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbcompraproveedorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcompraproveedordetalle`
--

CREATE TABLE `tbcompraproveedordetalle` (
    `tbcompraproveedordetalleid` int(11) NOT NULL,
    `tbcompraproveedordetallecompraproveedorid` int(11) DEFAULT NULL,
    `tbcompraproveedordetallecomponenteid` int(11) DEFAULT NULL,
    `tbcompraproveedordetallecantidad` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedordetallecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbcompraproveedordetallesubtotal` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedordetallecostofinal` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbcompraproveedordetalleid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Datos iniciales: un vendedor con su tienda y un cliente
--

INSERT INTO `tbusuario` (`tbusuarioid`, `tbusuarioidentificaciontipo`, `tbusuarioidentificacionnumero`, `tbusuarionombrecompleto`, `tbusuarioperfilimagen`, `tbusuariocorreo`, `tbusuariotelefono`, `tbusuariocontrasena`, `tbusuariorol`, `tbusuarioregistrofecha`, `tbusuarioactivo`) VALUES
(1, 'Cedula', '200000002', 'Vendedor de Prueba', NULL, 'vendedor.bisubox@gmail.com', '77777777', '$2y$12$cy7xzNTAn0Gl/Ymia8TU1evpuuyjzp4UfwlBRIY0ocJvWYYVhAdzm', 'Vendedor', '2026-10-01 00:00:00', 1),
(2, NULL, NULL, 'Cliente de Prueba', NULL, 'cliente.bisubox@gmail.com', '66666666', '$2y$12$Kag4xPDKWvZgDtsJ8uYUqu71BQoosc2NilsEK4OWX/rr7K0vpYkdK', 'Cliente', '2026-10-01 00:00:00', 1);

INSERT INTO `tbvendedor` (`tbvendedorid`, `tbvendedorusuarioid`, `tbvendedortiendanombre`, `tbvendedortiendaenlace`, `tbvendedortiendadescripcion`, `tbvendedortiendalogo`, `tbvendedoractivo`) VALUES
(1, 1, 'Tienda de Prueba', 'tienda-de-prueba', 'Bisutería hecha a mano para probar el sistema', NULL, 1);

INSERT INTO `tbvendedorcontacto` (`tbvendedorcontactoid`, `tbvendedorcontactovendedorid`, `tbvendedorcontactotipo`, `tbvendedorcontactovalor`) VALUES
(1, 1, 'WhatsApp', '88451290'),
(2, 1, 'Instagram', 'tiendadeprueba');

INSERT INTO `tbcliente` (`tbclienteid`, `tbclienteusuarioid`) VALUES
(1, 2);

INSERT INTO `tbproveedor` (`tbproveedorid`, `tbproveedorvendedorid`, `tbproveedornombre`, `tbproveedortelefono`, `tbproveedorcorreo`, `tbproveedorregistrofecha`, `tbproveedoractivo`) VALUES
(1, 1, 'Perlas del Valle S.A.', '22345678', 'ventas@perlasdelvalle.com', '2026-10-01 00:00:00', 1),
(2, 1, 'Ana Rojas Mora', '87654321', 'anarojas@gmail.com', '2026-10-01 00:00:00', 1);