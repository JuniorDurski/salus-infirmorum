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
