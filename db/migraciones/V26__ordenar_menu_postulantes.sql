-- Estudios pertenece únicamente a POSTULANTES y debe aparecer debajo de ESCUELAS.
UPDATE menus
SET activo = 0
WHERE codigo = 'referenciales.estudios';

UPDATE menus
SET etiqueta = 'ESTUDIOS', orden = 20, activo = 1
WHERE codigo = 'informes.estudios';

-- Orden estable y espaciado para facilitar futuras incorporaciones.
UPDATE menus
SET orden = CASE codigo
    WHEN 'informes.escuelas' THEN 10
    WHEN 'informes.estudios' THEN 20
    WHEN 'informes.postulantes' THEN 30
    WHEN 'informes.laboratorio' THEN 40
    WHEN 'informes.rx' THEN 50
    WHEN 'informes.eco' THEN 60
    WHEN 'informes.audiometria' THEN 70
    WHEN 'informes.rotulos' THEN 80
    WHEN 'informes.gestion' THEN 90
    ELSE orden
END
WHERE codigo IN (
    'informes.escuelas',
    'informes.estudios',
    'informes.postulantes',
    'informes.laboratorio',
    'informes.rx',
    'informes.eco',
    'informes.audiometria',
    'informes.rotulos',
    'informes.gestion'
);
