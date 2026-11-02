-- El área POSTULANTES conservaba el código heredado "informes". Se renombran
-- los nodos de menú y sus permisos de navegación a "postulantes". Se actualiza
-- el código en el mismo registro: id_permiso no cambia, así que las
-- asignaciones de rol_permisos existentes se conservan intactas.
UPDATE permisos
SET codigo = CONCAT('menu.postulantes', SUBSTRING(codigo, CHAR_LENGTH('menu.informes') + 1))
WHERE codigo = 'menu.informes' OR codigo LIKE 'menu.informes.%';

UPDATE menus
SET codigo = CONCAT('postulantes', SUBSTRING(codigo, CHAR_LENGTH('informes') + 1))
WHERE codigo = 'informes' OR codigo LIKE 'informes.%';

-- Iconos minimalistas: el menú guarda una clave que la aplicación dibuja como
-- SVG monocromo (backend/iconos.php). Un texto que no sea una clave conocida se
-- sigue mostrando tal cual, por compatibilidad con emojis cargados a mano.
UPDATE menus
SET icono = CASE codigo
    WHEN 'inicio' THEN 'inicio'
    WHEN 'pacientes' THEN 'paciente'
    WHEN 'atenciones' THEN 'estetoscopio'
    WHEN 'postulantes' THEN 'postulantes'
    WHEN 'postulantes.referenciales' THEN 'carpeta'
    WHEN 'postulantes.referenciales.escuelas' THEN 'escuela'
    WHEN 'postulantes.referenciales.estudios' THEN 'documento'
    WHEN 'postulantes.arreadores' THEN 'convenio'
    WHEN 'postulantes.postulantes' THEN 'lista'
    WHEN 'postulantes.laboratorio' THEN 'laboratorio'
    WHEN 'postulantes.rx' THEN 'rayos'
    WHEN 'postulantes.eco' THEN 'onda'
    WHEN 'postulantes.audiometria' THEN 'audio'
    WHEN 'postulantes.rotulos' THEN 'etiqueta'
    WHEN 'postulantes.gestion' THEN 'portapapeles'
    WHEN 'referenciales' THEN 'libro'
    WHEN 'referenciales.universidades' THEN 'graduacion'
    WHEN 'referenciales.titulos' THEN 'titulo'
    WHEN 'referenciales.estudios' THEN 'documento'
    WHEN 'seguridad' THEN 'escudo'
    WHEN 'seguridad.usuarios' THEN 'usuarios'
    WHEN 'seguridad.roles' THEN 'credencial'
    WHEN 'seguridad.permisos' THEN 'llave'
    WHEN 'seguridad.menus' THEN 'menu'
    WHEN 'perfil' THEN 'usuario'
    ELSE icono
END;
