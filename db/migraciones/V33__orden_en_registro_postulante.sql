-- El orden pasa del paciente a su registro en postulantes: cada registro se
-- numera dentro de su convenio y día de registro desde el orden_inicio del
-- convenio. Los registros de pacientes inactivos quedan sin orden.
-- La aplicación lo recalcula en backend/codigos_pacientes.php.
ALTER TABLE postulantes_arreador_postulante
    ADD COLUMN orden SMALLINT UNSIGNED NULL AFTER derivado_en,
    ADD KEY ix_postulantes_arreador_postulante_orden (id_arreador_escuela, derivado_en, orden);

CREATE TEMPORARY TABLE tmp_orden_registros AS
SELECT ap.id_arreador_postulante,
       ae.orden_inicio - 1 + ROW_NUMBER() OVER (
           PARTITION BY ap.id_arreador_escuela, DATE(ap.derivado_en)
           ORDER BY ap.derivado_en, ap.id_arreador_postulante
       ) AS nro
FROM postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente AND p.activo = 1
INNER JOIN postulantes_arreador_escuela ae ON ae.id_arreador_escuela = ap.id_arreador_escuela;

UPDATE postulantes_arreador_postulante ap
INNER JOIN tmp_orden_registros t ON t.id_arreador_postulante = ap.id_arreador_postulante
SET ap.orden = t.nro;

DROP TEMPORARY TABLE tmp_orden_registros;

ALTER TABLE pacientes DROP COLUMN orden;
