-- El llamado deja de ser un estado operativo. Se conservan sus columnas para
-- no perder datos históricos, pero el permiso queda desactivado globalmente.
UPDATE permisos
SET activo = 0,
    descripcion = 'Permiso histórico: el flujo actual registra directamente la atención'
WHERE codigo = 'postulantes_atencion.llamar';

-- La exportación está disponible para todo usuario que tenga acceso a la
-- jornada; este permiso se conserva únicamente por compatibilidad con roles.
UPDATE permisos
SET nombre = 'Exportar listas de atención (compatibilidad)',
    descripcion = 'La exportación está habilitada para todos los usuarios con acceso a la jornada'
WHERE codigo = 'postulantes_atencion.exportar';
