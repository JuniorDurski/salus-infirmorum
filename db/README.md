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

El rol es el control habitual de acceso. El estado de un permiso se administra
desde `permisos.php` y funciona como un bloqueo global: si un permiso `menu.*`
está inactivo, el nodo se oculta para todos los roles. La migración `V8` deja
como universales solamente Inicio y Mi perfil; el resto exige asignación por rol.

## Catálogos geográficos y académicos

La migración `V9` carga los 249 códigos ISO 3166-1 asignados, sus nombres en
español y prefijos telefónicos internacionales. También incorpora la nómina de
57 universidades paraguayas publicada por ANEAES y crea los módulos protegidos
`universidades.php` y `titulos.php` bajo el menú Referenciales.

## Asignaciones de atención

La migración `V10` implementa el DER de atención de postulantes. Una cabecera
puede asociar varios profesionales, escuelas y estudios referenciales. Al
guardarla o sincronizarla se incorporan únicamente los pacientes activos cuya
escuela coincide y cuya fecha de creación es la fecha de la jornada.

Los permisos funcionales `postulantes_atencion.*` separan creación, inicio,
sincronización, atención, llamado, comentarios, pagos y exportación. Solo
`ADMIN` los recibe inicialmente; los demás roles se configuran desde el módulo
de roles. Cuando `pago_realizado = 1`, la atención queda en modo de solo lectura
para el profesional.

La migración `V11` simplifica el trabajo del profesional: desactiva el permiso
de llamado y convierte “Guardar atención” en la única acción operativa. La
exportación queda disponible para cualquier usuario que pueda acceder a la
jornada, sin exigir un permiso adicional. Las columnas históricas de llamado se
conservan para no destruir información existente.

`postulantes_atencion.observacion` conserva en JSON las claves históricas
`clinica`, `general` y `doctor` por compatibilidad. La interfaz las presenta
como un único historial de observaciones identificado solamente por usuario y
fecha. El estado de cada estudio se conserva normalizado en
`postulantes_atencion_estudios`, lo que permite la consulta pública de solo
lectura mediante documento y escuela.

## Nombres normalizados

La migración `V12` convierte a mayúsculas los nombres de usuarios, pacientes,
escuelas, universidades y derivadores, además del nombre de institución del
derivador. Disparadores de base de datos mantienen la regla para inserciones y
actualizaciones realizadas fuera de la aplicación. Los formularios aplican la
misma normalización mientras el usuario escribe.

## Estado de atención

Desde `V13`, `postulantes_atencion.atendido` es un estado derivado: vale `1`
solamente cuando el postulante tiene al menos un estudio y todos sus checks
están realizados. Al desmarcar cualquier estudio vuelve a `0`. La migración
también corrige los registros históricos para aplicar la misma regla.
