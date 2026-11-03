-- Re-ingreso de un postulante cuyo registro fue desactivado (por ejemplo ayer):
-- al volver a guardarlo se marca reingresado_en. Desde ese momento vuelve a
-- aparecer como pendiente en postulantes y sigue el flujo normal (pagos y nuevo
-- registro del día). El registro anterior no se modifica.
ALTER TABLE pacientes
    ADD COLUMN reingresado_en DATETIME NULL AFTER creado_en;
