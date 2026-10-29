-- Normaliza los datos existentes de personas e instituciones.
UPDATE usuarios
SET nombre = UPPER(TRIM(nombre));

UPDATE pacientes
SET nombre = UPPER(TRIM(nombre));

UPDATE escuelas
SET nombre = UPPER(TRIM(nombre));

UPDATE universidades
SET nombre = UPPER(TRIM(nombre)),
    abreviatura = IF(abreviatura IS NULL, NULL, UPPER(TRIM(abreviatura)));

UPDATE derivadores
SET nombre = UPPER(TRIM(nombre)),
    institucion = IF(institucion IS NULL, NULL, UPPER(TRIM(institucion)));

-- La base también aplica la regla para escrituras externas o módulos futuros.
DROP TRIGGER IF EXISTS trg_usuarios_bi_mayusculas;
CREATE TRIGGER trg_usuarios_bi_mayusculas
BEFORE INSERT ON usuarios FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_usuarios_bu_mayusculas;
CREATE TRIGGER trg_usuarios_bu_mayusculas
BEFORE UPDATE ON usuarios FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_pacientes_bi_mayusculas;
CREATE TRIGGER trg_pacientes_bi_mayusculas
BEFORE INSERT ON pacientes FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_pacientes_bu_mayusculas;
CREATE TRIGGER trg_pacientes_bu_mayusculas
BEFORE UPDATE ON pacientes FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_escuelas_bi_mayusculas;
CREATE TRIGGER trg_escuelas_bi_mayusculas
BEFORE INSERT ON escuelas FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_escuelas_bu_mayusculas;
CREATE TRIGGER trg_escuelas_bu_mayusculas
BEFORE UPDATE ON escuelas FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre));

DROP TRIGGER IF EXISTS trg_universidades_bi_mayusculas;
CREATE TRIGGER trg_universidades_bi_mayusculas
BEFORE INSERT ON universidades FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.abreviatura = IF(NEW.abreviatura IS NULL, NULL, UPPER(TRIM(NEW.abreviatura)));

DROP TRIGGER IF EXISTS trg_universidades_bu_mayusculas;
CREATE TRIGGER trg_universidades_bu_mayusculas
BEFORE UPDATE ON universidades FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.abreviatura = IF(NEW.abreviatura IS NULL, NULL, UPPER(TRIM(NEW.abreviatura)));

DROP TRIGGER IF EXISTS trg_derivadores_bi_mayusculas;
CREATE TRIGGER trg_derivadores_bi_mayusculas
BEFORE INSERT ON derivadores FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.institucion = IF(NEW.institucion IS NULL, NULL, UPPER(TRIM(NEW.institucion)));

DROP TRIGGER IF EXISTS trg_derivadores_bu_mayusculas;
CREATE TRIGGER trg_derivadores_bu_mayusculas
BEFORE UPDATE ON derivadores FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.institucion = IF(NEW.institucion IS NULL, NULL, UPPER(TRIM(NEW.institucion)));
