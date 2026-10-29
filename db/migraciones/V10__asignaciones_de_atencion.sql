-- Módulo de asignación y atención de postulantes.
-- El DER original se conserva; las tablas puente permiten seleccionar varios
-- usuarios, escuelas y estudios en una misma cabecera.

CREATE TABLE IF NOT EXISTS postulantes_estudios_referenciales (
    id_estudio INT UNSIGNED NOT NULL AUTO_INCREMENT,
    estudio_referencial VARCHAR(180) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_estudio),
    UNIQUE KEY uk_postulantes_estudio_referencial (estudio_referencial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_asignar_atencion (
    id_asignar_atencion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    empieza_atender TINYINT(1) NOT NULL DEFAULT 0,
    pago_realizado TINYINT(1) NOT NULL DEFAULT 0,
    creado_por INT UNSIGNED NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_asignar_atencion),
    KEY ix_asignacion_fecha (fecha),
    KEY ix_asignacion_inicio_pago (fecha, empieza_atender, pago_realizado),
    CONSTRAINT fk_asignacion_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_asignar_atencion_usuarios (
    id_asignar_atencion BIGINT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_asignar_atencion, id_usuario),
    KEY ix_asignacion_usuario (id_usuario, id_asignar_atencion),
    CONSTRAINT fk_asignacion_usuario_cabecera
        FOREIGN KEY (id_asignar_atencion) REFERENCES postulantes_asignar_atencion (id_asignar_atencion) ON DELETE CASCADE,
    CONSTRAINT fk_asignacion_usuario_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_asignar_atencion_escuelas (
    id_asignar_atencion BIGINT UNSIGNED NOT NULL,
    id_escuela INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_asignar_atencion, id_escuela),
    KEY ix_asignacion_escuela (id_escuela, id_asignar_atencion),
    CONSTRAINT fk_asignacion_escuela_cabecera
        FOREIGN KEY (id_asignar_atencion) REFERENCES postulantes_asignar_atencion (id_asignar_atencion) ON DELETE CASCADE,
    CONSTRAINT fk_asignacion_escuela_escuela
        FOREIGN KEY (id_escuela) REFERENCES escuelas (id_escuela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_asignar_atencion_estudios (
    id_asignar_atencion BIGINT UNSIGNED NOT NULL,
    id_estudio INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_asignar_atencion, id_estudio),
    CONSTRAINT fk_asignacion_estudio_cabecera
        FOREIGN KEY (id_asignar_atencion) REFERENCES postulantes_asignar_atencion (id_asignar_atencion) ON DELETE CASCADE,
    CONSTRAINT fk_asignacion_estudio_referencial
        FOREIGN KEY (id_estudio) REFERENCES postulantes_estudios_referenciales (id_estudio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_atencion (
    id_postulante_atencion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_asignar_atencion BIGINT UNSIGNED NOT NULL,
    id_paciente INT UNSIGNED NOT NULL,
    llamado TINYINT(1) NOT NULL DEFAULT 0,
    llamado_en DATETIME NULL,
    llamado_por INT UNSIGNED NULL,
    atendido TINYINT(1) NOT NULL DEFAULT 0,
    atendido_en DATETIME NULL,
    atendido_por INT UNSIGNED NULL,
    observacion JSON NOT NULL COMMENT '{"clinica":[],"general":[],"doctor":[]}',
    pagado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_postulante_atencion),
    UNIQUE KEY uk_postulante_asignacion (id_asignar_atencion, id_paciente),
    KEY ix_postulante_atencion_paciente (id_paciente),
    CONSTRAINT fk_postulante_atencion_asignacion
        FOREIGN KEY (id_asignar_atencion) REFERENCES postulantes_asignar_atencion (id_asignar_atencion) ON DELETE CASCADE,
    CONSTRAINT fk_postulante_atencion_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_postulante_atencion_llamado_por
        FOREIGN KEY (llamado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_postulante_atencion_atendido_por
        FOREIGN KEY (atendido_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulantes_atencion_estudios (
    id_postulante_atencion BIGINT UNSIGNED NOT NULL,
    id_estudio INT UNSIGNED NOT NULL,
    realizado TINYINT(1) NOT NULL DEFAULT 0,
    realizado_en DATETIME NULL,
    actualizado_por INT UNSIGNED NULL,
    PRIMARY KEY (id_postulante_atencion, id_estudio),
    CONSTRAINT fk_atencion_estudio_detalle
        FOREIGN KEY (id_postulante_atencion) REFERENCES postulantes_atencion (id_postulante_atencion) ON DELETE CASCADE,
    CONSTRAINT fk_atencion_estudio_referencial
        FOREIGN KEY (id_estudio) REFERENCES postulantes_estudios_referenciales (id_estudio),
    CONSTRAINT fk_atencion_estudio_actor
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('postulantes_atencion.ver', 'Ver atenciones asignadas', 'Consulta las asignaciones y sus postulantes según el alcance del usuario', 1),
    ('postulantes_atencion.asignar', 'Crear asignaciones de atención', 'Crea y edita cabeceras, escuelas, profesionales y estudios', 1),
    ('postulantes_atencion.iniciar', 'Iniciar atención de postulantes', 'Habilita o pausa la lista del día para los profesionales', 1),
    ('postulantes_atencion.sincronizar', 'Sincronizar postulantes', 'Incorpora postulantes creados en la fecha y escuelas asignadas', 1),
    ('postulantes_atencion.atender', 'Atender postulantes', 'Marca la atención y los estudios realizados', 1),
    ('postulantes_atencion.llamar', 'Llamar postulantes', 'Registra el llamado del postulante en la lista', 1),
    ('postulantes_atencion.comentar', 'Comentar atenciones', 'Agrega mensajes clínicos, generales o del doctor', 1),
    ('postulantes_atencion.pagar', 'Registrar pagos de atención', 'Marca pagos en la cabecera y por postulante', 1),
    ('postulantes_atencion.exportar', 'Exportar listas de atención', 'Descarga listas en Excel, CSV, TXT o JSON', 1),
    ('postulantes_estudios.ver', 'Ver estudios referenciales', 'Consulta el catálogo de estudios para postulantes', 1),
    ('postulantes_estudios.editar', 'Administrar estudios referenciales', 'Crea, edita y activa estudios referenciales', 1),
    ('menu.atenciones', 'Ver menú ATENCIONES', 'Permite visualizar el menú atenciones', 1),
    ('menu.atenciones.gestion', 'Ver menú GESTIÓN DE ATENCIONES', 'Permite visualizar la gestión de asignaciones', 1),
    ('menu.atenciones.estudios', 'Ver menú ESTUDIOS REFERENCIALES', 'Permite visualizar los estudios de postulantes', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT NULL, p.id_permiso, 'atenciones', 'ATENCIONES', NULL, '☷', 35, 0, 1
FROM permisos p WHERE p.codigo = 'menu.atenciones'
ON DUPLICATE KEY UPDATE id_permiso = VALUES(id_permiso), etiqueta = VALUES(etiqueta),
    url = VALUES(url), icono = VALUES(icono), orden = VALUES(orden), activo = 1;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, p.id_permiso, 'atenciones.gestion', 'GESTIÓN DE ATENCIONES',
       'atenciones.php', '✓', 10, 0, 1
FROM menus padre
INNER JOIN permisos p ON p.codigo = 'menu.atenciones.gestion'
WHERE padre.codigo = 'atenciones'
ON DUPLICATE KEY UPDATE id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono), orden = VALUES(orden), activo = 1;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, p.id_permiso, 'atenciones.estudios', 'ESTUDIOS REFERENCIALES',
       'estudios_referenciales.php', '▤', 20, 0, 1
FROM menus padre
INNER JOIN permisos p ON p.codigo = 'menu.atenciones.estudios'
WHERE padre.codigo = 'atenciones'
ON DUPLICATE KEY UPDATE id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono), orden = VALUES(orden), activo = 1;

-- Solo ADMIN recibe los permisos inicialmente. Los demás roles se configuran
-- desde el módulo de roles según los actores que defina la organización.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'ADMIN'
  AND (p.codigo LIKE 'postulantes_atencion.%'
       OR p.codigo LIKE 'postulantes_estudios.%'
       OR p.codigo LIKE 'menu.atenciones%');
