-- Rótulos de postulantes: impresión individual, seleccionada o masiva.
INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('reportes.rotulos.imprimir', 'Imprimir rótulos de postulantes',
     'Busca postulantes por escuela y fecha y genera rótulos individuales o masivos', 1),
    ('menu.informes.rotulos', 'Ver menú IMPRIMIR RÓTULO',
     'Permite visualizar el módulo IMPRIMIR RÓTULO dentro de POSTULANTES', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.rotulos', 'IMPRIMIR RÓTULO',
       'rotulos.php', '🏷️', 7, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.rotulos'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = 0, activo = 1;

-- Parte con los mismos perfiles que ya pueden imprimir y abrir INFORME ECO.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo = 'reportes.eco.imprimir'
CROSS JOIN permisos nuevo
WHERE nuevo.codigo = 'reportes.rotulos.imprimir';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo = 'menu.informes.eco'
CROSS JOIN permisos nuevo
WHERE nuevo.codigo = 'menu.informes.rotulos';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'ADMIN'
  AND permiso.codigo IN ('reportes.rotulos.imprimir', 'menu.informes.rotulos');
