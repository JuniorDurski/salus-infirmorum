-- Orden del paciente guardado en su propia columna, separado del id y del código.
-- Se numera desde 1 entre los pacientes activos de la misma escuela y el mismo
-- día de registro. El día de registro es el de su ingreso a postulantes
-- (postulantes_arreador_postulante.derivado_en) o, si nunca ingresó, su alta.
-- La aplicación lo recalcula en backend/codigos_pacientes.php.
ALTER TABLE pacientes
    ADD COLUMN orden SMALLINT UNSIGNED NULL AFTER codigo;

CREATE TEMPORARY TABLE tmp_orden_pacientes AS
SELECT id_paciente,
       ROW_NUMBER() OVER (
           PARTITION BY escuela_ref, DATE(momento)
           ORDER BY momento, id_paciente
       ) AS nro
FROM (
    SELECT p.id_paciente,
           COALESCE(p.id_escuela, 0) AS escuela_ref,
           COALESCE(ult.derivado_en, p.creado_en) AS momento
    FROM pacientes p
    LEFT JOIN (
        SELECT id_paciente, MAX(derivado_en) AS derivado_en
        FROM postulantes_arreador_postulante
        GROUP BY id_paciente
    ) ult ON ult.id_paciente = p.id_paciente
    WHERE p.activo = 1
) base;

UPDATE pacientes p
INNER JOIN tmp_orden_pacientes t ON t.id_paciente = p.id_paciente
SET p.orden = t.nro;

DROP TEMPORARY TABLE tmp_orden_pacientes;
