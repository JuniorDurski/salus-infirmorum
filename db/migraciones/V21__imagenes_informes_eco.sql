ALTER TABLE postulantes_informes_eco
    ADD COLUMN imagen_1 VARCHAR(512) NULL AFTER resultados,
    ADD COLUMN imagen_2 VARCHAR(512) NULL AFTER imagen_1;
