-- Un postulante cuyo registro fue desactivado puede registrarse de nuevo otro día,
-- aunque sea en el mismo convenio. La unicidad (convenio, paciente) se exige solo
-- entre registros activos: la columna generada vale 1 si el registro está activo
-- y NULL si no, y los NULL no chocan en un índice único.
ALTER TABLE postulantes_arreador_postulante
    ADD COLUMN activo_unico TINYINT(1) AS (IF(activo = 1, 1, NULL)) PERSISTENT AFTER activo;

ALTER TABLE postulantes_arreador_postulante
    ADD UNIQUE KEY uk_postulantes_arreador_postulante_activo (id_arreador_escuela, id_paciente, activo_unico);

ALTER TABLE postulantes_arreador_postulante
    DROP INDEX uk_postulantes_arreador_postulante;
