# Salus Infirmorum

Sistema clínico en PHP, MariaDB y JavaScript sin dependencias de frontend. Los informes PDF usan mPDF y conservan las fórmulas y plantillas originales.

## Instalación

1. Crear la base de datos `salus_infirmorum_db_01` con `utf8mb4`.
2. Si la base está vacía, ejecutar [`db/db.sql`](db/db.sql).
3. Ejecutar [`db/seed.sql`](db/seed.sql) para los roles, permisos, menús, escuelas y usuario inicial. No volver a ejecutar `db/db.sql` sobre una base con datos.
4. Abrir `http://localhost/salus-infirmorum/login.php` con Apache y MariaDB de XAMPP.

Usuario inicial: `admin`. Contraseña: `Salus2026!Inicio`. Cambiarla desde **Mi perfil** luego del primer ingreso.

La conexión usa por defecto `localhost`, usuario `root`, contraseña vacía y base `salus_infirmorum_db_01`. Se pueden configurar `SALUS_DB_HOST`, `SALUS_DB_USER`, `SALUS_DB_PASSWORD` y `SALUS_DB_NAME` en el entorno del servidor.

## Uso

- **Escuelas:** el código identifica la plantilla PDF (`LICEMIL`, `ACADEMIL`, etc.).
- **Pacientes:** registrar primero la ficha personal y luego crear una evaluación asociada a escuela y período.
- **Derivaciones:** registrar derivador y después vincularlo al paciente. La fecha y hora se asignan automáticamente.
- **Informes:** filtrar por escuela, año, mes, fecha y nombre/documento. Se puede imprimir uno, varios seleccionados o todos los resultados filtrados, además de la tarjeta Rh.
- **Resultados mock:** la generación usa los cálculos existentes en `backend/valores_*.php` y `reportes/asignacion_valor.php`. La primera impresión guarda los valores en `resultados_mock.resultados` como JSON; las siguientes recuperan ese mismo documento.
- **Seguridad:** los menús visibles dependen de `menu_permiso`, los permisos de `rol_permiso` y los roles de `usuario_rol`. Los endpoints comprueban permisos independientemente del menú. La actividad del módulo se registra en `auditoria_seguridad`.

Las bajas usan `eliminado_en` y `eliminado_por`; las consultas operativas excluyen filas eliminadas. Las tablas puente se pueden reactivar sin crear duplicados.

El código anterior de `backend/*DAO.php` quedó deshabilitado y la interfaz utiliza `backend/api.php`. Las plantillas `reportes/informe_*.php` y las funciones de cálculo no se modificaron.
