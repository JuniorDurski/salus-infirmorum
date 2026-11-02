-- Las observaciones del registro del postulante pasan de texto libre a una lista
-- JSON de comentarios, como en atención: cada comentario guarda su autor y fecha.
--   [{"id": "...", "texto": "...", "id_usuario": 1, "usuario": "NOMBRE", "fecha": "2026-11-02T10:00:00-03:00"}]
-- El texto existente se conserva como primer comentario, a nombre de quien
-- registró al postulante y con la fecha del registro.
UPDATE postulantes_arreador_postulante ap
LEFT JOIN usuarios u ON u.id_usuario = ap.registrado_por
SET ap.observacion = IF(
    NULLIF(TRIM(ap.observacion), '') IS NULL,
    NULL,
    JSON_ARRAY(JSON_OBJECT(
        'id', LEFT(MD5(CONCAT(ap.id_arreador_postulante, '-', RAND())), 16),
        'texto', TRIM(ap.observacion),
        'id_usuario', ap.registrado_por,
        'usuario', COALESCE(u.nombre, 'SISTEMA'),
        'fecha', DATE_FORMAT(ap.derivado_en, '%Y-%m-%dT%H:%i:%s')
    ))
);

ALTER TABLE postulantes_arreador_postulante
    MODIFY observacion JSON NULL;
