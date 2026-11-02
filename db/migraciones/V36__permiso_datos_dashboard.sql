-- Los indicadores, gráficos y ganancias del panel principal quedan protegidos por
-- un permiso propio. Por ahora solo lo tiene el rol ADMIN; el resto de los
-- usuarios ve el saludo y los accesos rápidos.
INSERT INTO permisos (codigo, nombre, descripcion)
VALUES ('dashboard.ver', 'Ver datos del panel principal',
        'Indicadores, cobros, ganancias y gráficos del panel principal');

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo = 'dashboard.ver'
WHERE r.codigo = 'ADMIN';
