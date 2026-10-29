INSERT INTO permisos (codigo, nombre, descripcion) VALUES
    ('menus.ver', 'Ver configuración de menús', 'Consulta del árbol de navegación y sus accesos'),
    ('menus.editar', 'Administrar menús', 'Crea, organiza y configura la visibilidad de los menús')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

-- Cada entrada de navegación posee un permiso independiente menu.<ruta>.
INSERT INTO permisos (codigo, nombre, descripcion, activo)
SELECT CONCAT('menu.', m.codigo), CONCAT('Ver menú ', m.etiqueta),
       CONCAT('Permite visualizar el menú ', m.codigo), 1
FROM menus m
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- Conserva los accesos existentes antes de reemplazar el permiso de cada menú.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT DISTINCT rp.id_rol, nuevo.id_permiso, rp.asignado_por
FROM menus m
INNER JOIN rol_permisos rp ON rp.id_permiso = m.id_permiso
INNER JOIN permisos nuevo ON nuevo.codigo = CONCAT('menu.', m.codigo);

UPDATE menus m
INNER JOIN permisos p ON p.codigo = CONCAT('menu.', m.codigo)
SET m.id_permiso = p.id_permiso;

UPDATE menus
SET icono = CASE codigo
    WHEN 'inicio' THEN '⌂'
    WHEN 'escuelas' THEN '▣'
    WHEN 'pacientes' THEN '♥'
    WHEN 'informes' THEN '▤'
    WHEN 'seguridad' THEN '⚙'
    WHEN 'seguridad.usuarios' THEN '♦'
    WHEN 'seguridad.roles' THEN '◈'
    WHEN 'seguridad.permisos' THEN '✓'
    WHEN 'perfil' THEN '●'
    ELSE icono
END
WHERE icono IS NULL;

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r CROSS JOIN permisos p
WHERE r.codigo = 'ADMIN'
  AND (p.codigo IN ('menus.ver', 'menus.editar') OR p.codigo LIKE 'menu.%');

INSERT INTO permisos (codigo, nombre, descripcion, activo)
VALUES ('menu.seguridad.menus', 'Ver menú MENÚS', 'Permite visualizar el menú seguridad.menus', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'seguridad.menus', 'MENÚS', 'menus.php', '☰', 40, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.seguridad.menus'
WHERE padre.codigo = 'seguridad'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = VALUES(visible_todos), activo = 1;

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo IN ('menus.ver', 'menus.editar', 'menu.seguridad.menus')
WHERE r.codigo = 'ADMIN';
