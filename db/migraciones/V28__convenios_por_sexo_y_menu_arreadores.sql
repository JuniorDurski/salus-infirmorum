-- Tarifas diferenciadas por sexo y monto manual para la clínica.
ALTER TABLE postulantes_arreador_escuela
    DROP CONSTRAINT ck_postulantes_arreador_escuela_montos,
    ADD COLUMN monto_total_hombre DECIMAL(12,2) NULL AFTER anio,
    ADD COLUMN monto_total_mujer DECIMAL(12,2) NULL AFTER monto_total_hombre,
    ADD COLUMN monto_clinica_manual DECIMAL(12,2) NULL AFTER comision_arreador;

UPDATE postulantes_arreador_escuela
SET monto_total_hombre = monto_total,
    monto_total_mujer = monto_total,
    monto_clinica_manual = monto_clinica;

ALTER TABLE postulantes_arreador_escuela
    DROP COLUMN monto_clinica,
    DROP COLUMN monto_total,
    CHANGE COLUMN monto_clinica_manual monto_clinica DECIMAL(12,2) NOT NULL,
    MODIFY COLUMN monto_total_hombre DECIMAL(12,2) NOT NULL,
    MODIFY COLUMN monto_total_mujer DECIMAL(12,2) NOT NULL,
    ADD CONSTRAINT ck_postulantes_arreador_escuela_montos
        CHECK (
            monto_total_hombre > 0
            AND monto_total_mujer > 0
            AND comision_arreador >= 0
            AND comision_arreador <= monto_total_hombre
            AND comision_arreador <= monto_total_mujer
            AND monto_clinica >= 0
        );

-- Nuevo submódulo de configuración dentro de POSTULANTES.
INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('menu.informes.arreadores', 'Ver menú ARREADORES Y CONVENIOS',
     'Permite visualizar la configuración de arreadores y convenios de postulantes', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.arreadores', 'ARREADORES Y CONVENIOS',
       'arreadores.php', '🤝', 30, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.arreadores'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = 0, activo = 1;

-- Conserva los mismos perfiles que ya pueden abrir POSTULANTES.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo = 'menu.informes.postulantes'
CROSS JOIN permisos nuevo
WHERE nuevo.codigo = 'menu.informes.arreadores';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'ADMIN'
  AND permiso.codigo IN ('menu.informes', 'menu.informes.arreadores');

UPDATE menus
SET orden = CASE codigo
    WHEN 'informes.escuelas' THEN 10
    WHEN 'informes.estudios' THEN 20
    WHEN 'informes.arreadores' THEN 30
    WHEN 'informes.postulantes' THEN 40
    WHEN 'informes.laboratorio' THEN 50
    WHEN 'informes.rx' THEN 60
    WHEN 'informes.eco' THEN 70
    WHEN 'informes.audiometria' THEN 80
    WHEN 'informes.rotulos' THEN 90
    WHEN 'informes.gestion' THEN 100
    ELSE orden
END
WHERE codigo IN (
    'informes.escuelas',
    'informes.estudios',
    'informes.arreadores',
    'informes.postulantes',
    'informes.laboratorio',
    'informes.rx',
    'informes.eco',
    'informes.audiometria',
    'informes.rotulos',
    'informes.gestion'
);
