-- Desactivar un postulante ya no desactiva al paciente: se desactiva solo su
-- registro en postulantes (soft delete, igual que pacientes). Un registro
-- inactivo no aparece en ningún módulo de postulantes, no cuenta en totales y
-- queda sin orden; los demás registros de su convenio y día se renumeran.
ALTER TABLE postulantes_arreador_postulante
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER observacion,
    ADD COLUMN eliminado_en DATETIME NULL AFTER activo,
    ADD COLUMN eliminado_por INT UNSIGNED NULL AFTER eliminado_en,
    ADD KEY ix_postulantes_arreador_postulante_paciente_activo (id_paciente, activo),
    ADD CONSTRAINT fk_postulantes_arreador_postulante_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL;
