-- El estado de atención se deriva exclusivamente de los estudios: debe existir
-- al menos uno y todos deben estar realizados.
UPDATE postulantes_atencion pa
LEFT JOIN (
    SELECT id_postulante_atencion,
           COUNT(*) AS total_estudios,
           SUM(realizado = 1) AS estudios_realizados
    FROM postulantes_atencion_estudios
    GROUP BY id_postulante_atencion
) resumen ON resumen.id_postulante_atencion = pa.id_postulante_atencion
SET pa.atendido = IF(resumen.total_estudios > 0
                      AND resumen.estudios_realizados = resumen.total_estudios, 1, 0),
    pa.atendido_en = IF(resumen.total_estudios > 0
                         AND resumen.estudios_realizados = resumen.total_estudios,
                         COALESCE(pa.atendido_en, NOW()), NULL),
    pa.atendido_por = IF(resumen.total_estudios > 0
                          AND resumen.estudios_realizados = resumen.total_estudios,
                          pa.atendido_por, NULL);
