INSERT INTO paises (codigo_iso2, codigo_iso3, nombre, prefijo_telefonico) VALUES
    ('AR', 'ARG', 'Argentina', '+54'),
    ('BO', 'BOL', 'Bolivia', '+591'),
    ('BR', 'BRA', 'Brasil', '+55'),
    ('CL', 'CHL', 'Chile', '+56'),
    ('CO', 'COL', 'Colombia', '+57'),
    ('EC', 'ECU', 'Ecuador', '+593'),
    ('ES', 'ESP', 'España', '+34'),
    ('MX', 'MEX', 'México', '+52'),
    ('PY', 'PRY', 'Paraguay', '+595'),
    ('PE', 'PER', 'Perú', '+51'),
    ('US', 'USA', 'Estados Unidos', '+1'),
    ('UY', 'URY', 'Uruguay', '+598')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    prefijo_telefonico = VALUES(prefijo_telefonico),
    activo = 1;

INSERT INTO titulos (titulo, abreviatura, es_femenino) VALUES
    ('Doctor', 'Dr.', 0),
    ('Doctora', 'Dra.', 1),
    ('Licenciado', 'Lic.', 0),
    ('Licenciada', 'Lic.', 1),
    ('Bioquímico', 'Bioq.', 0),
    ('Bioquímica', 'Bioq.', 1)
ON DUPLICATE KEY UPDATE abreviatura = VALUES(abreviatura), activo = 1;

INSERT INTO roles (codigo, nombre, descripcion) VALUES
    ('ADMIN', 'Administrador', 'Acceso total al sistema'),
    ('PROFESIONAL', 'Profesional de salud', 'Gestiona estudios y resultados'),
    ('RECEPCION', 'Recepción', 'Gestiona pacientes y derivaciones')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO permisos (codigo, nombre) VALUES
    ('seguridad.administrar', 'Administrar seguridad'),
    ('escuelas.ver', 'Ver escuelas'),
    ('escuelas.editar', 'Editar escuelas'),
    ('pacientes.ver', 'Ver pacientes'),
    ('pacientes.editar', 'Editar pacientes'),
    ('estudios.ver', 'Ver estudios'),
    ('estudios.editar', 'Editar estudios'),
    ('reportes.imprimir', 'Imprimir reportes')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
CROSS JOIN permisos p
WHERE r.codigo = 'ADMIN'
ON DUPLICATE KEY UPDATE id_rol = VALUES(id_rol);

INSERT INTO tipos_estudio (codigo, nombre) VALUES
    ('LABORATORIO', 'Laboratorio'),
    ('IMAGENOLOGIA', 'Imagenología'),
    ('CONSULTA', 'Consulta clínica'),
    ('OTRO', 'Otro estudio')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;

INSERT INTO plantillas_estudio
    (id_tipo_estudio, codigo, nombre, version, cabecera, plantilla, calculador)
SELECT
    id_tipo_estudio,
    'LABORATORIO_MOCK',
    'Resultados simulados de laboratorio',
    1,
    JSON_OBJECT('titulo', 'Informe de laboratorio', 'es_mock', true),
    JSON_OBJECT('formato', 'clave-valor', 'resultados', JSON_OBJECT()),
    'resultado_propuesto'
FROM tipos_estudio
WHERE codigo = 'LABORATORIO'
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    cabecera = VALUES(cabecera),
    plantilla = VALUES(plantilla),
    calculador = VALUES(calculador),
    activo = 1;

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
VALUES (NULL, NULL, 'inicio', 'INICIO', 'index.php', 10, 1)
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), url = VALUES(url), orden = VALUES(orden), visible_todos = VALUES(visible_todos);

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT NULL, id_permiso, 'escuelas', 'ESCUELAS', 'designaciones.php', 20, 1
FROM permisos WHERE codigo = 'escuelas.ver'
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), url = VALUES(url), id_permiso = VALUES(id_permiso), orden = VALUES(orden), visible_todos = VALUES(visible_todos);

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT NULL, id_permiso, 'pacientes', 'PACIENTES', 'pacientes.php', 30, 1
FROM permisos WHERE codigo = 'pacientes.ver'
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), url = VALUES(url), id_permiso = VALUES(id_permiso), orden = VALUES(orden), visible_todos = VALUES(visible_todos);

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT NULL, id_permiso, 'informes', 'IMPRIMIR INFORMES', 'imprimir.php', 40, 1
FROM permisos WHERE codigo = 'reportes.imprimir'
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), url = VALUES(url), id_permiso = VALUES(id_permiso), orden = VALUES(orden), visible_todos = VALUES(visible_todos);

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT NULL, id_permiso, 'seguridad', 'SEGURIDAD', NULL, 50, 0
FROM permisos WHERE codigo = 'seguridad.administrar'
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta), id_permiso = VALUES(id_permiso), orden = VALUES(orden), visible_todos = VALUES(visible_todos);

INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, orden, visible_todos)
SELECT m.id_menu, p.id_permiso, 'seguridad.usuarios', 'USUARIOS', 'usuarios.php', 10, 0
FROM menus m
JOIN permisos p ON p.codigo = 'seguridad.administrar'
WHERE m.codigo = 'seguridad'
ON DUPLICATE KEY UPDATE id_menu_padre = VALUES(id_menu_padre), etiqueta = VALUES(etiqueta), url = VALUES(url), id_permiso = VALUES(id_permiso), orden = VALUES(orden);
