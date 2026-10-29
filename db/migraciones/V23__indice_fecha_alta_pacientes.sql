-- El código generado de los pacientes ("NNN-ID") cuenta los pacientes dados de alta
-- el mismo día; este índice permite hacerlo sin recorrer toda la tabla.
CREATE INDEX ix_pacientes_creado_en ON pacientes (creado_en, id_paciente);
