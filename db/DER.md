# DER — Salus Infirmorum

![Diagrama entidad-relación de la base de datos](DER.svg)

El diagrama representa el esquema resultante de las migraciones `V1` a `V8`,
más la tabla técnica `schema_migrations` creada por el ejecutor.

- `PK`: clave primaria.
- `FK`: clave foránea.
- `UK`: columna que forma parte de una restricción única.
- `auditorias` no tiene una FK directa porque actor, entidad y registro se
  almacenan dentro de `datos` en formato JSON.

La fuente regenerable del gráfico está en [`DER.mmd`](DER.mmd).
