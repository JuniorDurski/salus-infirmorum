CREATE TABLE IF NOT EXISTS postulantes_informes_eco (
    id_informe_eco BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_postulante INT UNSIGNED NOT NULL,
    codigo_estudio VARCHAR(50) NOT NULL,
    resultados JSON NOT NULL,
    actualizado_por INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_informe_eco),
    UNIQUE KEY uk_postulante_informe_eco (id_postulante, codigo_estudio),
    KEY ix_informe_eco_estudio (codigo_estudio),
    CONSTRAINT fk_informe_eco_postulante
        FOREIGN KEY (id_postulante) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_informe_eco_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    CONSTRAINT ck_informe_eco_resultados CHECK (JSON_VALID(resultados))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('reportes.eco.imprimir', 'Imprimir informes ECO',
     'Busca postulantes y genera informes de ecografía por tipo de estudio', 1),
    ('reportes.eco.editar', 'Editar resultados ECO',
     'Edita y guarda los resultados individuales de los informes de ecografía', 1),
    ('menu.informes.eco', 'Ver menú INFORME ECO',
     'Permite visualizar el módulo INFORME ECO dentro de POSTULANTES', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.eco', 'INFORME ECO',
       'informe_eco.php', '◫', 5, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.eco'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = 0, activo = 1;

-- INFORME ECO parte con los mismos roles funcionales que INFORME RX.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo IN ('reportes.rx.imprimir', 'reportes.imprimir')
CROSS JOIN permisos nuevo
WHERE nuevo.codigo = 'reportes.eco.imprimir';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, editar.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos imprimir
    ON imprimir.id_permiso = acceso.id_permiso
   AND imprimir.codigo = 'reportes.eco.imprimir'
CROSS JOIN permisos editar
WHERE editar.codigo = 'reportes.eco.editar';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, menu.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos imprimir
    ON imprimir.id_permiso = acceso.id_permiso
   AND imprimir.codigo = 'reportes.eco.imprimir'
CROSS JOIN permisos menu
WHERE menu.codigo = 'menu.informes.eco';

INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo = 'ADMIN'
  AND permiso.codigo IN ('reportes.eco.imprimir', 'reportes.eco.editar', 'menu.informes.eco');

