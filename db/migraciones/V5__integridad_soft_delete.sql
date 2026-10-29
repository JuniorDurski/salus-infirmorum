ALTER TABLE titulos
    ADD CONSTRAINT ck_titulos_genero CHECK (es_femenino IN (0, 1));

ALTER TABLE pacientes
    ADD CONSTRAINT ck_pacientes_genero CHECK (es_femenino IN (0, 1));

ALTER TABLE escuelas
    ADD CONSTRAINT ck_escuelas_periodo CHECK (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')) AS UNSIGNED) BETWEEN 1900 AND 2200
        AND CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')) AS UNSIGNED) BETWEEN 1 AND 12
    );

DROP TRIGGER IF EXISTS trg_usuarios_bd_soft_delete;
CREATE TRIGGER trg_usuarios_bd_soft_delete
BEFORE DELETE ON usuarios FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los usuarios usan soft delete: actualice activo y eliminado_en';

DROP TRIGGER IF EXISTS trg_pacientes_bd_soft_delete;
CREATE TRIGGER trg_pacientes_bd_soft_delete
BEFORE DELETE ON pacientes FOR EACH ROW
SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Los pacientes usan soft delete: actualice activo y eliminado_en';
