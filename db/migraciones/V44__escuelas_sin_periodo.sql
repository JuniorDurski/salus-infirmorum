-- Las escuelas dejan de tener periodo (año/mes): el sistema ya no lo usa.
-- Primero se quita el CHECK que lo valida y después la columna.
ALTER TABLE escuelas
    DROP CONSTRAINT ck_escuelas_periodo;

ALTER TABLE escuelas
    DROP COLUMN periodo;
