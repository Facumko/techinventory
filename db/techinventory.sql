-- TechInventory | GlobalTech S.A. | Control de activos e inventario interno
-- Importar desde phpMyAdmin (pestaña "Importar") o con: mysql -u root < techinventory.sql

CREATE DATABASE IF NOT EXISTS `techinventory` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `techinventory`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `asignaciones`;
DROP TABLE IF EXISTS `activos`;
DROP TABLE IF EXISTS `empleados`;
DROP TABLE IF EXISTS `sedes`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- sedes: catálogo base de lugares físicos
-- ---------------------------------------------------------------------------
CREATE TABLE `sedes` (
  `id_sede`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(80)  NOT NULL,
  `planta`      VARCHAR(20)  NOT NULL,
  `descripcion` TEXT         NOT NULL,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_sede`),
  UNIQUE KEY `uq_sedes_nombre_planta` (`nombre`, `planta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- empleados: personal interno; cada empleado pertenece a una sede
-- ---------------------------------------------------------------------------
CREATE TABLE `empleados` (
  `id_empleado` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(40)  NOT NULL,
  `apellido`    VARCHAR(40)  NOT NULL,
  `dni`         VARCHAR(8)   NOT NULL,
  `id_sede`     INT UNSIGNED NOT NULL,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `uq_empleados_dni` (`dni`),
  KEY `idx_empleados_sede` (`id_sede`),
  CONSTRAINT `fk_empleados_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- activos: bienes individuales; cada activo está ubicado en una sede
-- ---------------------------------------------------------------------------
CREATE TABLE `activos` (
  `id_activo`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `marca`        VARCHAR(40)  NOT NULL,
  `tipo`         ENUM('Notebook','Monitor','Teclado','Mouse','Celular','Licencia','Otro') NOT NULL,
  `modelo`       VARCHAR(40)  DEFAULT NULL,
  `numero_serie` VARCHAR(100) DEFAULT NULL,
  `estado`       ENUM('Nuevo','Bueno','Regular','Requiere reparación') NOT NULL DEFAULT 'Nuevo',
  `id_sede`      INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_activo`),
  UNIQUE KEY `uq_activos_numero_serie` (`numero_serie`),
  KEY `idx_activos_sede` (`id_sede`),
  CONSTRAINT `fk_activos_sede` FOREIGN KEY (`id_sede`) REFERENCES `sedes` (`id_sede`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- asignaciones: tabla intermedia (empleados N:M activos) con el historial
-- Una asignación con fecha_devolucion NULL está vigente.
-- ---------------------------------------------------------------------------
CREATE TABLE `asignaciones` (
  `id_asignacion`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_empleado`       INT UNSIGNED NOT NULL,
  `id_activo`         INT UNSIGNED NOT NULL,
  `fecha_entrega`     DATE         NOT NULL,
  `fecha_devolucion`  DATE         DEFAULT NULL,
  `estado_equipo`     ENUM('Nuevo','Bueno','Regular','Requiere reparación') NOT NULL,
  `estado_devolucion` ENUM('Nuevo','Bueno','Regular','Requiere reparación') DEFAULT NULL,
  `observaciones`     TEXT         DEFAULT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asignaciones_empleado` (`id_empleado`),
  KEY `idx_asignaciones_activo` (`id_activo`, `fecha_devolucion`),
  CONSTRAINT `fk_asignaciones_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_asignaciones_activo` FOREIGN KEY (`id_activo`) REFERENCES `activos` (`id_activo`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_asignaciones_fechas` CHECK (`fecha_devolucion` IS NULL OR `fecha_devolucion` >= `fecha_entrega`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================================================
-- Datos de prueba
-- ===========================================================================
INSERT INTO `sedes` (`id_sede`, `nombre`, `planta`, `descripcion`) VALUES
(1, 'Casa Central', 'Planta Alta', 'Oficinas de gerencia, administración y sistemas'),
(2, 'Casa Central', 'Planta Baja', 'Recepción, depósito de equipos y mesa de ayuda'),
(3, 'Sucursal Centro', 'Piso 1', 'Atención comercial y soporte técnico en el centro de la ciudad');

INSERT INTO `empleados` (`id_empleado`, `nombre`, `apellido`, `dni`, `id_sede`, `activo`) VALUES
(1, 'Lucía',   'Fernández', '30123456', 1, 1),
(2, 'Martín',  'Gómez',     '32456789', 1, 1),
(3, 'Sofía',   'Ramírez',   '35789012', 2, 1),
(4, 'Diego',   'Acosta',    '28345678', 3, 1),
(5, 'Valeria', 'Benítez',   '36901234', 3, 1),
(6, 'Julián',  'Sosa',      '33567890', 2, 0);

INSERT INTO `activos` (`id_activo`, `marca`, `tipo`, `modelo`, `numero_serie`, `estado`, `id_sede`) VALUES
(1,  'Dell',      'Notebook', 'Latitude 5440',  'DL5440-0001', 'Bueno',               1),
(2,  'Dell',      'Notebook', 'Latitude 5440',  'DL5440-0002', 'Bueno',               1),
(3,  'HP',        'Monitor',  'E24 G5',         'HPE24-1001',  'Bueno',               1),
(4,  'HP',        'Monitor',  'E24 G5',         'HPE24-1002',  'Regular',             3),
(5,  'Logitech',  'Teclado',  'K120',           'LGK120-2001', 'Nuevo',               2),
(6,  'Logitech',  'Mouse',    'M185',           'LGM185-3001', 'Nuevo',               2),
(7,  'Samsung',   'Celular',  'Galaxy A54',     'SGA54-4001',  'Bueno',               3),
(8,  'Lenovo',    'Notebook', 'ThinkPad E14',   'LNE14-5001',  'Requiere reparación', 2),
(9,  'Microsoft', 'Licencia', 'Office 2021',    'MSO21-6001',  'Nuevo',               2),
(10, 'Lenovo',    'Notebook', 'ThinkPad E14',   'LNE14-5002',  'Nuevo',               3);

INSERT INTO `asignaciones` (`id_asignacion`, `id_empleado`, `id_activo`, `fecha_entrega`, `fecha_devolucion`, `estado_equipo`, `estado_devolucion`, `observaciones`) VALUES
(1, 1, 1, '2026-02-02', NULL,         'Nuevo',   NULL,      'Notebook de uso diario'),
(2, 1, 3, '2026-02-02', NULL,         'Bueno',   NULL,      NULL),
(3, 2, 2, '2026-03-10', NULL,         'Bueno',   NULL,      NULL),
(4, 4, 7, '2026-04-15', NULL,         'Bueno',   NULL,      'Celular corporativo'),
(5, 6, 8, '2026-01-12', '2026-08-30', 'Bueno',   'Requiere reparación', 'Devuelve el equipo por desvinculación; pantalla dañada'),
(6, 5, 4, '2026-05-20', '2026-09-18', 'Bueno',   'Regular', 'Devuelto por cambio de monitor');
