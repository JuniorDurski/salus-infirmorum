-- ============================================================
-- SALUS INFIRMORUM - ESQUEMA BASE
-- MariaDB 10.4+
-- Ejecutar sobre una base de datos vacia.
-- Todas las eliminaciones son logicas: eliminado_en IS NULL = activo.
-- ============================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================
-- USUARIOS Y CONFIGURACION DEL ESPECIALISTA
-- ============================================================

CREATE TABLE usuarios (
    id_usuario         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(250) NOT NULL,
    ci                 VARCHAR(30) NOT NULL,
    email              VARCHAR(254) NULL,
    password_hash      VARCHAR(255) NOT NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_acceso_en   DATETIME(6) NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_usuarios_ci UNIQUE (ci),
    CONSTRAINT uq_usuarios_email UNIQUE (email),
    CONSTRAINT fk_usuarios_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuarios_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuarios_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_usuarios_activos (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE titulos (
    id_titulo          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(120) NOT NULL,
    abreviatura        VARCHAR(30) NOT NULL,
    para_hombre        BOOLEAN NOT NULL DEFAULT TRUE,
    para_mujer         BOOLEAN NOT NULL DEFAULT TRUE,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_titulos_nombre_abreviatura UNIQUE (nombre, abreviatura),
    CONSTRAINT chk_titulos_genero
        CHECK (para_hombre = TRUE OR para_mujer = TRUE),
    CONSTRAINT fk_titulos_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_titulos_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_titulos_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_titulos_activos (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE universidades (
    id_universidad     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(250) NOT NULL,
    abreviatura        VARCHAR(50) NULL,
    pais               VARCHAR(100) NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_universidades_nombre UNIQUE (nombre),
    CONSTRAINT fk_universidades_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_universidades_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_universidades_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_universidades_activas (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_configuracion (
    id_usuario         INT UNSIGNED PRIMARY KEY,
    id_titulo          INT UNSIGNED NULL,
    id_universidad     INT UNSIGNED NULL,
    numero_registro    VARCHAR(100) NULL,
    firma_url          VARCHAR(2048) NULL,
    firma_sello_url    VARCHAR(2048) NULL,
    sello_url          VARCHAR(2048) NULL,
    modo_oscuro        BOOLEAN NOT NULL DEFAULT FALSE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_usuario_config_registro UNIQUE (numero_registro),
    CONSTRAINT fk_usuario_config_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_config_titulo
        FOREIGN KEY (id_titulo) REFERENCES titulos (id_titulo)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_config_universidad
        FOREIGN KEY (id_universidad) REFERENCES universidades (id_universidad)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_config_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuario_config_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuario_config_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_usuario_config_activa (eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEGURIDAD: USUARIOS -> USUARIO_ROL <- ROLES
--                         ROLES -> ROL_PERMISO <- PERMISOS
-- ============================================================

CREATE TABLE roles (
    id_rol             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo             VARCHAR(80) NOT NULL,
    nombre             VARCHAR(120) NOT NULL,
    descripcion        VARCHAR(500) NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_roles_codigo UNIQUE (codigo),
    CONSTRAINT fk_roles_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_roles_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_roles_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_roles_activos (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permisos (
    id_permiso         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo             VARCHAR(120) NOT NULL,
    nombre             VARCHAR(150) NOT NULL,
    modulo             VARCHAR(80) NOT NULL,
    descripcion        VARCHAR(500) NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_permisos_codigo UNIQUE (codigo),
    CONSTRAINT fk_permisos_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_permisos_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_permisos_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_permisos_modulo (modulo),
    INDEX idx_permisos_activos (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_rol (
    id_usuario         INT UNSIGNED NOT NULL,
    id_rol             INT UNSIGNED NOT NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    PRIMARY KEY (id_usuario, id_rol),
    CONSTRAINT fk_usuario_rol_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_rol_rol
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_rol_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuario_rol_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_usuario_rol_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_usuario_rol_rol (id_rol, eliminado_en),
    INDEX idx_usuario_rol_activo (id_usuario, eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permiso (
    id_rol             INT UNSIGNED NOT NULL,
    id_permiso         INT UNSIGNED NOT NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rol_permiso_rol
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_rol_permiso_permiso
        FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_rol_permiso_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_rol_permiso_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_rol_permiso_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_rol_permiso_permiso (id_permiso, eliminado_en),
    INDEX idx_rol_permiso_activo (id_rol, eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MENUS Y SUBMENUS
-- visible_todos = todos los usuarios autenticados.
-- ============================================================

CREATE TABLE menus (
    id_menu            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_menu_padre      INT UNSIGNED NULL,
    codigo             VARCHAR(100) NOT NULL,
    nombre             VARCHAR(150) NOT NULL,
    ruta               VARCHAR(500) NULL,
    icono              VARCHAR(100) NULL,
    orden              INT NOT NULL DEFAULT 0,
    visible_todos      BOOLEAN NOT NULL DEFAULT FALSE,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_menus_codigo UNIQUE (codigo),
    CONSTRAINT fk_menus_padre
        FOREIGN KEY (id_menu_padre) REFERENCES menus (id_menu)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_menus_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_menus_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_menus_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_menus_padre_orden (id_menu_padre, orden),
    INDEX idx_menus_visibles (eliminado_en, activo, visible_todos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_permiso (
    id_menu            INT UNSIGNED NOT NULL,
    id_permiso         INT UNSIGNED NOT NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    PRIMARY KEY (id_menu, id_permiso),
    CONSTRAINT fk_menu_permiso_menu
        FOREIGN KEY (id_menu) REFERENCES menus (id_menu)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_menu_permiso_permiso
        FOREIGN KEY (id_permiso) REFERENCES permisos (id_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_menu_permiso_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_menu_permiso_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_menu_permiso_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_menu_permiso_permiso (id_permiso, eliminado_en),
    INDEX idx_menu_permiso_activo (id_menu, eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDITORIA DE SEGURIDAD Y MENUS
-- La aplicacion inserta una fila dentro de la misma transaccion.
-- ============================================================

CREATE TABLE auditoria_seguridad (
    id_auditoria       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario_actor   INT UNSIGNED NULL,
    actor_identificador VARCHAR(150) NULL,
    modulo             VARCHAR(80) NOT NULL,
    entidad            VARCHAR(100) NOT NULL,
    clave_registro     JSON NULL,
    accion             ENUM(
                           'INSERTAR', 'ACTUALIZAR', 'ELIMINAR', 'RESTAURAR',
                           'ASIGNAR', 'REVOCAR', 'INICIAR_SESION', 'CERRAR_SESION'
                       ) NOT NULL,
    datos_anteriores   JSON NULL,
    datos_nuevos       JSON NULL,
    direccion_ip       VARCHAR(45) NULL,
    user_agent         VARCHAR(500) NULL,
    request_id         CHAR(36) NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT fk_auditoria_actor
        FOREIGN KEY (id_usuario_actor) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_auditoria_actor_fecha (id_usuario_actor, creado_en),
    INDEX idx_auditoria_entidad_fecha (entidad, creado_en),
    INDEX idx_auditoria_activa (eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PACIENTES
-- ============================================================

CREATE TABLE pacientes (
    id_paciente        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres            VARCHAR(150) NOT NULL,
    apellidos          VARCHAR(150) NOT NULL,
    numero_documento   VARCHAR(30) NOT NULL,
    codigo             VARCHAR(20) NULL,
    grupo_sanguineo    VARCHAR(10) NULL,
    fecha_nacimiento   DATE NOT NULL,
    sexo               CHAR(1) NOT NULL,
    id_padre           INT UNSIGNED NULL,
    id_madre           INT UNSIGNED NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_pacientes_documento UNIQUE (numero_documento),
    CONSTRAINT chk_pacientes_sexo CHECK (sexo IN ('M', 'F', 'X')),
    CONSTRAINT chk_pacientes_padres_distintos
        CHECK (id_padre IS NULL OR id_madre IS NULL OR id_padre <> id_madre),
    CONSTRAINT fk_pacientes_padre
        FOREIGN KEY (id_padre) REFERENCES pacientes (id_paciente)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_pacientes_madre
        FOREIGN KEY (id_madre) REFERENCES pacientes (id_paciente)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_pacientes_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_pacientes_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_pacientes_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_pacientes_nombre (apellidos, nombres),
    INDEX idx_pacientes_padre (id_padre),
    INDEX idx_pacientes_madre (id_madre),
    INDEX idx_pacientes_activos (eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DERIVADORES Y DERIVACIONES
-- ============================================================

CREATE TABLE derivadores (
    id_derivador       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo               ENUM('PERSONA', 'INSTITUCION') NOT NULL,
    nombre             VARCHAR(250) NOT NULL,
    numero_documento   VARCHAR(30) NULL,
    numero_registro    VARCHAR(100) NULL,
    telefono           VARCHAR(50) NULL,
    email              VARCHAR(254) NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT fk_derivadores_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_derivadores_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_derivadores_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_derivadores_nombre (nombre),
    INDEX idx_derivadores_activos (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE derivaciones (
    id_derivacion      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_derivador       INT UNSIGNED NOT NULL,
    id_paciente        INT UNSIGNED NOT NULL,
    motivo             VARCHAR(1000) NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT fk_derivaciones_derivador
        FOREIGN KEY (id_derivador) REFERENCES derivadores (id_derivador)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_derivaciones_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_derivaciones_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_derivaciones_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_derivaciones_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_derivaciones_paciente_fecha (id_paciente, creado_en),
    INDEX idx_derivaciones_derivador_fecha (id_derivador, creado_en),
    INDEX idx_derivaciones_activas (eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ESCUELAS, EVALUACIONES Y PERIODO JSON
-- Formato de periodo: {"anio": 2026, "mes": 10}
-- ============================================================

CREATE TABLE escuelas (
    id_escuela         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo             VARCHAR(30) NOT NULL,
    nombre             VARCHAR(200) NOT NULL,
    descripcion        VARCHAR(500) NULL,
    activo             BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_escuelas_codigo UNIQUE (codigo),
    CONSTRAINT uq_escuelas_nombre UNIQUE (nombre),
    CONSTRAINT fk_escuelas_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_escuelas_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_escuelas_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_escuelas_activas (eliminado_en, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evaluaciones (
    id_evaluacion      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_paciente        INT UNSIGNED NOT NULL,
    id_escuela         INT UNSIGNED NOT NULL,
    codigo             VARCHAR(30) NULL,
    periodo            JSON NOT NULL,
    periodo_anio       SMALLINT UNSIGNED
                       AS (
                           CAST(
                               JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio'))
                               AS UNSIGNED
                           )
                       ) PERSISTENT,
    periodo_mes        TINYINT UNSIGNED
                       AS (
                           CAST(
                               JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes'))
                               AS UNSIGNED
                           )
                       ) PERSISTENT,
    estado             ENUM('BORRADOR', 'GENERADA', 'FINALIZADA', 'ANULADA')
                       NOT NULL DEFAULT 'BORRADOR',
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT chk_evaluaciones_periodo_objeto
        CHECK (JSON_TYPE(periodo) = 'OBJECT'),
    CONSTRAINT chk_evaluaciones_periodo_anio
        CHECK (periodo_anio IS NOT NULL AND periodo_anio BETWEEN 1900 AND 2200),
    CONSTRAINT chk_evaluaciones_periodo_mes
        CHECK (periodo_mes IS NOT NULL AND periodo_mes BETWEEN 1 AND 12),
    CONSTRAINT fk_evaluaciones_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_escuela
        FOREIGN KEY (id_escuela) REFERENCES escuelas (id_escuela)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_evaluaciones_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_evaluaciones_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_evaluaciones_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_evaluaciones_paciente (id_paciente, eliminado_en),
    INDEX idx_evaluaciones_escuela_periodo
        (id_escuela, periodo_anio, periodo_mes, eliminado_en),
    INDEX idx_evaluaciones_estado (estado, eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RESULTADOS MOCK EN JSON
-- ============================================================

CREATE TABLE resultados_mock (
    id_resultado       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_evaluacion      BIGINT UNSIGNED NOT NULL,
    plantilla          VARCHAR(100) NOT NULL,
    version_esquema    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    version_resultado  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    resultados         JSON NOT NULL,
    expira_en          DATETIME(6) NULL,
    creado_en          DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    actualizado_en     DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
                                  ON UPDATE CURRENT_TIMESTAMP(6),
    eliminado_en       DATETIME(6) NULL,
    creado_por         INT UNSIGNED NULL,
    actualizado_por    INT UNSIGNED NULL,
    eliminado_por      INT UNSIGNED NULL,

    CONSTRAINT uq_resultados_mock_version
        UNIQUE (id_evaluacion, version_resultado),
    CONSTRAINT chk_resultados_mock_documento
        CHECK (JSON_TYPE(resultados) IN ('OBJECT', 'ARRAY')),
    CONSTRAINT fk_resultados_mock_evaluacion
        FOREIGN KEY (id_evaluacion) REFERENCES evaluaciones (id_evaluacion)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_resultados_mock_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_resultados_mock_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT fk_resultados_mock_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE SET NULL,
    INDEX idx_resultados_mock_evaluacion (id_evaluacion, eliminado_en),
    INDEX idx_resultados_mock_expiracion (expira_en, eliminado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- EJEMPLOS DE SOFT DELETE Y RESTAURACION
-- ============================================================

-- Soft delete:
-- UPDATE pacientes
-- SET eliminado_en = CURRENT_TIMESTAMP(6),
--     eliminado_por = :id_usuario,
--     actualizado_por = :id_usuario
-- WHERE id_paciente = :id_paciente
--   AND eliminado_en IS NULL;

-- Consultar activos:
-- SELECT * FROM pacientes WHERE eliminado_en IS NULL;

-- Restaurar:
-- UPDATE pacientes
-- SET eliminado_en = NULL,
--     eliminado_por = NULL,
--     actualizado_por = :id_usuario
-- WHERE id_paciente = :id_paciente;

-- En tablas puente se restaura la misma fila:
-- UPDATE usuario_rol
-- SET eliminado_en = NULL,
--     eliminado_por = NULL,
--     actualizado_por = :id_usuario
-- WHERE id_usuario = :id_usuario_objetivo
--   AND id_rol = :id_rol;
