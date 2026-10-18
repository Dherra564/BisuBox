-- Base de datos de BisuBox

SET NAMES utf8mb4;

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
    PRIMARY KEY (
        `tbtipocomponenteatributoopcionid`
    )
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
    `tbcomponentefoto` varchar(500) DEFAULT NULL,
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
-- Estructura de tabla para la tabla `tbproducto`
--

CREATE TABLE `tbproducto` (
    `tbproductoid` int(11) NOT NULL,
    `tbproductovendedorid` int(11) DEFAULT NULL,
    `tbproductonombre` varchar(60) DEFAULT NULL,
    `tbproductodescripcion` varchar(300) DEFAULT NULL,
    `tbproductofoto` varchar(500) DEFAULT NULL,
    `tbproductominutos` int(11) DEFAULT NULL,
    `tbproductotarifahora` decimal(12, 2) DEFAULT NULL,
    `tbproductocostocomponentes` decimal(12, 2) DEFAULT NULL,
    `tbproductocostomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbproductocostoextras` decimal(12, 2) DEFAULT NULL,
    `tbproductocostototal` decimal(12, 2) DEFAULT NULL,
    `tbproductoprecioventa` decimal(12, 2) DEFAULT NULL,
    `tbproductoexistencia` int(11) DEFAULT NULL,
    `tbproductoregistrofecha` datetime DEFAULT NULL,
    `tbproductoactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbproductoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproductocomponente`
--

CREATE TABLE `tbproductocomponente` (
    `tbproductocomponenteid` int(11) NOT NULL,
    `tbproductocomponenteproductoid` int(11) DEFAULT NULL,
    `tbproductocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbproductocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbproductocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbproductocomponentesubtotal` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbproductocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproductoextra`
--

CREATE TABLE `tbproductoextra` (
    `tbproductoextraid` int(11) NOT NULL,
    `tbproductoextraproductoid` int(11) DEFAULT NULL,
    `tbproductoextraconcepto` varchar(60) DEFAULT NULL,
    `tbproductoextramonto` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbproductoextraid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproduccion`
--

CREATE TABLE `tbproduccion` (
    `tbproduccionid` int(11) NOT NULL,
    `tbproduccionvendedorid` int(11) DEFAULT NULL,
    `tbproduccionproductoid` int(11) DEFAULT NULL,
    `tbproduccioncantidad` int(11) DEFAULT NULL,
    `tbproduccionfecha` date DEFAULT NULL,
    `tbproduccionestado` varchar(20) DEFAULT NULL,
    `tbproduccionregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbproduccionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproduccioncomponente`
--

CREATE TABLE `tbproduccioncomponente` (
    `tbproduccioncomponenteid` int(11) NOT NULL,
    `tbproduccioncomponenteproduccionid` int(11) DEFAULT NULL,
    `tbproduccioncomponentecomponenteid` int(11) DEFAULT NULL,
    `tbproduccioncomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbproduccioncomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbproduccioncomponenteid`)
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
-- Estructura de tabla para la tabla `tbfoto`
--

CREATE TABLE `tbfoto` (
    `tbfotoid` int(11) NOT NULL,
    `tbfotocarpeta` varchar(20) DEFAULT NULL,
    `tbfotonombre` varchar(40) DEFAULT NULL,
    `tbfototipo` varchar(20) DEFAULT NULL,
    `tbfotodatos` mediumblob DEFAULT NULL,
    `tbfotoregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbfotoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcarrito`
-- Lo que cada cliente tiene en el carrito. Se guarda aquí para que no se pierda
-- al cerrar sesión o cambiar de computadora
--

CREATE TABLE `tbcarrito` (
    `tbcarritoid` int(11) NOT NULL,
    `tbcarritoclienteid` int(11) DEFAULT NULL,
    `tbcarritovendedorid` int(11) DEFAULT NULL,
    `tbcarritoproductoid` int(11) DEFAULT NULL,
    `tbcarritocantidad` int(11) DEFAULT NULL,
    `tbcarritoagregadofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbcarritoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbpedido`
-- Un pedido es de un solo cliente a una sola tienda
--

CREATE TABLE `tbpedido` (
    `tbpedidoid` int(11) NOT NULL,
    `tbpedidovendedorid` int(11) DEFAULT NULL,
    `tbpedidoclienteid` int(11) DEFAULT NULL,
    `tbpedidofecha` datetime DEFAULT NULL,
    `tbpedidoentrega` varchar(20) DEFAULT NULL,
    `tbpedidodireccion` varchar(250) DEFAULT NULL,
    `tbpedidometodopago` varchar(20) DEFAULT NULL,
    `tbpedidonota` varchar(300) DEFAULT NULL,
    `tbpedidototal` decimal(12, 2) DEFAULT NULL,
    `tbpedidoestado` varchar(20) DEFAULT NULL,
    `tbpedidocanceladopor` varchar(20) DEFAULT NULL,
    `tbpedidoactualizacionfecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbpedidoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbpedidodetalle`
-- Se guarda el nombre y el precio del momento, por si el producto cambia después
--

CREATE TABLE `tbpedidodetalle` (
    `tbpedidodetalleid` int(11) NOT NULL,
    `tbpedidodetallepedidoid` int(11) DEFAULT NULL,
    `tbpedidodetalleproductoid` int(11) DEFAULT NULL,
    `tbpedidodetallenombre` varchar(60) DEFAULT NULL,
    `tbpedidodetallecantidad` int(11) DEFAULT NULL,
    `tbpedidodetalleprecio` decimal(12, 2) DEFAULT NULL,
    `tbpedidodetallesubtotal` decimal(12, 2) DEFAULT NULL,
    `tbpedidodetallepersonalizadoid` int(11) DEFAULT NULL,
    PRIMARY KEY (`tbpedidodetalleid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbpersonalizado`
--

CREATE TABLE `tbpersonalizado` (
    `tbpersonalizadoid` int(11) NOT NULL,
    `tbpersonalizadovendedorid` int(11) DEFAULT NULL,
    `tbpersonalizadoclienteid` int(11) DEFAULT NULL,
    `tbpersonalizadoproductoid` int(11) DEFAULT NULL,
    `tbpersonalizadonombre` varchar(60) DEFAULT NULL,
    `tbpersonalizadopiezas` int(11) DEFAULT NULL,
    `tbpersonalizadonotacliente` varchar(300) DEFAULT NULL,
    `tbpersonalizadoestado` varchar(20) DEFAULT NULL,
    `tbpersonalizadominutos` int(11) DEFAULT NULL,
    `tbpersonalizadotarifahora` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocostocomponentes` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocostomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocostoextras` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocostototal` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadoganancia` int(11) DEFAULT NULL,
    `tbpersonalizadoprecio` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadototal` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadonotatienda` varchar(300) DEFAULT NULL,
    `tbpersonalizadocanceladopor` varchar(20) DEFAULT NULL,
    `tbpersonalizadoavisovisto` tinyint(4) DEFAULT NULL,
    `tbpersonalizadopedidoid` int(11) DEFAULT NULL,
    `tbpersonalizadoregistrofecha` datetime DEFAULT NULL,
    `tbpersonalizadocotizadofecha` datetime DEFAULT NULL,
    `tbpersonalizadorespuestafecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbpersonalizadocomponente`
--

CREATE TABLE `tbpersonalizadocomponente` (
    `tbpersonalizadocomponenteid` int(11) NOT NULL,
    `tbpersonalizadocomponentepersonalizadoid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponenteoriginalid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbpersonalizadoextra`
--

CREATE TABLE `tbpersonalizadoextra` (
    `tbpersonalizadoextraid` int(11) NOT NULL,
    `tbpersonalizadoextrapersonalizadoid` int(11) DEFAULT NULL,
    `tbpersonalizadoextraconcepto` varchar(60) DEFAULT NULL,
    `tbpersonalizadoextramonto` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadoextraid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Datos iniciales: un vendedor con su tienda y un cliente
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
        '200000002',
        'Vendedor de Prueba',
        NULL,
        'vendedor.bisubox@gmail.com',
        '77777777',
        '$2y$12$cy7xzNTAn0Gl/Ymia8TU1evpuuyjzp4UfwlBRIY0ocJvWYYVhAdzm',
        'Vendedor',
        '2026-10-01 00:00:00',
        1
    ),
    (
        2,
        NULL,
        NULL,
        'Cliente de Prueba',
        NULL,
        'cliente.bisubox@gmail.com',
        '66666666',
        '$2y$12$Kag4xPDKWvZgDtsJ8uYUqu71BQoosc2NilsEK4OWX/rr7K0vpYkdK',
        'Cliente',
        '2026-10-01 00:00:00',
        1
    );

INSERT INTO
    `tbvendedor` (
        `tbvendedorid`,
        `tbvendedorusuarioid`,
        `tbvendedortiendanombre`,
        `tbvendedortiendaenlace`,
        `tbvendedortiendadescripcion`,
        `tbvendedortiendalogo`,
        `tbvendedoractivo`
    )
VALUES (
        1,
        1,
        'Tienda de Prueba',
        'tienda-de-prueba',
        'Bisutería hecha a mano para probar el sistema',
        NULL,
        1
    );

INSERT INTO
    `tbvendedorcontacto` (
        `tbvendedorcontactoid`,
        `tbvendedorcontactovendedorid`,
        `tbvendedorcontactotipo`,
        `tbvendedorcontactovalor`
    )
VALUES (1, 1, 'WhatsApp', '88451290'),
    (
        2,
        1,
        'Instagram',
        'tiendadeprueba'
    );

INSERT INTO
    `tbcliente` (
        `tbclienteid`,
        `tbclienteusuarioid`
    )
VALUES (1, 2);

INSERT INTO
    `tbproveedor` (
        `tbproveedorid`,
        `tbproveedorvendedorid`,
        `tbproveedornombre`,
        `tbproveedortelefono`,
        `tbproveedorcorreo`,
        `tbproveedorregistrofecha`,
        `tbproveedoractivo`
    )
VALUES (
        1,
        1,
        'Perlas del Valle S.A.',
        '22345678',
        'ventas@perlasdelvalle.com',
        '2026-10-01 00:00:00',
        1
    ),
    (
        2,
        1,
        'Ana Rojas Mora',
        '87654321',
        'anarojas@gmail.com',
        '2026-10-01 00:00:00',
        1
    );