-- El código cargado a mano pertenece al registro del postulante en un convenio,
-- igual que el orden: pasa de pacientes a postulantes_arreador_postulante, al
-- lado de orden. Se copia al último registro de cada paciente; los pacientes sin
-- registro en postulantes pierden el código (no tienen dónde guardarlo).
ALTER TABLE postulantes_arreador_postulante
    ADD COLUMN codigo VARCHAR(20) NULL AFTER orden;

UPDATE postulantes_arreador_postulante ap
INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
SET ap.codigo = NULLIF(TRIM(p.codigo), '')
WHERE ap.id_arreador_postulante = (
    SELECT ultimo.id_arreador_postulante
    FROM (
        SELECT id_arreador_postulante, id_paciente,
               ROW_NUMBER() OVER (
                   PARTITION BY id_paciente
                   ORDER BY derivado_en DESC, id_arreador_postulante DESC
               ) AS posicion
        FROM postulantes_arreador_postulante
    ) ultimo
    WHERE ultimo.id_paciente = ap.id_paciente AND ultimo.posicion = 1
);

ALTER TABLE pacientes DROP COLUMN codigo;
