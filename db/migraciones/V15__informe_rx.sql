-- Informe radiológico para postulantes, con permisos funcionales y de menú
-- independientes. Los roles parten con el mismo acceso que ya poseen para
-- INFORME LAB y luego pueden configurarse desde Seguridad > Roles.
INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('reportes.rx.imprimir', 'Imprimir informes RX',
     'Busca postulantes y genera informes radiológicos individuales o masivos', 1),
    ('menu.informes.rx', 'Ver menú INFORME RX',
     'Permite visualizar el módulo INFORME RX dentro de POSTULANTES', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.rx', 'INFORME RX',
       'informe_rx.php', '▧', 4, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.rx'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre),
    id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta),
    url = VALUES(url),
    icono = VALUES(icono),
    orden = VALUES(orden),
    visible_todos = 0,
    activo = 1;

-- Hereda inicialmente los roles del permiso funcional de informes existente.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT existente.id_rol, nuevo.id_permiso, existente.asignado_por
FROM rol_permisos existente
INNER JOIN permisos anterior
    ON anterior.id_permiso = existente.id_permiso
   AND anterior.codigo = 'reportes.imprimir'
INNER JOIN permisos nuevo ON nuevo.codigo = 'reportes.rx.imprimir';

-- En instalaciones que ya tienen INFORME LAB como hijo, copia sus roles.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT existente.id_rol, nuevo.id_permiso, existente.asignado_por
FROM rol_permisos existente
INNER JOIN permisos anterior
    ON anterior.id_permiso = existente.id_permiso
   AND anterior.codigo = 'menu.informes.laboratorio'
INNER JOIN permisos nuevo ON nuevo.codigo = 'menu.informes.rx';

-- Respaldo para instalaciones donde INFORME LAB todavía es el propio padre.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT existente.id_rol, nuevo.id_permiso, existente.asignado_por
FROM rol_permisos existente
INNER JOIN permisos anterior
    ON anterior.id_permiso = existente.id_permiso
   AND anterior.codigo = 'menu.informes'
INNER JOIN permisos nuevo ON nuevo.codigo = 'menu.informes.rx';

-- ADMIN conserva acceso aunque la instalación no tuviera asignaciones previas.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'ADMIN'
  AND permiso.codigo IN ('reportes.rx.imprimir', 'menu.informes.rx');
