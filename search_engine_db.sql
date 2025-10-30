-- Script para crear las tablas del sistema de Recuperación de Información (IR).

-- Crea la base de datos si no existe y la selecciona.
CREATE SCHEMA IF NOT EXISTS `search_engine_db_bs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `search_engine_db_bs`;

--
-- Tabla para almacenar información sobre los documentos (archivos) cargados.
--
DROP TABLE IF EXISTS `postings`;
DROP TABLE IF EXISTS `terms`;
DROP TABLE IF EXISTS `documents`;

CREATE TABLE IF NOT EXISTS `documents` (
  `doc_id` INT(11) NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(512) NOT NULL,
  `snippet` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_content` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  UNIQUE KEY `idx_filename` (`filename`),
  FULLTEXT KEY `idx_fulltext_content` (`full_content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;