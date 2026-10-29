-- Lote de postulantes ACADEPOL correspondiente al periodo 2026-10.
SET @pais_paraguay = (
    SELECT p.id_pais
    FROM paises p
    WHERE p.codigo_iso2 = 'PY'
    LIMIT 1
);

-- La escuela puede existir desde V19 con otro periodo; este lote requiere
-- especificamente la designacion ACADEPOL de octubre de 2026.
INSERT INTO escuelas (id_pais, nombre, periodo, activo)
SELECT @pais_paraguay, 'ACADEPOL', JSON_OBJECT('anio', 2026, 'mes', 10), 1
WHERE NOT EXISTS (
    SELECT 1
    FROM escuelas e
    WHERE e.nombre = 'ACADEPOL'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.anio')) AS UNSIGNED) = 2026
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.mes')) AS UNSIGNED) = 10
      AND e.activo = 1
);

SET @escuela_acadepol_2026_10 = (
    SELECT e.id_escuela
    FROM escuelas e
    WHERE e.nombre = 'ACADEPOL'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.anio')) AS UNSIGNED) = 2026
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.mes')) AS UNSIGNED) = 10
      AND e.activo = 1
    ORDER BY e.id_escuela DESC
    LIMIT 1
);

INSERT INTO pacientes
    (id_escuela, id_pais, codigo, nombre, documento, fecha_nacimiento,
     es_femenino, activo)
SELECT
    @escuela_acadepol_2026_10,
    @pais_paraguay,
    NULL,
    lote.nombre,
    lote.documento,
    lote.fecha_nacimiento,
    lote.es_femenino,
    1
FROM (
    SELECT 'BLANCA MARCHEL OVANDO VERON' AS nombre, '759614' AS documento,
           DATE('2006-11-02') AS fecha_nacimiento, 1 AS es_femenino
    UNION ALL
    SELECT 'OSCAR FRANCISCO LEGUA ROJAS', '6611405', DATE('2008-03-27'), 0
    UNION ALL
    SELECT 'GUSTAVO GONZALEZ FRANCO', '5777098', DATE('2005-06-15'), 0
    UNION ALL
    SELECT 'MANUEL ORTIZ RIOS', '5168689', DATE('2008-06-16'), 0
    UNION ALL
    SELECT 'LUIS CHAMORRO GONZALEZ', '6127757', DATE('2008-03-20'), 0
) AS lote
WHERE NOT EXISTS (
    SELECT 1
    FROM pacientes existente
    WHERE existente.id_pais = @pais_paraguay
      AND existente.documento = lote.documento
);

SET @escuela_acadepol_2026_10 = NULL;
SET @pais_paraguay = NULL;
