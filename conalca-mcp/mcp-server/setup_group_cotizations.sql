-- Script para crear la tabla group_cotizations y establecer la relación con cotizacion_models
-- Base de datos: ai_transport

-- 1. Crear tabla group_cotizations si no existe
CREATE TABLE IF NOT EXISTS `group_cotizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT 'Nombre del grupo de cotizaciones',
  `description` text DEFAULT NULL COMMENT 'Descripción del grupo',
  `status` enum('active','inactive','draft') DEFAULT 'active' COMMENT 'Estado del grupo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_group_status` (`status`),
  KEY `idx_group_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos de cotizaciones para organizar cotizaciones relacionadas';

-- 2. Verificar si la columna group_cotizations_id ya existe en cotizacion_models
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'ai_transport'
    AND TABLE_NAME = 'cotizacion_models'
    AND COLUMN_NAME = 'group_cotizations_id'
);

-- 3. Agregar la columna group_cotizations_id a cotizacion_models si no existe
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `cotizacion_models` ADD COLUMN `group_cotizations_id` int(11) DEFAULT NULL COMMENT "ID del grupo de cotizaciones al que pertenece" AFTER `silogtran_status`',
    'SELECT "La columna group_cotizations_id ya existe en cotizacion_models" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Crear la clave foránea si no existe
SET @fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'ai_transport'
    AND TABLE_NAME = 'cotizacion_models'
    AND CONSTRAINT_NAME = 'fk_cotizacion_group'
);

SET @sql_fk = IF(@fk_exists = 0,
    'ALTER TABLE `cotizacion_models` ADD CONSTRAINT `fk_cotizacion_group` FOREIGN KEY (`group_cotizations_id`) REFERENCES `group_cotizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "La clave foránea fk_cotizacion_group ya existe" as message'
);

PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- 5. Crear índices para optimizar consultas
CREATE INDEX IF NOT EXISTS `idx_cotizacion_group` ON `cotizacion_models` (`group_cotizations_id`);

-- 6. Insertar algunos datos de ejemplo en group_cotizations (opcional)
INSERT IGNORE INTO `group_cotizations` (`id`, `name`, `description`, `status`) VALUES
(1, 'Transporte Nacional', 'Grupo de cotizaciones para transporte dentro del territorio nacional', 'active'),
(2, 'Transporte Internacional', 'Grupo de cotizaciones para transporte internacional', 'active'),
(3, 'Transporte de Carga Pesada', 'Cotizaciones especializadas en transporte de carga pesada', 'active'),
(4, 'Transporte Urbano', 'Cotizaciones para transporte dentro de ciudades', 'active'),
(5, 'Transporte Refrigerado', 'Cotizaciones para transporte que requiere refrigeración', 'active');

-- Mostrar resultado de la configuración
SELECT 
    'Configuración completada exitosamente' as resultado,
    (SELECT COUNT(*) FROM group_cotizations) as grupos_creados,
    'Relación establecida entre cotizacion_models y group_cotizations' as relacion;