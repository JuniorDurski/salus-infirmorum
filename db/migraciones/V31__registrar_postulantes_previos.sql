-- Postulantes cargados antes de implementar convenios (29/10 y 31/10/2026):
-- se registran en el convenio GENERAL JUAN CARDENAS (MARISCAL) - ACADEPOL 2026,
-- con su fecha de alta como fecha de registro. No se crean pagos: quedan con el
-- saldo total del convenio.
INSERT INTO postulantes_arreador_postulante
    (id_arreador, id_arreador_escuela, id_paciente, derivado_en, registrado_por)
SELECT ae.id_arreador, ae.id_arreador_escuela, p.id_paciente, p.creado_en, NULL
FROM pacientes p
INNER JOIN escuelas e ON e.id_escuela = p.id_escuela AND e.nombre = 'ACADEPOL'
INNER JOIN postulantes_arreador_escuela ae
        ON ae.id_escuela = e.id_escuela AND ae.anio = 2026
INNER JOIN postulantes_arreadores a
        ON a.id_arreador = ae.id_arreador AND a.nombre = 'GENERAL JUAN CARDENAS'
WHERE p.activo = 1
  AND p.creado_en < '2026-11-02'
  AND NOT EXISTS (
      SELECT 1
      FROM postulantes_arreador_postulante ap
      INNER JOIN postulantes_arreador_escuela ae2
              ON ae2.id_arreador_escuela = ap.id_arreador_escuela
      WHERE ap.id_paciente = p.id_paciente AND ae2.anio = 2026
  );

-- BIANCA MARICEL OVANDO VERON también vino antes: su registro toma la fecha de alta.
UPDATE postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
SET ap.derivado_en = p.creado_en
WHERE p.documento = '5759614';

-- Recalcula el orden con las nuevas fechas de registro (misma regla que
-- recalcularOrdenPacientes() en backend/codigos_pacientes.php).
CREATE TEMPORARY TABLE tmp_orden_pacientes AS
SELECT id_paciente,
       orden_inicio - 1 + ROW_NUMBER() OVER (
           PARTITION BY grupo, DATE(momento)
           ORDER BY momento, id_paciente
       ) AS nro
FROM (
    SELECT p.id_paciente,
           IF(ult.id_arreador_escuela IS NULL,
              CONCAT('e', COALESCE(p.id_escuela, 0)),
              CONCAT('c', ult.id_arreador_escuela)) AS grupo,
           COALESCE(ult.orden_inicio, 1) AS orden_inicio,
           COALESCE(ult.derivado_en, p.creado_en) AS momento
    FROM pacientes p
    LEFT JOIN (
        SELECT x.id_paciente, x.derivado_en, x.id_arreador_escuela, ae.orden_inicio
        FROM (
            SELECT ap.id_paciente, ap.derivado_en, ap.id_arreador_escuela,
                   ROW_NUMBER() OVER (
                       PARTITION BY ap.id_paciente
                       ORDER BY ap.derivado_en DESC, ap.id_arreador_postulante DESC
                   ) AS rn
            FROM postulantes_arreador_postulante ap
        ) x
        INNER JOIN postulantes_arreador_escuela ae
                ON ae.id_arreador_escuela = x.id_arreador_escuela
        WHERE x.rn = 1
    ) ult ON ult.id_paciente = p.id_paciente
    WHERE p.activo = 1
) base;

UPDATE pacientes p
LEFT JOIN tmp_orden_pacientes t ON t.id_paciente = p.id_paciente
SET p.orden = t.nro;

DROP TEMPORARY TABLE tmp_orden_pacientes;
