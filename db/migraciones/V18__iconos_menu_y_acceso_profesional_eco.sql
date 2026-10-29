-- Iconos descriptivos para la navegacion principal y sus modulos.
UPDATE menus
SET icono = CASE codigo
    WHEN 'inicio' THEN '🏠'
    WHEN 'pacientes' THEN '🧑‍⚕️'
    WHEN 'atenciones' THEN '🩺'
    WHEN 'informes' THEN '👮'
    WHEN 'referenciales' THEN '📚'
    WHEN 'seguridad' THEN '🔐'
    WHEN 'perfil' THEN '👤'
    WHEN 'informes.escuelas' THEN '🏫'
    WHEN 'informes.postulantes' THEN '👥'
    WHEN 'informes.laboratorio' THEN '🧪'
    WHEN 'informes.rx' THEN '🩻'
    WHEN 'informes.eco' THEN '🩺'
    WHEN 'informes.gestion' THEN '📋'
    WHEN 'seguridad.usuarios' THEN '👥'
    WHEN 'seguridad.roles' THEN '🪪'
    WHEN 'seguridad.permisos' THEN '🔑'
    WHEN 'seguridad.menus' THEN '🧭'
    WHEN 'referenciales.universidades' THEN '🎓'
    WHEN 'referenciales.titulos' THEN '📜'
    WHEN 'referenciales.estudios' THEN '🧾'
    ELSE icono
END
WHERE codigo IN (
    'inicio', 'pacientes', 'atenciones', 'informes', 'referenciales',
    'seguridad', 'perfil', 'informes.escuelas', 'informes.postulantes',
    'informes.laboratorio', 'informes.rx', 'informes.eco',
    'informes.gestion', 'seguridad.usuarios', 'seguridad.roles',
    'seguridad.permisos', 'seguridad.menus',
    'referenciales.universidades', 'referenciales.titulos',
    'referenciales.estudios'
);

-- El rol profesional puede abrir POSTULANTES, entrar a INFORME ECO,
-- imprimir los estudios y editar sus resultados.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'PROFESIONAL'
  AND permiso.codigo IN (
      'menu.informes',
      'menu.informes.eco',
      'reportes.eco.imprimir',
      'reportes.eco.editar'
  );
