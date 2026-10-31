-- Separa las rutas de PACIENTES y POSTULANTES sin duplicar los datos clínicos.
-- También normaliza los nombres de archivo de Informe LAB y Estudios Postulantes.

UPDATE menus
SET etiqueta = 'PACIENTES', url = 'pacientes.php', activo = 1
WHERE codigo = 'pacientes';

UPDATE menus
SET etiqueta = 'POSTULANTES', url = 'postulantes.php', activo = 1
WHERE codigo = 'informes.postulantes';

UPDATE menus
SET etiqueta = 'INFORME LAB', url = 'informe_lab.php', activo = 1
WHERE codigo = 'informes.laboratorio';

-- Conserva el acceso desde REFERENCIALES, pero usa el nombre de archivo normalizado.
UPDATE menus
SET etiqueta = 'ESTUDIOS POSTULANTES', url = 'estudios_postulantes.php', activo = 1
WHERE codigo = 'referenciales.estudios';

INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('menu.informes.estudios', 'Ver menú ESTUDIOS POSTULANTES',
     'Permite visualizar Estudios Postulantes dentro del módulo POSTULANTES', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.estudios', 'ESTUDIOS POSTULANTES',
       'estudios_postulantes.php', '🧾', 8, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.estudios'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = 0, activo = 1;

-- Hereda los perfiles que ya podían ver o administrar el catálogo.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo IN (
       'menu.referenciales.estudios',
       'postulantes_estudios.ver',
       'postulantes_estudios.editar'
   )
CROSS JOIN permisos nuevo
WHERE nuevo.codigo = 'menu.informes.estudios';

-- Si un perfil recibe Estudios Postulantes, también debe poder abrir su menú padre.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, padre.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos estudios
    ON estudios.id_permiso = acceso.id_permiso
   AND estudios.codigo = 'menu.informes.estudios'
CROSS JOIN permisos padre
WHERE padre.codigo = 'menu.informes';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'ADMIN'
  AND permiso.codigo IN ('menu.informes', 'menu.informes.estudios');
