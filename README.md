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
