-- Datos iniciales. Ejecutar DESPUES de db.sql.
-- Usuario inicial: admin / Salus2026!Inicio
-- Cambiar la clave desde Mi perfil despues del primer ingreso.

INSERT INTO usuarios (nombre, ci, email, password_hash)
VALUES ('Administrador', 'admin', NULL, '$2y$12$aeIwz4OREsmjCbS8mtlKPeK4eCDYzGYQpwG.NsNkK4Z8x/rAvWzpq')
ON DUPLICATE KEY UPDATE id_usuario = id_usuario;

INSERT INTO usuario_configuracion (id_usuario, modo_oscuro, creado_por)
SELECT id_usuario, 0, id_usuario FROM usuarios WHERE ci = 'admin'
ON DUPLICATE KEY UPDATE modo_oscuro = modo_oscuro;

INSERT INTO roles (codigo, nombre, descripcion) VALUES
('ADMIN', 'Administrador', 'Acceso a todos los modulos'),
('ESPECIALISTA', 'Especialista medico', 'Atencion, evaluaciones e informes')
ON DUPLICATE KEY UPDATE codigo = codigo;

INSERT INTO permisos (codigo, nombre, modulo) VALUES
('pacientes.ver', 'Consultar pacientes', 'PACIENTES'),
('pacientes.editar', 'Editar pacientes', 'PACIENTES'),
('escuelas.ver', 'Consultar escuelas', 'ESCUELAS'),
('escuelas.editar', 'Editar escuelas', 'ESCUELAS'),
('evaluaciones.ver', 'Consultar evaluaciones', 'EVALUACIONES'),
('evaluaciones.editar', 'Editar evaluaciones', 'EVALUACIONES'),
('derivadores.ver', 'Consultar derivadores', 'DERIVACIONES'),
('derivadores.editar', 'Editar derivadores', 'DERIVACIONES'),
('informes.generar', 'Generar informes PDF', 'INFORMES'),
('seguridad.ver', 'Consultar seguridad y auditoria', 'SEGURIDAD'),
('seguridad.editar', 'Administrar usuarios y roles', 'SEGURIDAD')
ON DUPLICATE KEY UPDATE codigo = codigo;

INSERT INTO usuario_rol (id_usuario, id_rol, creado_por)
SELECT u.id_usuario, r.id_rol, u.id_usuario FROM usuarios u CROSS JOIN roles r
WHERE u.ci='admin' AND r.codigo='ADMIN'
ON DUPLICATE KEY UPDATE eliminado_en=NULL, eliminado_por=NULL;

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r CROSS JOIN permisos p WHERE r.codigo='ADMIN'
ON DUPLICATE KEY UPDATE eliminado_en=NULL, eliminado_por=NULL;

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso FROM roles r CROSS JOIN permisos p
WHERE r.codigo='ESPECIALISTA' AND p.codigo IN
('pacientes.ver','pacientes.editar','escuelas.ver','evaluaciones.ver','evaluaciones.editar','derivadores.ver','derivadores.editar','informes.generar')
ON DUPLICATE KEY UPDATE eliminado_en=NULL, eliminado_por=NULL;

INSERT INTO menus (codigo, nombre, ruta, orden, visible_todos) VALUES
('INICIO', 'Panel general', 'index.php', 1, 1),
('PACIENTES', 'Pacientes y evaluaciones', 'pacientes.php', 2, 0),
('DERIVACIONES', 'Derivaciones', 'derivadores.php', 3, 0),
('INFORMES', 'Informes PDF', 'imprimir.php', 4, 0),
('ADMINISTRACION', 'Administracion', NULL, 5, 1),
('ESCUELAS', 'Escuelas', 'designaciones.php', 6, 0),
('SEGURIDAD', 'Seguridad', 'usuarios.php', 7, 0),
('PERFIL', 'Mi perfil', 'perfil.php', 8, 1)
ON DUPLICATE KEY UPDATE codigo=codigo;

UPDATE menus child JOIN menus parent ON parent.codigo='ADMINISTRACION'
SET child.id_menu_padre=parent.id_menu
WHERE child.codigo IN ('ESCUELAS','SEGURIDAD');

INSERT INTO menu_permiso (id_menu, id_permiso)
SELECT m.id_menu,p.id_permiso FROM menus m JOIN permisos p ON
    (m.codigo='PACIENTES' AND p.codigo='pacientes.ver') OR
    (m.codigo='DERIVACIONES' AND p.codigo='derivadores.ver') OR
    (m.codigo='INFORMES' AND p.codigo='informes.generar') OR
    (m.codigo='ESCUELAS' AND p.codigo='escuelas.ver') OR
    (m.codigo='SEGURIDAD' AND p.codigo='seguridad.ver')
ON DUPLICATE KEY UPDATE eliminado_en=NULL, eliminado_por=NULL;

INSERT INTO escuelas (codigo, nombre) VALUES
('ACADEMIL', 'Academia Militar'),
('ISEPOL', 'Instituto Superior de Educacion Policial'),
('ACADEPOL', 'Academia de Policia'),
('COLEPOL', 'Colegio de Policia'),
('DISERGEMIL', 'DISERGEMIL'),
('DISERSANFA', 'DISERSANFA'),
('LICEMIL', 'Liceo Militar'),
('COMISOE', 'COMISOE'),
('DIGETREN', 'DIGETREN'),
('DROGAS', 'Estudios de drogas')
ON DUPLICATE KEY UPDATE codigo=codigo;

INSERT INTO titulos (nombre, abreviatura, para_hombre, para_mujer) VALUES
('Doctor', 'Dr.', 1, 0),
('Doctora', 'Dra.', 0, 1),
('Licenciado', 'Lic.', 1, 0),
('Licenciada', 'Licda.', 0, 1),
('Bioquimico', 'Bq.', 1, 0),
('Bioquimica', 'Bq.', 0, 1)
ON DUPLICATE KEY UPDATE nombre=nombre;
