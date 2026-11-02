-- Número desde el que se numera el orden de los postulantes de cada convenio.
-- Los postulantes de un convenio se numeran por día desde orden_inicio; los
-- pacientes que nunca ingresaron a postulantes siguen numerándose desde 1 por
-- escuela y día. La aplicación lo recalcula en backend/codigos_pacientes.php.
ALTER TABLE postulantes_arreador_escuela
    ADD COLUMN orden_inicio SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER anio,
    ADD CONSTRAINT ck_postulantes_arreador_escuela_orden_inicio CHECK (orden_inicio >= 1);

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
INNER JOIN tmp_orden_pacientes t ON t.id_paciente = p.id_paciente
SET p.orden = t.nro;

DROP TEMPORARY TABLE tmp_orden_pacientes;
