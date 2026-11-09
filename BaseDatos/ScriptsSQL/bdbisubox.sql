SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `bdbisubox`;

CREATE DATABASE `bdbisubox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bdbisubox`;

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

CREATE TABLE `tbvendedor` (
    `tbvendedorid` int(11) NOT NULL,
    `tbvendedorusuarioid` int(11) DEFAULT NULL,
    `tbvendedortiendanombre` varchar(100) DEFAULT NULL,
    `tbvendedortiendaenlace` varchar(60) DEFAULT NULL,
    `tbvendedortiendadescripcion` varchar(300) DEFAULT NULL,
    `tbvendedortiendalogo` varchar(500) DEFAULT NULL,
    `tbvendedorsinpe` varchar(8) DEFAULT NULL,
    `tbvendedoractivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbvendedorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbvendedorcontacto` (
    `tbvendedorcontactoid` int(11) NOT NULL,
    `tbvendedorcontactovendedorid` int(11) DEFAULT NULL,
    `tbvendedorcontactotipo` varchar(20) DEFAULT NULL,
    `tbvendedorcontactovalor` varchar(300) DEFAULT NULL,
    PRIMARY KEY (`tbvendedorcontactoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbcliente` (
    `tbclienteid` int(11) NOT NULL,
    `tbclienteusuarioid` int(11) DEFAULT NULL,
    PRIMARY KEY (`tbclienteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbsesion` (
    `tbsesionid` int(11) NOT NULL,
    `tbsesionusuarioid` int(11) DEFAULT NULL,
    `tbsesionusuariotipo` varchar(20) DEFAULT NULL,
    `tbsesionfechainicio` datetime DEFAULT NULL,
    `tbsesionfechacierre` datetime DEFAULT NULL,
    `tbsesionactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbsesionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbrecuperacion` (
    `tbrecuperacionid` int(11) NOT NULL,
    `tbrecuperacionusuarioid` int(11) DEFAULT NULL,
    `tbrecuperacioncodigohash` varchar(64) DEFAULT NULL,
    `tbrecuperacionvencimiento` datetime DEFAULT NULL,
    `tbrecuperacionusado` tinyint(4) DEFAULT NULL,
    `tbrecuperacionregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbrecuperacionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbfoto` (
    `tbfotoid` int(11) NOT NULL,
    `tbfotocarpeta` varchar(20) DEFAULT NULL,
    `tbfotonombre` varchar(40) DEFAULT NULL,
    `tbfototipo` varchar(20) DEFAULT NULL,
    `tbfotodatos` mediumblob DEFAULT NULL,
    `tbfotoregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbfotoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbtipocomponente` (
    `tbtipocomponenteid` int(11) NOT NULL,
    `tbtipocomponentevendedorid` int(11) DEFAULT NULL,
    `tbtipocomponentenombre` varchar(60) DEFAULT NULL,
    `tbtipocomponenteactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbtipocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE `tbtipocomponenteatributoopcion` (
    `tbtipocomponenteatributoopcionid` int(11) NOT NULL,
    `tbtipocomponenteatributoopciontipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbtipocomponenteatributoopcionvalor` varchar(60) DEFAULT NULL,
    `tbtipocomponenteatributoopcionactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbtipocomponenteatributoopcionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE `tbcomponentevalor` (
    `tbcomponentevalorid` int(11) NOT NULL,
    `tbcomponentevalorcomponenteid` int(11) DEFAULT NULL,
    `tbcomponentevalortipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbcomponentevalorvalor` varchar(300) DEFAULT NULL,
    PRIMARY KEY (`tbcomponentevalorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbcomponentevista` (
    `tbcomponentevistaid` int(11) NOT NULL,
    `tbcomponentevistavendedorid` int(11) DEFAULT NULL,
    `tbcomponentevistacomponenteid` int(11) DEFAULT NULL,
    `tbcomponentevistafoto` varchar(500) DEFAULT NULL,
    `tbcomponentevistatamano` decimal(6, 1) DEFAULT NULL,
    `tbcomponentevistaregistrofecha` datetime DEFAULT NULL,
    `tbcomponentevistaactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbcomponentevistaid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE `tbproductocomponente` (
    `tbproductocomponenteid` int(11) NOT NULL,
    `tbproductocomponenteproductoid` int(11) DEFAULT NULL,
    `tbproductocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbproductocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbproductocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbproductocomponentesubtotal` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbproductocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbproductoextra` (
    `tbproductoextraid` int(11) NOT NULL,
    `tbproductoextraproductoid` int(11) DEFAULT NULL,
    `tbproductoextraconcepto` varchar(60) DEFAULT NULL,
    `tbproductoextramonto` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbproductoextraid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE `tbproduccioncomponente` (
    `tbproduccioncomponenteid` int(11) NOT NULL,
    `tbproduccioncomponenteproduccionid` int(11) DEFAULT NULL,
    `tbproduccioncomponentecomponenteid` int(11) DEFAULT NULL,
    `tbproduccioncomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbproduccioncomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbproduccioncomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbpersonalizador` (
    `tbpersonalizadorid` int(11) NOT NULL,
    `tbpersonalizadorvendedorid` int(11) DEFAULT NULL,
    `tbpersonalizadorhilocomponenteid` int(11) DEFAULT NULL,
    `tbpersonalizadormanoobra` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadorganancia` decimal(6, 2) DEFAULT NULL,
    `tbpersonalizadoractivo` tinyint(4) DEFAULT NULL,
    `tbpersonalizadoractualizacionfecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadorid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbdiseno` (
    `tbdisenoid` int(11) NOT NULL,
    `tbdisenoclienteid` int(11) DEFAULT NULL,
    `tbdisenovendedorid` int(11) DEFAULT NULL,
    `tbdisenotipopieza` varchar(20) DEFAULT NULL,
    `tbdisenolargo` decimal(6, 1) DEFAULT NULL,
    `tbdisenosimetrico` tinyint(4) DEFAULT NULL,
    `tbdisenohilocomponenteid` int(11) DEFAULT NULL,
    `tbdisenohilonombre` varchar(100) DEFAULT NULL,
    `tbdisenohilocosto` decimal(14, 4) DEFAULT NULL,
    `tbdisenomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbdisenoganancia` decimal(6, 2) DEFAULT NULL,
    `tbdisenocosto` decimal(12, 2) DEFAULT NULL,
    `tbdisenoprecio` decimal(12, 2) DEFAULT NULL,
    `tbdisenoestado` varchar(20) DEFAULT NULL,
    `tbdisenoregistrofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbdisenoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbdisenodetalle` (
    `tbdisenodetalleid` int(11) NOT NULL,
    `tbdisenodetalledisenoid` int(11) DEFAULT NULL,
    `tbdisenodetallecomponenteid` int(11) DEFAULT NULL,
    `tbdisenodetalleposicion` int(11) DEFAULT NULL,
    `tbdisenodetallenombre` varchar(100) DEFAULT NULL,
    `tbdisenodetallefoto` varchar(500) DEFAULT NULL,
    `tbdisenodetalletamano` decimal(6, 1) DEFAULT NULL,
    `tbdisenodetallecostounitario` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbdisenodetalleid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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

CREATE TABLE `tbpersonalizadocomponente` (
    `tbpersonalizadocomponenteid` int(11) NOT NULL,
    `tbpersonalizadocomponentepersonalizadoid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponenteoriginalid` int(11) DEFAULT NULL,
    `tbpersonalizadocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbpersonalizadocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadocomponenteid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbpersonalizadoextra` (
    `tbpersonalizadoextraid` int(11) NOT NULL,
    `tbpersonalizadoextrapersonalizadoid` int(11) DEFAULT NULL,
    `tbpersonalizadoextraconcepto` varchar(60) DEFAULT NULL,
    `tbpersonalizadoextramonto` decimal(12, 2) DEFAULT NULL,
    PRIMARY KEY (`tbpersonalizadoextraid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbcarrito` (
    `tbcarritoid` int(11) NOT NULL,
    `tbcarritoclienteid` int(11) DEFAULT NULL,
    `tbcarritovendedorid` int(11) DEFAULT NULL,
    `tbcarritoproductoid` int(11) DEFAULT NULL,
    `tbcarritocantidad` int(11) DEFAULT NULL,
    `tbcarritoagregadofecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbcarritoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

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
    `tbpedidoretirocodigo` varchar(6) DEFAULT NULL,
    PRIMARY KEY (`tbpedidoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbpedidodetalle` (
    `tbpedidodetalleid` int(11) NOT NULL,
    `tbpedidodetallepedidoid` int(11) DEFAULT NULL,
    `tbpedidodetalleproductoid` int(11) DEFAULT NULL,
    `tbpedidodetallenombre` varchar(60) DEFAULT NULL,
    `tbpedidodetallecantidad` int(11) DEFAULT NULL,
    `tbpedidodetalleprecio` decimal(12, 2) DEFAULT NULL,
    `tbpedidodetallesubtotal` decimal(12, 2) DEFAULT NULL,
    `tbpedidodetallepersonalizadoid` int(11) DEFAULT NULL,
    `tbpedidodetalledisenoid` int(11) DEFAULT NULL,
    `tbpedidodetallecotizacionid` int(11) DEFAULT NULL,
    PRIMARY KEY (`tbpedidodetalleid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbpedidopago` (
    `tbpedidopagoid` int(11) NOT NULL,
    `tbpedidopagopedidoid` int(11) DEFAULT NULL,
    `tbpedidopagonumerosinpe` varchar(8) DEFAULT NULL,
    `tbpedidopagocodigo` varchar(10) DEFAULT NULL,
    `tbpedidopagoreferencia` varchar(4) DEFAULT NULL,
    `tbpedidopagocomprobante` varchar(40) DEFAULT NULL,
    `tbpedidopagomonto` decimal(12, 2) DEFAULT NULL,
    `tbpedidopagoestado` varchar(20) DEFAULT NULL,
    `tbpedidopagomotivo` varchar(300) DEFAULT NULL,
    `tbpedidopagointentos` int(11) DEFAULT NULL,
    `tbpedidopagoregistrofecha` datetime DEFAULT NULL,
    `tbpedidopagorevisionfecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbpedidopagoid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbcotizacion` (
    `tbcotizacionid` int(11) NOT NULL,
    `tbcotizacionclienteid` int(11) DEFAULT NULL,
    `tbcotizacionvendedorid` int(11) DEFAULT NULL,
    `tbcotizacionfoto` varchar(500) DEFAULT NULL,
    `tbcotizaciondescripcion` varchar(300) DEFAULT NULL,
    `tbcotizacionrespuesta` varchar(300) DEFAULT NULL,
    `tbcotizacionmanoobra` decimal(12, 2) DEFAULT NULL,
    `tbcotizacionprecio` decimal(12, 2) DEFAULT NULL,
    `tbcotizacionestado` varchar(20) DEFAULT NULL,
    `tbcotizacionpedidoid` int(11) DEFAULT NULL,
    `tbcotizacionregistrofecha` datetime DEFAULT NULL,
    `tbcotizacionrespuestafecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbcotizacionid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbcotizaciondetalle` (
    `tbcotizaciondetalleid` int(11) NOT NULL,
    `tbcotizaciondetallecotizacionid` int(11) DEFAULT NULL,
    `tbcotizaciondetallecomponenteid` int(11) DEFAULT NULL,
    `tbcotizaciondetallecantidad` decimal(12, 2) DEFAULT NULL,
    `tbcotizaciondetallecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbcotizaciondetallesubtotal` decimal(12, 2) DEFAULT NULL,
    `tbcotizaciondetalleactivo` tinyint(4) DEFAULT NULL,
    PRIMARY KEY (`tbcotizaciondetalleid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbventa` (
    `tbventaid` int(11) NOT NULL,
    `tbventavendedorid` int(11) DEFAULT NULL,
    `tbventaclienteid` int(11) DEFAULT NULL,
    `tbventapedidoid` int(11) DEFAULT NULL,
    `tbventatipo` varchar(20) DEFAULT NULL,
    `tbventafecha` datetime DEFAULT NULL,
    `tbventatotal` decimal(12, 2) DEFAULT NULL,
    `tbventasaldo` decimal(12, 2) DEFAULT NULL,
    `tbventavencimiento` date DEFAULT NULL,
    `tbventaestadopago` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`tbventaid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `tbabonoventa` (
    `tbabonoventaid` int(11) NOT NULL,
    `tbabonoventaventaid` int(11) DEFAULT NULL,
    `tbabonoventamonto` decimal(12, 2) DEFAULT NULL,
    `tbabonoventametodopago` varchar(20) DEFAULT NULL,
    `tbabonoventanota` varchar(300) DEFAULT NULL,
    `tbabonoventafecha` datetime DEFAULT NULL,
    PRIMARY KEY (`tbabonoventaid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `tbusuario` (`tbusuarioid`, `tbusuarioidentificaciontipo`, `tbusuarioidentificacionnumero`, `tbusuarionombrecompleto`, `tbusuarioperfilimagen`, `tbusuariocorreo`, `tbusuariotelefono`, `tbusuariocontrasena`, `tbusuariorol`, `tbusuarioregistrofecha`, `tbusuarioactivo`)
VALUES
(1, 'Cedula', '200000002', 'Vendedor de Prueba', NULL, 'vendedor-bisubox@gmail.com', '77777777', '$2y$12$cy7xzNTAn0Gl/Ymia8TU1evpuuyjzp4UfwlBRIY0ocJvWYYVhAdzm', 'Vendedor', '2026-10-01 00:00:00', 1),
(2, NULL, NULL, 'Cliente de Prueba', NULL, 'cliente-bisubox@gmail.com', '66666666', '$2y$12$Kag4xPDKWvZgDtsJ8uYUqu71BQoosc2NilsEK4OWX/rr7K0vpYkdK', 'Cliente', '2026-10-01 00:00:00', 1),
(3, 'Cedula', '304560789', 'Luna Vargas Mora', NULL, 'vendedor@gmail.com', '71234567', '$2y$12$EweQabsPMmcSBbpOBhdzDe4q5Ig6JKLHlxq6agjAl8Na3RBYSs9LG', 'Vendedor', '2026-10-02 00:00:00', 1),
(4, NULL, NULL, 'Carlos Comprador Rojas', NULL, 'comprador@gmail.com', '62345678', '$2y$12$elAA.scsUup07yc2BMm0cuteSvozpZi0Zj9NH7rPt4G2XJIeotkzq', 'Cliente', '2026-10-02 00:00:00', 1);

INSERT INTO `tbvendedor` (`tbvendedorid`, `tbvendedorusuarioid`, `tbvendedortiendanombre`, `tbvendedortiendaenlace`, `tbvendedortiendadescripcion`, `tbvendedortiendalogo`, `tbvendedorsinpe`, `tbvendedoractivo`)
VALUES
(1, 1, 'Tienda de Prueba', 'tienda-de-prueba', 'Bisutería hecha a mano para probar el sistema', NULL, '88451290', 1),
(2, 3, 'Joyería Luna', 'joyeria-luna', 'Accesorios artesanales y diseños a pedido', NULL, '71234567', 1);

INSERT INTO `tbvendedorcontacto` (`tbvendedorcontactoid`, `tbvendedorcontactovendedorid`, `tbvendedorcontactotipo`, `tbvendedorcontactovalor`)
VALUES
(1, 1, 'WhatsApp', '88451290'),
(2, 1, 'Instagram', 'tiendadeprueba'),
(3, 2, 'WhatsApp', '71234567'),
(4, 2, 'Instagram', 'joyerialuna');

INSERT INTO `tbcliente` (`tbclienteid`, `tbclienteusuarioid`)
VALUES (1, 2), (2, 4);

INSERT INTO `tbproveedor` (`tbproveedorid`, `tbproveedorvendedorid`, `tbproveedornombre`, `tbproveedortelefono`, `tbproveedorcorreo`, `tbproveedorregistrofecha`, `tbproveedoractivo`)
VALUES
(1, 1, 'Perlas del Valle S.A.', '22345678', 'ventas@perlasdelvalle.com', '2026-10-01 00:00:00', 1),
(2, 1, 'Ana Rojas Mora', '87654321', 'anarojas@gmail.com', '2026-10-01 00:00:00', 1),
(3, 2, 'Bisutería Central', '24567890', 'pedidos@bisuteriacentral.com', '2026-10-02 00:00:00', 1);

INSERT INTO `tbtipocomponente` (`tbtipocomponenteid`, `tbtipocomponentevendedorid`, `tbtipocomponentenombre`, `tbtipocomponenteactivo`)
VALUES
(101, 1, 'Perlas y cuentas', 1),
(102, 1, 'Cadenas e hilos', 1),
(103, 1, 'Herrajes', 1),
(201, 2, 'Piedras', 1);

INSERT INTO `tbcomponente` (`tbcomponenteid`, `tbcomponentevendedorid`, `tbcomponentetipocomponenteid`, `tbcomponentenombre`, `tbcomponentefoto`, `tbcomponenteunidad`, `tbcomponenteexistencia`, `tbcomponentecostounitario`, `tbcomponenteexistenciaminima`, `tbcomponenteactivo`)
VALUES
(101, 1, 101, 'Perla blanca 8 mm', NULL, 'Unidad', 200.00, 150.0000, 20.00, 1),
(102, 1, 102, 'Cadena dorada', NULL, 'Metro', 30.00, 1200.0000, 5.00, 1),
(103, 1, 103, 'Broche de langosta', NULL, 'Unidad', 80.00, 300.0000, 10.00, 1),
(104, 1, 102, 'Hilo elástico transparente', NULL, 'Metro', 50.00, 250.0000, 5.00, 1),
(105, 1, 101, 'Cuenta de cristal rosa 6 mm', NULL, 'Unidad', 150.00, 90.0000, 20.00, 1),
(106, 1, 103, 'Dije de corazón dorado', NULL, 'Unidad', 12.00, 450.0000, 5.00, 1),
(107, 1, 103, 'Argolla dorada 5 mm', NULL, 'Unidad', 8.00, 40.0000, 20.00, 1),
(201, 2, 201, 'Cuarzo rosa 10 mm', NULL, 'Unidad', 60.00, 350.0000, 10.00, 1),
(202, 2, 201, 'Hilo encerado café', NULL, 'Metro', 20.00, 300.0000, 3.00, 1);

INSERT INTO `tbproducto` (`tbproductoid`, `tbproductovendedorid`, `tbproductonombre`, `tbproductodescripcion`, `tbproductofoto`, `tbproductominutos`, `tbproductotarifahora`, `tbproductocostocomponentes`, `tbproductocostomanoobra`, `tbproductocostoextras`, `tbproductocostototal`, `tbproductoprecioventa`, `tbproductoexistencia`, `tbproductoregistrofecha`, `tbproductoactivo`)
VALUES
(1, 1, 'Collar de perlas clásico', 'Collar de 45 cm con perlas blancas y broche de langosta', NULL, 60, 2000.00, 6840.00, 2000.00, 0.00, 8840.00, 13500.00, 3, '2026-10-05 10:00:00', 1),
(2, 1, 'Pulsera rosa de cristal', 'Pulsera elástica con cuentas de cristal rosa', NULL, 30, 2000.00, 2762.50, 1000.00, 300.00, 4062.50, 6500.00, 5, '2026-10-05 10:10:00', 1),
(3, 1, 'Collar corazón dorado', 'Cadena dorada con dije de corazón', NULL, 20, 2000.00, 1370.00, 666.67, 0.00, 2036.67, 4500.00, 0, '2026-10-05 10:20:00', 1),
(4, 1, 'Pulsera de perlas', 'Pulsera elástica de perlas blancas', NULL, 25, 2000.00, 3350.00, 833.33, 0.00, 4183.33, 7000.00, 4, '2026-10-05 10:30:00', 1),
(201, 2, 'Pulsera de cuarzo rosa', 'Pulsera ajustable de cuarzo rosa con hilo encerado', NULL, 30, 1800.00, 5690.00, 900.00, 0.00, 6590.00, 9500.00, 2, '2026-10-06 09:00:00', 1);

INSERT INTO `tbproductocomponente` (`tbproductocomponenteid`, `tbproductocomponenteproductoid`, `tbproductocomponentecomponenteid`, `tbproductocomponentecantidad`, `tbproductocomponentecostounitario`, `tbproductocomponentesubtotal`)
VALUES
(1, 1, 101, 40.00, 150.0000, 6000.00),
(2, 1, 102, 0.45, 1200.0000, 540.00),
(3, 1, 103, 1.00, 300.0000, 300.00),
(4, 2, 105, 30.00, 90.0000, 2700.00),
(5, 2, 104, 0.25, 250.0000, 62.50),
(6, 3, 102, 0.45, 1200.0000, 540.00),
(7, 3, 106, 1.00, 450.0000, 450.00),
(8, 3, 103, 1.00, 300.0000, 300.00),
(9, 3, 107, 2.00, 40.0000, 80.00),
(10, 4, 101, 22.00, 150.0000, 3300.00),
(11, 4, 104, 0.20, 250.0000, 50.00),
(12, 201, 201, 16.00, 350.0000, 5600.00),
(13, 201, 202, 0.30, 300.0000, 90.00);

INSERT INTO `tbproductoextra` (`tbproductoextraid`, `tbproductoextraproductoid`, `tbproductoextraconcepto`, `tbproductoextramonto`)
VALUES (1, 2, 'Empaque', 300.00);

INSERT INTO `tbcotizacion` (`tbcotizacionid`, `tbcotizacionclienteid`, `tbcotizacionvendedorid`, `tbcotizacionfoto`, `tbcotizaciondescripcion`, `tbcotizacionrespuesta`, `tbcotizacionmanoobra`, `tbcotizacionprecio`, `tbcotizacionestado`, `tbcotizacionpedidoid`, `tbcotizacionregistrofecha`, `tbcotizacionrespuestafecha`)
VALUES
(101, 1, 1, NULL, 'Collar de perlas con cadena dorada, largo 45 cm', NULL, NULL, NULL, 'Enviada', NULL, '2026-11-03 09:00:00', NULL),
(102, 1, 1, NULL, 'Pulsera de perlas con cadena dorada fina', 'Se puede hacer en 3 días hábiles', 2000.00, 4100.00, 'Respondida', NULL, '2026-11-03 10:00:00', '2026-11-04 08:30:00'),
(103, 2, 1, NULL, 'Gargantilla de perlas con broche dorado', 'Lista en una semana', 3000.00, 6600.00, 'Aceptada', 101, '2026-11-02 11:00:00', '2026-11-03 09:15:00'),
(104, 2, 1, NULL, 'Set de aretes y collar de perlas grandes', 'Sin perlas de ese tamaño por ahora', 2500.00, 5500.00, 'Rechazada', NULL, '2026-11-01 14:00:00', '2026-11-02 10:00:00'),
(105, 1, 1, NULL, 'Tobillera con perlas pequeñas', NULL, NULL, NULL, 'Cancelada', NULL, '2026-11-01 16:00:00', NULL);

INSERT INTO `tbcotizaciondetalle` (`tbcotizaciondetalleid`, `tbcotizaciondetallecotizacionid`, `tbcotizaciondetallecomponenteid`, `tbcotizaciondetallecantidad`, `tbcotizaciondetallecostounitario`, `tbcotizaciondetallesubtotal`, `tbcotizaciondetalleactivo`)
VALUES
(101, 102, 101, 10.00, 150.0000, 1500.00, 1),
(102, 102, 102, 0.50, 1200.0000, 600.00, 1),
(103, 103, 101, 20.00, 150.0000, 3000.00, 1),
(104, 103, 103, 2.00, 300.0000, 600.00, 1),
(105, 104, 101, 12.00, 150.0000, 1800.00, 1),
(106, 104, 102, 1.00, 1200.0000, 1200.00, 1);

INSERT INTO `tbpedido` (`tbpedidoid`, `tbpedidovendedorid`, `tbpedidoclienteid`, `tbpedidofecha`, `tbpedidoentrega`, `tbpedidodireccion`, `tbpedidometodopago`, `tbpedidonota`, `tbpedidototal`, `tbpedidoestado`, `tbpedidocanceladopor`, `tbpedidoactualizacionfecha`, `tbpedidoretirocodigo`)
VALUES
(101, 1, 2, '2026-11-03 09:15:00', 'Retiro', NULL, 'Efectivo', 'Cotización aceptada', 6600.00, 'Entregado', NULL, '2026-11-05 16:00:00', 'K7M2QX'),
(102, 1, 1, '2026-11-06 14:20:00', 'Envio', 'Heredia, San Pablo, 100 m norte de la iglesia, casa blanca', 'Efectivo', NULL, 20000.00, 'Confirmado', NULL, NULL, NULL),
(103, 1, 1, '2026-11-01 10:00:00', 'Retiro', NULL, 'Transferencia', NULL, 13500.00, 'Entregado', NULL, '2026-11-02 11:30:00', 'P3H8TA');

INSERT INTO `tbpedidodetalle` (`tbpedidodetalleid`, `tbpedidodetallepedidoid`, `tbpedidodetalleproductoid`, `tbpedidodetallenombre`, `tbpedidodetallecantidad`, `tbpedidodetalleprecio`, `tbpedidodetallesubtotal`, `tbpedidodetallepersonalizadoid`, `tbpedidodetalledisenoid`, `tbpedidodetallecotizacionid`)
VALUES
(101, 101, NULL, 'Pieza por cotización #103', 1, 6600.00, 6600.00, NULL, NULL, 103),
(102, 102, 2, 'Pulsera rosa de cristal', 2, 6500.00, 13000.00, NULL, NULL, NULL),
(103, 102, 4, 'Pulsera de perlas', 1, 7000.00, 7000.00, NULL, NULL, NULL),
(104, 103, 1, 'Collar de perlas clásico', 1, 13500.00, 13500.00, NULL, NULL, NULL);

INSERT INTO `tbventa` (`tbventaid`, `tbventavendedorid`, `tbventaclienteid`, `tbventapedidoid`, `tbventatipo`, `tbventafecha`, `tbventatotal`, `tbventasaldo`, `tbventavencimiento`, `tbventaestadopago`)
VALUES
(1, 1, 2, 101, 'Personalizado', '2026-11-05 16:00:00', 6600.00, 3600.00, '2026-12-15', 'Por cobrar'),
(2, 1, 1, 103, 'Producto hecho', '2026-11-02 11:30:00', 13500.00, 0.00, NULL, 'Pagada');

INSERT INTO `tbabonoventa` (`tbabonoventaid`, `tbabonoventaventaid`, `tbabonoventamonto`, `tbabonoventametodopago`, `tbabonoventanota`, `tbabonoventafecha`)
VALUES
(1, 1, 3000.00, 'Efectivo', 'Adelanto al recoger', '2026-11-05 16:00:00'),
(2, 2, 13500.00, 'Transferencia', 'Pagado al entregar el pedido', '2026-11-02 11:30:00');