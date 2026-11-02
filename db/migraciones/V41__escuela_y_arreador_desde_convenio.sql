-- La escuela y el arreador del postulante salen del convenio de su registro:
--   pacientes -> postulantes_arreador_postulante -> postulantes_arreador_escuela
--             -> escuelas / postulantes_arreadores
-- pacientes.id_escuela y postulantes_arreador_postulante.id_arreador duplicaban
-- ese dato y se eliminan. Todo registro debe tener convenio.
ALTER TABLE pacientes
    DROP FOREIGN KEY fk_pacientes_escuela;
ALTER TABLE pacientes
    DROP INDEX ix_pacientes_escuela,
    DROP COLUMN id_escuela;

ALTER TABLE postulantes_arreador_postulante
    DROP FOREIGN KEY fk_postulantes_arreador_postulante_arreador;
ALTER TABLE postulantes_arreador_postulante
    DROP INDEX fk_postulantes_arreador_postulante_arreador,
    DROP COLUMN id_arreador,
    MODIFY id_arreador_escuela BIGINT UNSIGNED NOT NULL;
