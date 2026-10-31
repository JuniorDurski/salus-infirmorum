-- Estandariza las tablas históricas de derivadores para el flujo de postulantes.
DROP TRIGGER IF EXISTS trg_derivadores_bi_mayusculas;
DROP TRIGGER IF EXISTS trg_derivadores_bu_mayusculas;

RENAME TABLE derivadores TO postulantes_arreadores,
             derivador_paciente TO postulantes_arreador_postulante;

ALTER TABLE postulantes_arreador_postulante
    DROP FOREIGN KEY fk_derivador_paciente_derivador,
    DROP FOREIGN KEY fk_derivador_paciente_paciente,
    DROP FOREIGN KEY fk_derivador_paciente_usuario;

ALTER TABLE postulantes_arreadores
    DROP FOREIGN KEY fk_derivadores_pais,
    CHANGE COLUMN id_derivador id_arreador INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE postulantes_arreadores
    DROP INDEX ix_derivadores_nombre,
    ADD KEY ix_postulantes_arreadores_nombre (nombre),
    ADD CONSTRAINT fk_postulantes_arreadores_pais
        FOREIGN KEY (id_pais) REFERENCES paises (id_pais);

CREATE TABLE postulantes_arreador_escuela (
    id_arreador_escuela BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_arreador INT UNSIGNED NOT NULL,
    id_escuela INT UNSIGNED NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL DEFAULT (YEAR(CURDATE())),
    monto_total DECIMAL(12,2) NOT NULL,
    comision_arreador DECIMAL(12,2) NOT NULL,
    monto_clinica DECIMAL(12,2) AS (monto_total - comision_arreador) STORED,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_arreador_escuela),
    UNIQUE KEY uk_postulantes_arreador_escuela_anio (id_arreador, id_escuela, anio),
    KEY ix_postulantes_arreador_escuela_escuela (id_escuela, anio, activo),
    CONSTRAINT fk_postulantes_arreador_escuela_arreador
        FOREIGN KEY (id_arreador) REFERENCES postulantes_arreadores (id_arreador),
    CONSTRAINT fk_postulantes_arreador_escuela_escuela
        FOREIGN KEY (id_escuela) REFERENCES escuelas (id_escuela),
    CONSTRAINT ck_postulantes_arreador_escuela_montos
        CHECK (monto_total >= 0 AND comision_arreador >= 0 AND comision_arreador <= monto_total),
    CONSTRAINT ck_postulantes_arreador_escuela_anio
        CHECK (anio BETWEEN 2000 AND 2200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recupera las asociaciones históricas que ya tengan escuela sin perder información.
INSERT INTO postulantes_arreador_escuela
    (id_arreador, id_escuela, anio, monto_total, comision_arreador)
SELECT DISTINCT ap.id_derivador, p.id_escuela, YEAR(ap.derivado_en), 0, 0
FROM postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
WHERE p.id_escuela IS NOT NULL
ON DUPLICATE KEY UPDATE id_arreador_escuela = id_arreador_escuela;

ALTER TABLE postulantes_arreador_postulante
    DROP PRIMARY KEY,
    ADD COLUMN id_arreador_postulante BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    CHANGE COLUMN id_derivador id_arreador INT UNSIGNED NOT NULL,
    ADD COLUMN id_arreador_escuela BIGINT UNSIGNED NULL AFTER id_arreador,
    ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD PRIMARY KEY (id_arreador_postulante),
    ADD UNIQUE KEY uk_postulantes_arreador_postulante (id_arreador_escuela, id_paciente),
    ADD KEY ix_postulantes_arreador_postulante_paciente (id_paciente),
    ADD KEY ix_postulantes_arreador_postulante_convenio (id_arreador_escuela),
    ADD CONSTRAINT fk_postulantes_arreador_postulante_arreador
        FOREIGN KEY (id_arreador) REFERENCES postulantes_arreadores (id_arreador),
    ADD CONSTRAINT fk_postulantes_arreador_postulante_convenio
        FOREIGN KEY (id_arreador_escuela) REFERENCES postulantes_arreador_escuela (id_arreador_escuela),
    ADD CONSTRAINT fk_postulantes_arreador_postulante_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    ADD CONSTRAINT fk_postulantes_arreador_postulante_usuario
        FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL;

UPDATE postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
INNER JOIN postulantes_arreador_escuela ae
        ON ae.id_arreador = ap.id_arreador
       AND ae.id_escuela = p.id_escuela
       AND ae.anio = YEAR(ap.derivado_en)
SET ap.id_arreador_escuela = ae.id_arreador_escuela
WHERE ap.id_arreador_escuela IS NULL;

CREATE TABLE metodos_pago (
    id_metodo_pago SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_metodo_pago),
    UNIQUE KEY uk_metodos_pago_codigo (codigo),
    UNIQUE KEY uk_metodos_pago_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO metodos_pago (codigo, nombre) VALUES
    ('EFECTIVO', 'EFECTIVO'),
    ('TRANSFERENCIA', 'TRANSFERENCIA'),
    ('TARJETA', 'TARJETA'),
    ('CHEQUE', 'CHEQUE');

CREATE TABLE postulantes_pago (
    id_pago BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_arreador_postulante BIGINT UNSIGNED NOT NULL,
    id_metodo_pago SMALLINT UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(12,2) NOT NULL,
    saldo DECIMAL(12,2) NOT NULL,
    observacion VARCHAR(500) NULL,
    registrado_por INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pago),
    KEY ix_postulantes_pago_asignacion_fecha (id_arreador_postulante, fecha),
    KEY ix_postulantes_pago_metodo (id_metodo_pago),
    CONSTRAINT fk_postulantes_pago_asignacion
        FOREIGN KEY (id_arreador_postulante)
        REFERENCES postulantes_arreador_postulante (id_arreador_postulante),
    CONSTRAINT fk_postulantes_pago_metodo
        FOREIGN KEY (id_metodo_pago) REFERENCES metodos_pago (id_metodo_pago),
    CONSTRAINT fk_postulantes_pago_usuario
        FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    CONSTRAINT ck_postulantes_pago_monto CHECK (monto > 0),
    CONSTRAINT ck_postulantes_pago_saldo CHECK (saldo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TRIGGER trg_postulantes_arreadores_bi_mayusculas
BEFORE INSERT ON postulantes_arreadores FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.institucion = IF(NEW.institucion IS NULL, NULL, UPPER(TRIM(NEW.institucion)));

CREATE TRIGGER trg_postulantes_arreadores_bu_mayusculas
BEFORE UPDATE ON postulantes_arreadores FOR EACH ROW
SET NEW.nombre = UPPER(TRIM(NEW.nombre)),
    NEW.institucion = IF(NEW.institucion IS NULL, NULL, UPPER(TRIM(NEW.institucion)));
