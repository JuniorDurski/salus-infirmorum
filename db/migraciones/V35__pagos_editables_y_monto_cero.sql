-- Los pagos pueden registrarse con importe 0 (por ejemplo, para dejar constancia
-- del método o una observación) y pueden corregirse aunque el saldo ya esté
-- cancelado. Se guarda quién hizo la última modificación y cuándo.
ALTER TABLE postulantes_pago
    DROP CONSTRAINT ck_postulantes_pago_monto;

ALTER TABLE postulantes_pago
    ADD CONSTRAINT ck_postulantes_pago_monto CHECK (monto >= 0),
    ADD COLUMN modificado_por INT UNSIGNED NULL AFTER creado_en,
    ADD COLUMN modificado_en DATETIME NULL AFTER modificado_por,
    ADD CONSTRAINT fk_postulantes_pago_modificado_por
        FOREIGN KEY (modificado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL;
