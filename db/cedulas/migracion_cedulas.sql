-- =====================================================================
-- Migración del padrón de cédulas: test.datos  ->  cedulas_db.cedulas
-- =====================================================================
-- Origen : base `test`, tabla `datos` (dump datos_db_2026-07-10_12-01-48.sql)
-- Destino: base `cedulas_db`, tabla `cedulas`
--
-- Uso (XAMPP):
--   C:\xampp\mysql\bin\mysql.exe -uroot --default-character-set=utf8mb4 < db/cedulas/migracion_cedulas.sql
--
-- Se puede volver a correr: recrea la tabla `cedulas` desde cero.
--
-- Reglas
-- ------
-- * Orden de carga y prioridad (INSERT IGNORE sobre la PK `cedula`):
--     1) personas principales  2) padres  3) madres  4) cónyuges
--   Si una cédula ya existe, la fila nueva se descarta. Por eso los datos de
--   la persona principal siempre ganan sobre los de padre/madre/cónyuge.
-- * Dentro de cada grupo se recorre `datos` por id descendente: si la misma
--   cédula aparece varias veces (p. ej. números viejos reutilizados por dos
--   personas distintas), queda el registro más reciente (id más alto).
-- * Principales: cedula = nrodoc; nombre + apellido; fechanac; sexo.
-- * Padres  -> sexo 'M'. Madres -> sexo 'F'.
-- * Cónyuges -> sexo inverso al de la persona principal (F->M, M->F);
--   si la principal no tiene sexo válido, queda NULL.
-- * Padres/madres/cónyuges se descartan si la cédula es NULL, vacía, solo
--   espacios o no tiene ningún carácter alfanumérico. No tienen fecha de
--   nacimiento (NULL).
-- * Normalización de cédulas de familiares (las principales ya son números):
--     - se quitan espacios al inicio y al final;
--     - '4.085.254' (puntos de miles) -> '4085254';
--     - solo dígitos con ceros a la izquierda -> sin ceros ('0123' -> '123').
--   Cualquier otro valor (p. ej. 'L485819', '3.121.740-8') se guarda tal cual.
-- * fechanac viene como texto 'YYYY-MM-DD'. Se convierte a DATE; queda NULL
--   si está vacía, no es una fecha válida o cae fuera de 1850-01-01..hoy
--   (en el padrón hay valores de relleno como 1777-07-07 y fechas futuras).
-- * sexo viene como 'F' / 'M' (más algún valor en blanco). Todo lo que no sea
--   F o M queda NULL.
-- =====================================================================

SET NAMES utf8mb4;
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;  -- no bloquear filas de test.datos
SET SESSION sql_mode = '';

CREATE DATABASE IF NOT EXISTS cedulas_db
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cedulas_db;

