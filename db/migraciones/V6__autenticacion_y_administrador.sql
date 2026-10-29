ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS usuario VARCHAR(60) NULL AFTER id_usuario,
    ADD COLUMN IF NOT EXISTS clave_predeterminada TINYINT(1) NOT NULL DEFAULT 1 AFTER clave_hash,
    ADD COLUMN IF NOT EXISTS version_sesion INT UNSIGNED NOT NULL DEFAULT 1 AFTER clave_predeterminada,
    ADD COLUMN IF NOT EXISTS ultimo_login_en DATETIME NULL AFTER version_sesion;

UPDATE usuarios
SET usuario = CONCAT('usuario_', id_usuario)
WHERE usuario IS NULL OR TRIM(usuario) = '';

ALTER TABLE usuarios
    MODIFY COLUMN usuario VARCHAR(60) NOT NULL,
    ADD UNIQUE INDEX IF NOT EXISTS uk_usuarios_usuario (usuario),
    ADD CONSTRAINT ck_usuarios_clave_predeterminada
        CHECK (clave_predeterminada IN (0, 1));

INSERT INTO usuarios
    (usuario, nombre, ci, correo, clave_hash, clave_predeterminada, activo)
VALUES
    ('admin', 'ADMINISTRADOR DEL SISTEMA', 'ADMIN', NULL,
     '$2y$12$phcy3BPlFcCgyyLYG1yiV.vv4YO4dgUiKMjJh3q5LgAoBFGjlhLXK', 1, 1)
ON DUPLICATE KEY UPDATE
    usuario = 'admin',
    nombre = VALUES(nombre),
    clave_hash = VALUES(clave_hash),
    clave_predeterminada = 1,
    version_sesion = version_sesion + 1,
    activo = 1,
    eliminado_en = NULL,
    eliminado_por = NULL;

INSERT IGNORE INTO usuario_configuraciones (id_usuario)
SELECT id_usuario FROM usuarios WHERE usuario = 'admin';

INSERT INTO permisos (codigo, nombre, descripcion) VALUES
    ('usuarios.ver', 'Ver usuarios', 'Consulta de usuarios y sus roles'),
    ('usuarios.editar', 'Administrar usuarios', 'Alta, edición, activación y restablecimiento de claves'),
    ('roles.ver', 'Ver roles', 'Consulta de roles'),
    ('roles.editar', 'Administrar roles', 'Alta, edición y asignación de permisos'),
    ('permisos.ver', 'Ver permisos', 'Consulta de permisos'),
    ('permisos.editar', 'Administrar permisos', 'Alta y edición de permisos'),
    ('perfil.editar', 'Editar perfil propio', 'Configuración, firma y sello del usuario')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    activo = 1;

INSERT IGNORE INTO usuario_rol (id_usuario, id_rol)
SELECT u.id_usuario, r.id_rol
FROM usuarios u
INNER JOIN roles r ON r.codigo = 'ADMIN'
WHERE u.usuario = 'admin';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'ADMIN';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
    'pacientes.ver', 'estudios.ver', 'estudios.editar',
    'reportes.imprimir', 'perfil.editar'
)
WHERE r.codigo = 'PROFESIONAL';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
    'escuelas.ver', 'escuelas.editar', 'pacientes.ver', 'pacientes.editar', 'perfil.editar'
)
WHERE r.codigo = 'RECEPCION';

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT m.id_menu, p.id_permiso, 'seguridad.roles', 'ROLES', 'roles.php', 20, 0
FROM menus m
INNER JOIN permisos p ON p.codigo = 'roles.ver'
WHERE m.codigo = 'seguridad'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre),
    id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta),
    url = VALUES(url),
    orden = VALUES(orden),
    visible_todos = 0;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT m.id_menu, p.id_permiso, 'seguridad.permisos', 'PERMISOS', 'permisos.php', 30, 0
FROM menus m
INNER JOIN permisos p ON p.codigo = 'permisos.ver'
WHERE m.codigo = 'seguridad'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre),
    id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta),
    url = VALUES(url),
    orden = VALUES(orden),
    visible_todos = 0;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT NULL, p.id_permiso, 'perfil', 'MI PERFIL', 'perfil.php', 60, 1
FROM permisos p
WHERE p.codigo = 'perfil.editar'
ON DUPLICATE KEY UPDATE
    id_menu_padre = NULL,
    id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta),
    url = VALUES(url),
    orden = VALUES(orden),
    visible_todos = 1;

DROP TRIGGER IF EXISTS trg_usuarios_ai_auditoria;
CREATE TRIGGER trg_usuarios_ai_auditoria
AFTER INSERT ON usuarios FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuarios', 'accion', 'CREAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_usuario,
    'nuevo', JSON_OBJECT(
        'usuario', NEW.usuario, 'nombre', NEW.nombre, 'ci', NEW.ci,
        'correo', NEW.correo, 'activo', NEW.activo,
        'clave_predeterminada', NEW.clave_predeterminada
    ),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_usuarios_au_auditoria;
CREATE TRIGGER trg_usuarios_au_auditoria
AFTER UPDATE ON usuarios FOR EACH ROW
INSERT INTO auditorias (datos)
SELECT JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuarios',
    'accion', IF(
        OLD.activo <> NEW.activo,
        IF(NEW.activo = 1, 'ACTIVAR', 'DESACTIVAR'),
        IF(OLD.clave_hash <> NEW.clave_hash, 'CAMBIAR_CLAVE', 'MODIFICAR')
    ),
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_usuario,
    'anterior', JSON_OBJECT(
        'usuario', OLD.usuario, 'nombre', OLD.nombre, 'ci', OLD.ci,
        'correo', OLD.correo, 'activo', OLD.activo,
        'clave_predeterminada', OLD.clave_predeterminada
    ),
    'nuevo', JSON_OBJECT(
        'usuario', NEW.usuario, 'nombre', NEW.nombre, 'ci', NEW.ci,
        'correo', NEW.correo, 'activo', NEW.activo,
        'clave_predeterminada', NEW.clave_predeterminada
    ),
    'usuario_bd', CURRENT_USER()
)
WHERE NOT (OLD.usuario <=> NEW.usuario)
   OR NOT (OLD.nombre <=> NEW.nombre)
   OR NOT (OLD.ci <=> NEW.ci)
   OR NOT (OLD.correo <=> NEW.correo)
   OR OLD.activo <> NEW.activo
   OR OLD.clave_predeterminada <> NEW.clave_predeterminada
   OR OLD.clave_hash <> NEW.clave_hash;
