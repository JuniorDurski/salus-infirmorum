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

## Acceso inicial

La migración `V6` crea el usuario `admin` con una contraseña temporal.
`clave_predeterminada = 1` impide acceder al sistema hasta que el administrador
la reemplace desde `cambiar_clave.php`.

Las firmas, sellos y fotos no se reciben como URLs escritas manualmente. El
endpoint de carga valida JPG, PNG o WEBP (máximo 5 MB), guarda el archivo bajo
`uploads/` con un nombre aleatorio y persiste su ruta relativa en la base.

## Menús y permisos

La migración `V7` separa los permisos funcionales de los permisos de
navegación. Cada nodo de `menus` tiene un permiso generado con el formato
`menu.<ruta>`, por ejemplo `menu.seguridad.usuarios`. Un nodo se muestra cuando
el menú y su permiso están activos y el usuario posee ese permiso mediante uno
de sus roles. `visible_todos = 1` omite la asignación por rol, pero sigue
respetando ambos estados activos.

El módulo `menus.php` permite crear cualquier cantidad de niveles, mover nodos,
configurar una URL interna tanto en padres como en hijos y asignar los roles que
pueden verlos. Al mover o renombrar un padre, se actualizan de forma atómica las
rutas y permisos de todos sus descendientes.
