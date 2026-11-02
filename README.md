# salus-infirmorum
sistema de administracion clinica

## Base de datos

El esquema se gestiona con migraciones versionadas. En XAMPP para Windows:

```powershell
C:\xampp\php\php.exe db\migrar.php info
C:\xampp\php\php.exe db\migrar.php migrate
```

La configuración y la convención de versiones están documentadas en
[`db/README.md`](db/README.md).

Después de aplicar las migraciones, el acceso inicial es con el usuario
`admin`. La contraseña inicial es temporal y el sistema obliga a cambiarla antes
de habilitar el resto de los módulos.

La navegación se administra desde **Seguridad > Menús**. Cada padre o hijo crea
su permiso `menu.<ruta>` y puede limitarse por rol, desactivarse o declararse
visible para todos los usuarios autenticados.

Los catálogos de universidades y títulos profesionales se administran desde
**Referenciales**. La migración V9 incorpora países, prefijos telefónicos y la
nómina de universidades paraguayas.

## Rótulos de postulantes

La migración `V24` incorpora **POSTULANTES > IMPRIMIR RÓTULO**. La pantalla
permite filtrar por escuela, fecha de alta, nombre o cédula y generar un PDF
con un postulante, una selección de cualquier cantidad o todos los resultados.
Cada rótulo se imprime como un rectángulo horizontal de borde grueso, con texto
Arial centrado y la cédula separada por puntos de miles. Los formatos se declaran
en `backend/rotulos_service.php`: ACADEPOL imprime nombre y cédula; LICEMIL agrega
el código del postulante en la línea superior, mostrando solamente la parte
anterior al primer guion. La pantalla crea automáticamente
un botón identificado por cada formato disponible. Al pulsarlo imprime las filas
marcadas y, cuando no hay ninguna selección, imprime toda la tabla filtrada.

## Pacientes y postulantes

La migración `V25` reactiva **PACIENTES** como módulo permanente en
`pacientes.php` y asigna al módulo estacional **POSTULANTES** su propia ruta
`postulantes.php`. Ambas pantallas comparten los registros y el backend clínico,
pero son archivos completos e independientes para permitir que sus acciones
evolucionen por separado. También normaliza `informe_lab.php` y
`estudios_postulantes.php`.

La migración `V26` deja **ESTUDIOS** exclusivamente dentro de POSTULANTES,
inmediatamente debajo de ESCUELAS, y ordena todos los submódulos del área.

## Arreadores y pagos

La migración `V27` estandariza los arreadores dentro de POSTULANTES y agrega
convenios anuales por escuela, precios, comisiones, métodos de pago, abonos
parciales y saldos. La migración `V28` separa la configuración en
`arreadores.php`, permite tarifas diferentes para hombres y mujeres y convierte
el importe de la clínica en un dato manual. `postulantes.php` conserva la lista,
las asignaciones y los pagos; `pacientes.php` no se modifica.

La escuela y el convenio de cobro se conservan en el navegador hasta borrar la
selección. Después de guardar o localizar un postulante se pueden preparar uno
o varios medios de pago; la asignación y todos los pagos se registran juntos en
una única transacción. Los listados de postulantes se exportan a Excel o PDF en
versión básica, con comisión de gestión o con ganancia de la clínica, incluyendo
el logo institucional.

## Permisos de POSTULANTES e iconos

La migración `V34` renombra el área heredada `informes` a `postulantes`: los
permisos `menu.informes.*` pasan a `menu.postulantes.*` conservando su
`id_permiso`, por lo que los roles mantienen sus accesos. También reemplaza los
emojis del menú por claves de iconos minimalistas (SVG monocromo) definidos en
`backend/iconos.php`; un texto que no sea una clave conocida se muestra tal cual.

## Código del postulante

La migración `V37` mueve `pacientes.codigo` a `postulantes_arreador_postulante.codigo`,
al lado de `orden`: el código pertenece al registro del postulante en un convenio.
Si el postulante todavía no está registrado, el código del formulario se guarda
junto con el cobro que crea el registro. En los informes se imprime solo el orden
(`036`) o `orden-código` (`036-A12`) cuando hay código cargado.
