-- Los módulos funcionales deben respetar sus permisos menu.* por rol.
-- Inicio y perfil permanecen disponibles para todos los usuarios autenticados.
UPDATE menus
SET visible_todos = CASE
    WHEN codigo IN ('inicio', 'perfil') THEN 1
    ELSE 0
END
WHERE codigo IN (
    'inicio', 'perfil', 'escuelas', 'pacientes', 'informes',
    'seguridad', 'seguridad.usuarios', 'seguridad.roles',
    'seguridad.permisos', 'seguridad.menus'
);
