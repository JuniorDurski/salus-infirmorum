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
Cada rótulo se imprime como un rectángulo horizontal de borde grueso, con el
nombre y la cédula centrados en Arial 28 y la cédula separada por puntos de miles.
