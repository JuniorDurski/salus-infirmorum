CREATE TABLE IF NOT EXISTS paises (
    id_pais SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_iso2 CHAR(2) NOT NULL,
    codigo_iso3 CHAR(3) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    prefijo_telefonico VARCHAR(8) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_pais),
    UNIQUE KEY uk_paises_iso2 (codigo_iso2),
    UNIQUE KEY uk_paises_iso3 (codigo_iso3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS universidades (
    id_universidad INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_pais SMALLINT UNSIGNED NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    abreviatura VARCHAR(30) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_universidad),
    UNIQUE KEY uk_universidades_pais_nombre (id_pais, nombre),
    CONSTRAINT fk_universidades_pais
        FOREIGN KEY (id_pais) REFERENCES paises (id_pais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS titulos (
    id_titulo SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(100) NOT NULL,
    abreviatura VARCHAR(20) NOT NULL,
    es_femenino TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=hombre, 1=mujer',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_titulo),
    UNIQUE KEY uk_titulos_nombre_genero (titulo, es_femenino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(250) NOT NULL,
    ci VARCHAR(30) NOT NULL,
    correo VARCHAR(150) NULL,
    clave_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    eliminado_en DATETIME NULL,
    eliminado_por INT UNSIGNED NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uk_usuarios_ci (ci),
    UNIQUE KEY uk_usuarios_correo (correo),
    KEY ix_usuarios_activo (activo),
    CONSTRAINT fk_usuarios_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario_configuraciones (
    id_usuario INT UNSIGNED NOT NULL,
    id_titulo SMALLINT UNSIGNED NULL,
    id_universidad INT UNSIGNED NULL,
    numero_registro VARCHAR(80) NULL,
    url_firma VARCHAR(2048) NULL,
    url_firma_sello VARCHAR(2048) NULL,
    url_sello VARCHAR(2048) NULL,
    modo_oscuro TINYINT(1) NOT NULL DEFAULT 0,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    CONSTRAINT fk_usuario_config_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_config_titulo
        FOREIGN KEY (id_titulo) REFERENCES titulos (id_titulo),
    CONSTRAINT fk_usuario_config_universidad
        FOREIGN KEY (id_universidad) REFERENCES universidades (id_universidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id_rol SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(60) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uk_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos (
    id_permiso SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(100) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_permiso),
    UNIQUE KEY uk_permisos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario_rol (
    id_usuario INT UNSIGNED NOT NULL,
    id_rol SMALLINT UNSIGNED NOT NULL,
    asignado_por INT UNSIGNED NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_rol),
    CONSTRAINT fk_usuario_rol_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_rol_rol
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_rol_actor
        FOREIGN KEY (asignado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rol_permisos (
    id_rol SMALLINT UNSIGNED NOT NULL,
    id_permiso SMALLINT UNSIGNED NOT NULL,
    asignado_por INT UNSIGNED NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rol_permisos_rol
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol) ON DELETE CASCADE,
    CONSTRAINT fk_rol_permisos_permiso
        FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE CASCADE,
    CONSTRAINT fk_rol_permisos_actor
        FOREIGN KEY (asignado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menus (
    id_menu SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_menu_padre SMALLINT UNSIGNED NULL,
    id_permiso SMALLINT UNSIGNED NULL,
    codigo VARCHAR(80) NOT NULL,
    etiqueta VARCHAR(100) NOT NULL,
    url VARCHAR(255) NULL,
    icono VARCHAR(100) NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    visible_todos TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_menu),
    UNIQUE KEY uk_menus_codigo (codigo),
    KEY ix_menus_padre_orden (id_menu_padre, orden),
    CONSTRAINT fk_menus_padre
        FOREIGN KEY (id_menu_padre) REFERENCES menus (id_menu) ON DELETE CASCADE,
    CONSTRAINT fk_menus_permiso
        FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logueos (
    id_logueo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NULL,
    identificador VARCHAR(150) NOT NULL,
    exitoso TINYINT(1) NOT NULL,
    ip VARCHAR(45) NULL,
    agente_usuario VARCHAR(500) NULL,
    motivo_fallo VARCHAR(150) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_logueo),
    KEY ix_logueos_usuario_fecha (id_usuario, fecha),
    KEY ix_logueos_fecha (fecha),
    CONSTRAINT fk_logueos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditorias (
    id_auditoria BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    datos JSON NOT NULL,
    fecha DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id_auditoria),
    KEY ix_auditorias_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
