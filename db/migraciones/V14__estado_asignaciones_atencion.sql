-- Las asignaciones se ocultan mediante un estado reversible. Los detalles
-- clínicos y de liquidación se conservan íntegros al desactivar la cabecera.
ALTER TABLE postulantes_asignar_atencion
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER pago_realizado,
    ADD KEY ix_asignacion_activo_fecha (activo, fecha),
    ADD CONSTRAINT ck_asignacion_activo CHECK (activo IN (0, 1));

INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES
    ('postulantes_atencion.estado', 'Activar y desactivar asignaciones',
     'Oculta asignaciones activas y permite restaurarlas desde la sección de desactivadas', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    activo = 1;

-- El nuevo permiso es administrativo. Los demás roles pueden recibirlo de
-- forma explícita desde el módulo de roles.
INSERT IGNORE INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo = 'postulantes_atencion.estado'
WHERE r.codigo = 'ADMIN';
