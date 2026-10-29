# DER — Salus Infirmorum

![Diagrama entidad-relación de la base de datos](DER.svg)

El diagrama representa el esquema resultante de las migraciones `V1` a `V13`,
más la tabla técnica `schema_migrations` creada por el ejecutor.

- `PK`: clave primaria.
- `FK`: clave foránea.
- `UK`: columna que forma parte de una restricción única.
- `auditorias` no tiene una FK directa porque actor, entidad y registro se
  almacenan dentro de `datos` en formato JSON.
- `postulantes_atencion.observacion` conserva las claves JSON históricas
  `clinica`, `general` y `doctor`, pero la aplicación las muestra como un único
  historial de observaciones por usuario y fecha.
- Las tablas puente de asignación permiten varios profesionales, escuelas y
  estudios por cabecera sin duplicar la jornada.

La fuente regenerable del gráfico está en [`DER.mmd`](DER.mmd).
