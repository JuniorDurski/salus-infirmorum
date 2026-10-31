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
