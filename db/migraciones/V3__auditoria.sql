DROP TRIGGER IF EXISTS trg_usuarios_ai_auditoria;
CREATE TRIGGER trg_usuarios_ai_auditoria
AFTER INSERT ON usuarios FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuarios', 'accion', 'CREAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_usuario,
    'nuevo', JSON_OBJECT('nombre', NEW.nombre, 'ci', NEW.ci, 'correo', NEW.correo, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_usuarios_au_auditoria;
CREATE TRIGGER trg_usuarios_au_auditoria
AFTER UPDATE ON usuarios FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuarios',
    'accion', IF(OLD.activo <> NEW.activo, IF(NEW.activo = 1, 'ACTIVAR', 'DESACTIVAR'), 'MODIFICAR'),
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_usuario,
    'anterior', JSON_OBJECT('nombre', OLD.nombre, 'ci', OLD.ci, 'correo', OLD.correo, 'activo', OLD.activo),
    'nuevo', JSON_OBJECT('nombre', NEW.nombre, 'ci', NEW.ci, 'correo', NEW.correo, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_roles_ai_auditoria;
CREATE TRIGGER trg_roles_ai_auditoria
AFTER INSERT ON roles FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'roles', 'accion', 'CREAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_rol,
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'nombre', NEW.nombre, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_roles_au_auditoria;
CREATE TRIGGER trg_roles_au_auditoria
AFTER UPDATE ON roles FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'roles', 'accion', 'MODIFICAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_rol,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'nombre', OLD.nombre, 'activo', OLD.activo),
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'nombre', NEW.nombre, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_roles_ad_auditoria;
CREATE TRIGGER trg_roles_ad_auditoria
AFTER DELETE ON roles FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'roles', 'accion', 'ELIMINAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', OLD.id_rol,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'nombre', OLD.nombre, 'activo', OLD.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_permisos_ai_auditoria;
CREATE TRIGGER trg_permisos_ai_auditoria
AFTER INSERT ON permisos FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'permisos', 'accion', 'CREAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_permiso,
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'nombre', NEW.nombre, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_permisos_au_auditoria;
CREATE TRIGGER trg_permisos_au_auditoria
AFTER UPDATE ON permisos FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'permisos', 'accion', 'MODIFICAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_permiso,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'nombre', OLD.nombre, 'activo', OLD.activo),
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'nombre', NEW.nombre, 'activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_permisos_ad_auditoria;
CREATE TRIGGER trg_permisos_ad_auditoria
AFTER DELETE ON permisos FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'permisos', 'accion', 'ELIMINAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', OLD.id_permiso,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'nombre', OLD.nombre, 'activo', OLD.activo),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_usuario_rol_ai_auditoria;
CREATE TRIGGER trg_usuario_rol_ai_auditoria
AFTER INSERT ON usuario_rol FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuario_rol', 'accion', 'ASIGNAR_ROL',
    'actor_usuario_id', COALESCE(NEW.asignado_por, @app_usuario_id),
    'registro', JSON_OBJECT('id_usuario', NEW.id_usuario, 'id_rol', NEW.id_rol),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_usuario_rol_ad_auditoria;
CREATE TRIGGER trg_usuario_rol_ad_auditoria
AFTER DELETE ON usuario_rol FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'usuario_rol', 'accion', 'QUITAR_ROL',
    'actor_usuario_id', @app_usuario_id,
    'registro', JSON_OBJECT('id_usuario', OLD.id_usuario, 'id_rol', OLD.id_rol),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_rol_permisos_ai_auditoria;
CREATE TRIGGER trg_rol_permisos_ai_auditoria
AFTER INSERT ON rol_permisos FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'rol_permisos', 'accion', 'ASIGNAR_PERMISO',
    'actor_usuario_id', COALESCE(NEW.asignado_por, @app_usuario_id),
    'registro', JSON_OBJECT('id_rol', NEW.id_rol, 'id_permiso', NEW.id_permiso),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_rol_permisos_ad_auditoria;
CREATE TRIGGER trg_rol_permisos_ad_auditoria
AFTER DELETE ON rol_permisos FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'rol_permisos', 'accion', 'QUITAR_PERMISO',
    'actor_usuario_id', @app_usuario_id,
    'registro', JSON_OBJECT('id_rol', OLD.id_rol, 'id_permiso', OLD.id_permiso),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_menus_ai_auditoria;
CREATE TRIGGER trg_menus_ai_auditoria
AFTER INSERT ON menus FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'menus', 'accion', 'CREAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_menu,
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'etiqueta', NEW.etiqueta, 'permiso_id', NEW.id_permiso, 'visible_todos', NEW.visible_todos),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_menus_au_auditoria;
CREATE TRIGGER trg_menus_au_auditoria
AFTER UPDATE ON menus FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'menus', 'accion', 'MODIFICAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_menu,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'etiqueta', OLD.etiqueta, 'permiso_id', OLD.id_permiso, 'visible_todos', OLD.visible_todos),
    'nuevo', JSON_OBJECT('codigo', NEW.codigo, 'etiqueta', NEW.etiqueta, 'permiso_id', NEW.id_permiso, 'visible_todos', NEW.visible_todos),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_menus_ad_auditoria;
CREATE TRIGGER trg_menus_ad_auditoria
AFTER DELETE ON menus FOR EACH ROW
INSERT INTO auditorias (datos) VALUES (JSON_OBJECT(
    'modulo', 'SEGURIDAD', 'entidad', 'menus', 'accion', 'ELIMINAR',
    'actor_usuario_id', @app_usuario_id, 'registro_id', OLD.id_menu,
    'anterior', JSON_OBJECT('codigo', OLD.codigo, 'etiqueta', OLD.etiqueta),
    'usuario_bd', CURRENT_USER()
));

DROP TRIGGER IF EXISTS trg_pacientes_au_auditoria;
CREATE TRIGGER trg_pacientes_au_auditoria
AFTER UPDATE ON pacientes FOR EACH ROW
INSERT INTO auditorias (datos)
SELECT JSON_OBJECT(
    'modulo', 'PACIENTES', 'entidad', 'pacientes',
    'accion', IF(NEW.activo = 1, 'ACTIVAR', 'DESACTIVAR'),
    'actor_usuario_id', @app_usuario_id, 'registro_id', NEW.id_paciente,
    'anterior', JSON_OBJECT('activo', OLD.activo),
    'nuevo', JSON_OBJECT('activo', NEW.activo),
    'usuario_bd', CURRENT_USER()
)
WHERE OLD.activo <> NEW.activo;
