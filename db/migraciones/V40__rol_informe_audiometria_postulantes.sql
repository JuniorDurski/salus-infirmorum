-- Rol para quien carga e imprime las audiometrías de postulantes, equivalente al
-- rol INFO_ECO_POSTULANTES: menú POSTULANTES > INFORME AUDIO, ver y editar el DX,
-- imprimir el informe y consultar escuelas y postulantes para los filtros.
INSERT INTO roles (codigo, nombre, descripcion)
VALUES ('INFO_AUDIO_POSTULANTES', 'Informe de Audiometria - Postulantes',
        'Ve y Edita informe de audiometrías de postulantes');

INSERT INTO rol_permisos (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM roles r
INNER JOIN permisos p ON p.codigo IN (
    'menu.postulantes',
    'menu.postulantes.audiometria',
    'reportes.audiometria.imprimir',
    'reportes.audiometria.editar',
    'pacientes.ver',
    'escuelas.ver'
)
WHERE r.codigo = 'INFO_AUDIO_POSTULANTES';
