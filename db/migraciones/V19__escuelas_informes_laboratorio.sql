-- Las designaciones deben coincidir con el mapa de reportes/index.php.
-- Las nuevas escuelas heredan el periodo y pais de la ISEPOL activa.
SET @periodo_escuelas_lab = COALESCE(
    (
        SELECT e.periodo
        FROM escuelas e
        WHERE e.nombre = 'ISEPOL' AND e.activo = 1
        ORDER BY e.id_escuela DESC
        LIMIT 1
    ),
    JSON_OBJECT('anio', YEAR(CURRENT_DATE), 'mes', MONTH(CURRENT_DATE))
);

SET @pais_escuelas_lab = COALESCE(
    (
        SELECT e.id_pais
        FROM escuelas e
        WHERE e.nombre = 'ISEPOL' AND e.activo = 1
        ORDER BY e.id_escuela DESC
        LIMIT 1
    ),
    (SELECT p.id_pais FROM paises p WHERE p.codigo_iso2 = 'PY' LIMIT 1)
);

INSERT INTO escuelas (nombre, periodo, id_pais, activo)
SELECT catalogo.nombre, @periodo_escuelas_lab, @pais_escuelas_lab, 1
FROM (
    SELECT 'ACADEMIL' AS nombre
    UNION ALL SELECT 'ISEPOL'
    UNION ALL SELECT 'ACADEPOL'
    UNION ALL SELECT 'COLEPOL'
    UNION ALL SELECT 'DISERGEMIL'
    UNION ALL SELECT 'DISERSANFA'
    UNION ALL SELECT 'LICEMIL'
    UNION ALL SELECT 'LICEMIL | CARDENAS'
    UNION ALL SELECT 'LICEMIL | SANTOS'
    UNION ALL SELECT 'COMISOE'
    UNION ALL SELECT 'DIGETREN'
    UNION ALL SELECT 'DROGAS'
) AS catalogo
WHERE NOT EXISTS (
    SELECT 1
    FROM escuelas existente
    WHERE existente.nombre = catalogo.nombre
      AND existente.activo = 1
);

SET @periodo_escuelas_lab = NULL;
SET @pais_escuelas_lab = NULL;
