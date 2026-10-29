-- Informe de audiometría: un diagnóstico (DX) editable por postulante.
CREATE TABLE IF NOT EXISTS postulantes_informes_audiometria (
    id_informe_audiometria BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_postulante INT UNSIGNED NOT NULL,
    dx VARCHAR(2000) NOT NULL,
    actualizado_por INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_informe_audiometria),
    UNIQUE KEY uk_postulante_informe_audiometria (id_postulante),
    CONSTRAINT fk_informe_audiometria_postulante
        FOREIGN KEY (id_postulante) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_informe_audiometria_actualizado_por
        FOREIGN KEY (actualizado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('reportes.audiometria.imprimir', 'Imprimir informes de audiometría',
     'Busca postulantes y genera informes de audiometría', 1),
    ('reportes.audiometria.editar', 'Editar DX de audiometría',
     'Edita y guarda el diagnóstico de los informes de audiometría', 1),
    ('menu.informes.audiometria', 'Ver menú INFORME AUDIOMETRÍA',
     'Permite visualizar el módulo INFORME AUDIOMETRÍA dentro de POSTULANTES', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre), descripcion = VALUES(descripcion), activo = 1;

INSERT INTO menus
    (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo)
SELECT padre.id_menu, permiso.id_permiso, 'informes.audiometria', 'INFORME AUDIOMETRÍA',
       'informe_audiometria.php', '👂', 6, 0, 1
FROM menus padre
INNER JOIN permisos permiso ON permiso.codigo = 'menu.informes.audiometria'
WHERE padre.codigo = 'informes'
ON DUPLICATE KEY UPDATE
    id_menu_padre = VALUES(id_menu_padre), id_permiso = VALUES(id_permiso),
    etiqueta = VALUES(etiqueta), url = VALUES(url), icono = VALUES(icono),
    orden = VALUES(orden), visible_todos = 0, activo = 1;

-- Mismos roles funcionales que INFORME RX (igual que se hizo con INFORME ECO).
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
SELECT acceso.id_rol, nuevo.id_permiso, acceso.asignado_por
FROM rol_permisos acceso
INNER JOIN permisos anterior
    ON anterior.id_permiso = acceso.id_permiso
   AND anterior.codigo IN ('reportes.rx.imprimir', 'reportes.imprimir')
CROSS JOIN permisos nuevo
WHERE nuevo.codigo IN (
    'reportes.audiometria.imprimir',
    'reportes.audiometria.editar',
    'menu.informes.audiometria'
);

-- ADMIN y PROFESIONAL acceden, imprimen y editan.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT rol.id_rol, permiso.id_permiso
FROM roles rol
CROSS JOIN permisos permiso
WHERE rol.codigo IN ('ADMIN', 'PROFESIONAL')
  AND permiso.codigo IN (
      'reportes.audiometria.imprimir',
      'reportes.audiometria.editar',
      'menu.informes.audiometria'
  );
