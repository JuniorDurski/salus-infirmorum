# Base de datos

El esquema se administra mediante migraciones inmutables, inspiradas en Flyway.
La fuente de verdad está en `db/migraciones`; una migración aplicada no se
edita: cualquier cambio posterior se agrega en una nueva versión.

## Configuración

La conexión usa estas variables opcionales: `DB_HOST`, `DB_PORT`, `DB_NAME`,
`DB_USER` y `DB_PASSWORD`. Sin variables usa XAMPP local y la base
`salus_infirmorum_db_01`.

## Comandos

Desde la raíz del proyecto, en PowerShell:

```powershell
C:\xampp\php\php.exe db\migrar.php info
C:\xampp\php\php.exe db\migrar.php migrate
C:\xampp\php\php.exe db\migrar.php validate
```

`repair` elimina exclusivamente entradas fallidas del historial. Antes de
usarlo hay que corregir la causa y verificar los objetos que una migración DDL
pudo haber alcanzado a crear.

## Convención

Los archivos siguen `V<versión>__<descripcion>.sql`. El ejecutor registra fecha,
duración y SHA-256 en `schema_migrations`; si cambia el contenido de una versión
aplicada, la validación falla.

La aplicación asigna `@app_usuario_id` en cada conexión. Los triggers lo usan
para registrar al actor en `auditorias`; los procesos externos deben asignar la
misma variable antes de modificar seguridad o activar/desactivar usuarios y
pacientes.

## Plantillas y cálculos

`plantillas_estudio.calculador` guarda una clave, no código ejecutable. Esa clave
se resuelve contra la lista permitida de `backend/calculos/RegistroCalculos.php`.
Para agregar un cálculo se crea el callable en ese registro y una migración que
asigne su clave a la plantilla. Los resultados momentáneos quedan agrupados en
el JSON de `resultados_estudio` y se reutilizan en impresiones posteriores.
