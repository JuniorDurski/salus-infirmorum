-- El menú RX solo debe mostrarse a roles que también puedan abrir el módulo.
-- Corrige instalaciones donde el permiso del padre POSTULANTES tenía un
-- alcance más amplio que el permiso funcional de impresión.
DELETE acceso_menu
FROM rol_permisos acceso_menu
INNER JOIN permisos permiso_menu
    ON permiso_menu.id_permiso = acceso_menu.id_permiso
   AND permiso_menu.codigo = 'menu.informes.rx'
INNER JOIN roles rol ON rol.id_rol = acceso_menu.id_rol
LEFT JOIN permisos permiso_funcional
    ON permiso_funcional.codigo = 'reportes.rx.imprimir'
LEFT JOIN rol_permisos acceso_funcional
    ON acceso_funcional.id_rol = acceso_menu.id_rol
   AND acceso_funcional.id_permiso = permiso_funcional.id_permiso
WHERE acceso_funcional.id_permiso IS NULL
  AND rol.codigo <> 'ADMIN';