DROP TABLE IF EXISTS cedulas;
CREATE TABLE cedulas (
  cedula           VARCHAR(20)  NOT NULL COMMENT 'Número de cédula (texto: algunos familiares tienen letras o guiones)',
  nombre_completo  VARCHAR(160) NULL     COMMENT 'Nombre + apellido',
  fecha_nacimiento DATE         NULL     COMMENT 'Solo para personas principales',
  sexo             CHAR(1)      NULL     COMMENT 'F / M',
  origen           ENUM('principal','padre','madre','conyuge') NOT NULL COMMENT 'De qué columnas de test.datos salió el registro',
  PRIMARY KEY (cedula),
  CONSTRAINT chk_cedulas_sexo CHECK (sexo IN ('F','M'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Funciones auxiliares (solo para esta migración; se borran al final)
-- ---------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_mig_cedula_valida;
DROP FUNCTION IF EXISTS fn_mig_cedula_norm;
DROP FUNCTION IF EXISTS fn_mig_nombre;

DELIMITER $$

-- 1 si la cédula de un familiar se puede usar, 0 si se descarta
CREATE FUNCTION fn_mig_cedula_valida(c TEXT) RETURNS TINYINT DETERMINISTIC
  RETURN c IS NOT NULL AND c REGEXP '[[:alnum:]]'$$

-- Normaliza la cédula de un familiar (ver reglas arriba)
CREATE FUNCTION fn_mig_cedula_norm(c TEXT) RETURNS VARCHAR(20) DETERMINISTIC
BEGIN
  DECLARE v TEXT DEFAULT REGEXP_REPLACE(c, '^[[:space:]]+|[[:space:]]+$', '');
  IF v REGEXP '^[0-9]{1,3}([.][0-9]{3})+$' THEN
    SET v = REPLACE(v, '.', '');
  END IF;
  IF v REGEXP '^[0-9]+$' THEN
    SET v = TRIM(LEADING '0' FROM v);
    IF v = '' THEN SET v = '0'; END IF;
  END IF;
  RETURN LEFT(v, 20);
END$$

-- nombre + apellido, sin espacios dobles; NULL si queda vacío
CREATE FUNCTION fn_mig_nombre(n TEXT, a TEXT) RETURNS VARCHAR(160) DETERMINISTIC
  RETURN LEFT(NULLIF(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', n, a), '[[:space:]]+', ' ')), ''), 160)$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- 1) Personas principales
-- ---------------------------------------------------------------------
INSERT IGNORE INTO cedulas (cedula, nombre_completo, fecha_nacimiento, sexo, origen)
SELECT CAST(d.nrodoc AS CHAR),
       fn_mig_nombre(d.nombre, d.apellido),
       CASE WHEN d.fechanac REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
             AND STR_TO_DATE(d.fechanac, '%Y-%m-%d') BETWEEN '1850-01-01' AND CURDATE()
            THEN STR_TO_DATE(d.fechanac, '%Y-%m-%d') END,
       CASE UPPER(TRIM(d.sexo)) WHEN 'F' THEN 'F' WHEN 'M' THEN 'M' END,
       'principal'
FROM test.datos d
WHERE d.nrodoc IS NOT NULL
ORDER BY d.id DESC;
SET @ins_principal = ROW_COUNT();

-- ---------------------------------------------------------------------
-- 2) Padres (sexo M)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO cedulas (cedula, nombre_completo, fecha_nacimiento, sexo, origen)
SELECT fn_mig_cedula_norm(d.padre_cedul),
       fn_mig_nombre(d.padre_nombre, d.padre_apelli),
       NULL, 'M', 'padre'
FROM test.datos d
WHERE fn_mig_cedula_valida(d.padre_cedul)
ORDER BY d.id DESC;
SET @ins_padre = ROW_COUNT();

-- ---------------------------------------------------------------------
-- 3) Madres (sexo F)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO cedulas (cedula, nombre_completo, fecha_nacimiento, sexo, origen)
SELECT fn_mig_cedula_norm(d.madre_cedul),
       fn_mig_nombre(d.madre_nombre, d.madre_apelli),
       NULL, 'F', 'madre'
FROM test.datos d
WHERE fn_mig_cedula_valida(d.madre_cedul)
ORDER BY d.id DESC;
SET @ins_madre = ROW_COUNT();

-- ---------------------------------------------------------------------
-- 4) Cónyuges (sexo inverso al de la persona principal)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO cedulas (cedula, nombre_completo, fecha_nacimiento, sexo, origen)
SELECT fn_mig_cedula_norm(d.conyu_cedul),
       fn_mig_nombre(d.conyu_nombre, d.conyu_apelli),
       NULL,
       CASE UPPER(TRIM(d.sexo)) WHEN 'F' THEN 'M' WHEN 'M' THEN 'F' END,
       'conyuge'
FROM test.datos d
WHERE fn_mig_cedula_valida(d.conyu_cedul)
ORDER BY d.id DESC;
SET @ins_conyuge = ROW_COUNT();

-- ---------------------------------------------------------------------
-- Resumen
--   candidatos           = filas de test.datos
--   descartados_invalida = cédula NULL / vacía / solo espacios / sin alfanuméricos
--   descartados_repetida = cédula válida que ya estaba cargada (repetida en el
--                          mismo grupo o ya cargada por un grupo con prioridad)
-- ---------------------------------------------------------------------
SELECT 'principal' AS grupo,
       COUNT(*) AS candidatos,
       SUM(nrodoc IS NULL) AS descartados_invalida,
       SUM(nrodoc IS NOT NULL) - @ins_principal AS descartados_repetida,
       @ins_principal AS insertados
FROM test.datos
UNION ALL
SELECT 'padre', COUNT(*), SUM(NOT fn_mig_cedula_valida(padre_cedul)),
       SUM(fn_mig_cedula_valida(padre_cedul)) - @ins_padre, @ins_padre
FROM test.datos
UNION ALL
SELECT 'madre', COUNT(*), SUM(NOT fn_mig_cedula_valida(madre_cedul)),
       SUM(fn_mig_cedula_valida(madre_cedul)) - @ins_madre, @ins_madre
FROM test.datos
UNION ALL
SELECT 'conyuge', COUNT(*), SUM(NOT fn_mig_cedula_valida(conyu_cedul)),
       SUM(fn_mig_cedula_valida(conyu_cedul)) - @ins_conyuge, @ins_conyuge
FROM test.datos;

SELECT COUNT(*) AS total_cedulas FROM cedulas;

DROP FUNCTION IF EXISTS fn_mig_cedula_valida;
DROP FUNCTION IF EXISTS fn_mig_cedula_norm;
DROP FUNCTION IF EXISTS fn_mig_nombre;
