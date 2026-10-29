CREATE TABLE IF NOT EXISTS escuelas (
    id_escuela INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pais SMALLINT UNSIGNED NULL,
    nombre VARCHAR(150) NOT NULL,
    periodo JSON NOT NULL COMMENT '{"anio": 2026, "mes": 10}',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_escuela),
    KEY ix_escuelas_nombre (nombre),
    CONSTRAINT fk_escuelas_pais
        FOREIGN KEY (id_pais) REFERENCES paises (id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pacientes (
    id_paciente INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_escuela INT UNSIGNED NULL,
    id_pais SMALLINT UNSIGNED NULL,
    codigo VARCHAR(20) NULL,
    nombre VARCHAR(250) NOT NULL,
    documento VARCHAR(30) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    es_femenino TINYINT(1) NOT NULL COMMENT '0=hombre, 1=mujer',
    grupo_sanguineo VARCHAR(30) NULL,
    telefono VARCHAR(20) NULL COMMENT 'Formato E.164, por ejemplo +595981123456',
    url_foto VARCHAR(2048) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    eliminado_en DATETIME NULL,
    eliminado_por INT UNSIGNED NULL,
    PRIMARY KEY (id_paciente),
    UNIQUE KEY uk_pacientes_pais_documento (id_pais, documento),
    KEY ix_pacientes_nombre (nombre),
    KEY ix_pacientes_escuela (id_escuela),
    KEY ix_pacientes_activo (activo),
    CONSTRAINT fk_pacientes_escuela
        FOREIGN KEY (id_escuela) REFERENCES escuelas (id_escuela),
    CONSTRAINT fk_pacientes_pais
        FOREIGN KEY (id_pais) REFERENCES paises (id_pais),
    CONSTRAINT fk_pacientes_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    CONSTRAINT ck_pacientes_telefono
        CHECK (telefono IS NULL OR telefono REGEXP '^\\+[1-9][0-9]{7,14}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS derivadores (
    id_derivador INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pais SMALLINT UNSIGNED NULL,
    nombre VARCHAR(200) NOT NULL,
    institucion VARCHAR(200) NULL,
    telefono VARCHAR(20) NULL,
    correo VARCHAR(150) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_derivador),
    KEY ix_derivadores_nombre (nombre),
    CONSTRAINT fk_derivadores_pais
        FOREIGN KEY (id_pais) REFERENCES paises (id_pais),
    CONSTRAINT ck_derivadores_telefono
        CHECK (telefono IS NULL OR telefono REGEXP '^\\+[1-9][0-9]{7,14}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS derivador_paciente (
    id_derivador INT UNSIGNED NOT NULL,
    id_paciente INT UNSIGNED NOT NULL,
    derivado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(500) NULL,
    registrado_por INT UNSIGNED NULL,
    PRIMARY KEY (id_derivador, id_paciente),
    CONSTRAINT fk_derivador_paciente_derivador
        FOREIGN KEY (id_derivador) REFERENCES derivadores (id_derivador),
    CONSTRAINT fk_derivador_paciente_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_derivador_paciente_usuario
        FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipos_estudio (
    id_tipo_estudio SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_tipo_estudio),
    UNIQUE KEY uk_tipos_estudio_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plantillas_estudio (
    id_plantilla INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_tipo_estudio SMALLINT UNSIGNED NOT NULL,
    codigo VARCHAR(80) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    cabecera JSON NOT NULL,
    plantilla JSON NOT NULL,
    calculador VARCHAR(100) NOT NULL COMMENT 'Clave segura resuelta por RegistroCalculos en PHP',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_plantilla),
    UNIQUE KEY uk_plantillas_codigo_version (codigo, version),
    CONSTRAINT fk_plantillas_tipo
        FOREIGN KEY (id_tipo_estudio) REFERENCES tipos_estudio (id_tipo_estudio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estudios (
    id_estudio BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_paciente INT UNSIGNED NOT NULL,
    id_plantilla INT UNSIGNED NOT NULL,
    solicitado_por INT UNSIGNED NULL,
    cabecera JSON NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'BORRADOR',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_estudio),
    UNIQUE KEY uk_estudios_mock_paciente_plantilla (id_paciente, id_plantilla),
    KEY ix_estudios_fecha (fecha),
    CONSTRAINT fk_estudios_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_estudios_plantilla
        FOREIGN KEY (id_plantilla) REFERENCES plantillas_estudio (id_plantilla),
    CONSTRAINT fk_estudios_usuario
        FOREIGN KEY (solicitado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resultados_estudio (
    id_resultado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_estudio BIGINT UNSIGNED NOT NULL,
    resultado JSON NOT NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id_resultado),
    UNIQUE KEY uk_resultados_estudio (id_estudio),
    CONSTRAINT fk_resultados_estudio
        FOREIGN KEY (id_estudio) REFERENCES estudios (id_estudio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
