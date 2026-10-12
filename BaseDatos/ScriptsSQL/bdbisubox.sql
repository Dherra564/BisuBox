-- Base de datos de BisuBox
-- Módulo 1: usuarios (vendedores y clientes), tiendas, contactos y sesiones
-- Módulo 2: tipos de componente con sus datos, componentes (inventario), procesos, proveedores y compras a proveedores
-- Módulo 2: tipos de componente con sus datos, componentes (inventario), proveedores y compras a proveedores
-- Módulo 3: procesos (pasos con que el vendedor hace sus productos)
-- Las contraseñas de los usuarios por defecto están en el README

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
-- Estructura de tabla para la tabla `tbtipocomponente`
-- Tipos de componente que crea cada vendedor (Perlas, Dijes, Hilos...)
--

CREATE TABLE `tbtipocomponente` (
    `tbtipocomponenteid` int(11) NOT NULL,
    `tbtipocomponentevendedorid` int(11) DEFAULT NULL,
    `tbtipocomponentenombre` varchar(60) DEFAULT NULL,
    `tbtipocomponenteactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbtipocomponenteatributo`
-- Datos que pide cada tipo; la clase es Texto, Numero, Lista o SiNo
-- La unidad solo se usa en los de clase Numero (mm, cm...)
--

CREATE TABLE `tbtipocomponenteatributo` (
    `tbtipocomponenteatributoid` int(11) NOT NULL,
    `tbtipocomponenteatributotipocomponenteid` int(11) DEFAULT NULL,
    `tbtipocomponenteatributonombre` varchar(60) DEFAULT NULL,
    `tbtipocomponenteatributoclase` varchar(20) DEFAULT NULL,
    `tbtipocomponenteatributounidad` varchar(20) DEFAULT NULL,
    `tbtipocomponenteatributoobligatorio` tinyint(4) DEFAULT NULL,
    `tbtipocomponenteatributoorden` int(11) DEFAULT NULL,
    `tbtipocomponenteatributoactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbtipocomponenteatributoopcion`
-- Opciones de los datos de clase Lista
--

CREATE TABLE `tbtipocomponenteatributoopcion` (
    `tbtipocomponenteatributoopcionid` int(11) NOT NULL,
    `tbtipocomponenteatributoopciontipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbtipocomponenteatributoopcionvalor` varchar(60) DEFAULT NULL,
    `tbtipocomponenteatributoopcionactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcomponente`
-- Datos comunes de todo componente; la unidad de medida es Unidad, Metro o Gramo
-- La existencia solo cambia con compras y producción
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
    `tbcomponenteactivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcomponentevalor`
-- Valor de cada dato del tipo en un componente, siempre como texto
-- En Lista se guarda el id de la opción y en SiNo un 1 o un 0
--

CREATE TABLE `tbcomponentevalor` (
    `tbcomponentevalorid` int(11) NOT NULL,
    `tbcomponentevalorcomponenteid` int(11) DEFAULT NULL,
    `tbcomponentevalortipocomponenteatributoid` int(11) DEFAULT NULL,
    `tbcomponentevalorvalor` varchar(300) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproceso`
-- Pasos con que el vendedor hace sus productos; el tiempo estimado va en minutos
-- Cada vez que el vendedor elabora un producto (collar, aretes...); el precio se calcula solo
-- precio = costo de componentes + mano de obra (minutos / 60 * tarifa por hora) + costos extra
-- El estado es Registrado o Anulado
--

CREATE TABLE `tbproceso` (
    `tbprocesoid` int(11) NOT NULL,
    `tbprocesovendedorid` int(11) DEFAULT NULL,
    `tbprocesonombre` varchar(60) DEFAULT NULL,
    `tbprocesodescripcion` varchar(200) DEFAULT NULL,
    `tbprocesotiempoestimado` int(11) DEFAULT NULL,
    `tbprocesocostomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbprocesoactivo` tinyint(4) DEFAULT NULL
    `tbprocesofecha` date DEFAULT NULL,
    `tbprocesominutos` int(11) DEFAULT NULL,
    `tbprocesotarifahora` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostocomponentes` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostomanoobra` decimal(12, 2) DEFAULT NULL,
    `tbprocesocostoextras` decimal(12, 2) DEFAULT NULL,
    `tbprocesoprecio` decimal(12, 2) DEFAULT NULL,
    `tbprocesoestado` varchar(20) DEFAULT NULL,
    `tbprocesoregistrofecha` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbprocesocomponente`
-- Componentes que se usaron en el proceso; el costo es el del inventario al momento de registrar
--

CREATE TABLE `tbprocesocomponente` (
    `tbprocesocomponenteid` int(11) NOT NULL,
    `tbprocesocomponenteprocesoid` int(11) DEFAULT NULL,
    `tbprocesocomponentecomponenteid` int(11) DEFAULT NULL,
    `tbprocesocomponentecantidad` decimal(12, 2) DEFAULT NULL,
    `tbprocesocomponentecostounitario` decimal(14, 4) DEFAULT NULL,
    `tbprocesocomponentesubtotal` decimal(12, 2) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbprocesoextra`
-- Costos extra del proceso: luz, transporte, empaque...
--

CREATE TABLE `tbprocesoextra` (
    `tbprocesoextraid` int(11) NOT NULL,
    `tbprocesoextraprocesoid` int(11) DEFAULT NULL,
    `tbprocesoextraconcepto` varchar(60) DEFAULT NULL,
    `tbprocesoextramonto` decimal(12, 2) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbproveedor`
-- Proveedores de cada vendedor: nombre y contacto
--

CREATE TABLE `tbproveedor` (
    `tbproveedorid` int(11) NOT NULL,
    `tbproveedorvendedorid` int(11) DEFAULT NULL,
    `tbproveedoridentificaciontipo` varchar(20) DEFAULT NULL,
    `tbproveedoridentificacionnumero` varchar(20) DEFAULT NULL,
    `tbproveedornombre` varchar(50) DEFAULT NULL,
    `tbproveedortelefono` varchar(8) DEFAULT NULL,
    `tbproveedorcorreo` varchar(150) DEFAULT NULL,
    `tbproveedorregistrofecha` datetime DEFAULT NULL,
    `tbproveedoractivo` tinyint(4) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcompraproveedor`
-- Compras a proveedores; la fecha la escoge el vendedor y el envío es opcional (0 si no hubo)
-- total = subtotal + costo de envío; el estado es Registrada o Anulada
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
    `tbcompraproveedorregistrofecha` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbcompraproveedordetalle`
-- Una línea por componente comprado
-- El costo final es el costo de cada uno con su parte del envío; es el que entra al costo promedio (con 4 decimales)
--

CREATE TABLE `tbcompraproveedordetalle` (
    `tbcompraproveedordetalleid` int(11) NOT NULL,
    `tbcompraproveedordetallecompraproveedorid` int(11) DEFAULT NULL,
    `tbcompraproveedordetallecomponenteid` int(11) DEFAULT NULL,
    `tbcompraproveedordetallecantidad` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedordetallecostounitario` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedordetallesubtotal` decimal(12, 2) DEFAULT NULL,
    `tbcompraproveedordetallecostofinal` decimal(14, 4) DEFAULT NULL
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

-- --------------------------------------------------------

--
-- Índices
--

ALTER TABLE `tbusuario` ADD PRIMARY KEY (`tbusuarioid`);

ALTER TABLE `tbvendedor` ADD PRIMARY KEY (`tbvendedorid`);

ALTER TABLE `tbvendedorcontacto`
ADD PRIMARY KEY (`tbvendedorcontactoid`);

ALTER TABLE `tbcliente` ADD PRIMARY KEY (`tbclienteid`);

ALTER TABLE `tbsesion` ADD PRIMARY KEY (`tbsesionid`);

ALTER TABLE `tbtipocomponente`
ADD PRIMARY KEY (`tbtipocomponenteid`);

ALTER TABLE `tbtipocomponenteatributo`
ADD PRIMARY KEY (`tbtipocomponenteatributoid`);

ALTER TABLE `tbtipocomponenteatributoopcion`
ADD PRIMARY KEY (
    `tbtipocomponenteatributoopcionid`
);

ALTER TABLE `tbcomponente` ADD PRIMARY KEY (`tbcomponenteid`);

ALTER TABLE `tbcomponentevalor`
ADD PRIMARY KEY (`tbcomponentevalorid`);

ALTER TABLE `tbproceso` ADD PRIMARY KEY (`tbprocesoid`);
ALTER TABLE `tbprocesocomponente` ADD PRIMARY KEY (`tbprocesocomponenteid`);

ALTER TABLE `tbprocesoextra` ADD PRIMARY KEY (`tbprocesoextraid`);

ALTER TABLE `tbproveedor` ADD PRIMARY KEY (`tbproveedorid`);

ALTER TABLE `tbcompraproveedor`
ADD PRIMARY KEY (`tbcompraproveedorid`);

ALTER TABLE `tbcompraproveedordetalle`
ADD PRIMARY KEY (`tbcompraproveedordetalleid`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;