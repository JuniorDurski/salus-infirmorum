<?php

declare(strict_types=1);

/**
 * Genera una migración que deja los catálogos referenciales (escuelas, menús,
 * permisos, roles, convenios, etc.) exactamente como están en la base actual.
 *
 * Uso, desde la raíz del proyecto:
 *   C:\xampp\php\php.exe db\generar_migracion_referenciales.php [descripcion]
 *
 * Crea db/migraciones/V<siguiente>__<descripcion>.sql. La migración:
 *   - conserva los mismos ids que la base actual;
 *   - en la base actual no cambia nada (solo toca filas distintas o faltantes);
 *   - en una instalación nueva corrige/agrega/quita filas hasta igualar la actual.
 * No incluye datos operativos (pacientes, postulantes, pagos, informes, atenciones,
 * auditorías) ni usuarios: cada instalación crea los suyos.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este comando solo puede ejecutarse desde la consola.\n");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Tablas referenciales en orden de dependencia. 'omitir' son columnas que no se
 * copian ni se comparan (fechas de alta y referencias a usuarios de esta instalación).
 */
const TABLAS_REFERENCIALES = [
    'paises' => ['omitir' => []],
    'tipos_estudio' => ['omitir' => []],
    'titulos' => ['omitir' => ['creado_en', 'actualizado_en']],
    'universidades' => ['omitir' => ['creado_en', 'actualizado_en']],
    'escuelas' => ['omitir' => ['creado_en', 'actualizado_en']],
    'metodos_pago' => ['omitir' => ['creado_en', 'actualizado_en']],
    'plantillas_estudio' => ['omitir' => ['creado_en']],
    'postulantes_estudios_referenciales' => ['omitir' => ['creado_en', 'actualizado_en']],
    'postulantes_arreadores' => ['omitir' => ['creado_en', 'actualizado_en']],
    'postulantes_arreador_escuela' => ['omitir' => ['creado_en', 'actualizado_en']],
    'permisos' => ['omitir' => []],
    'roles' => ['omitir' => []],
    'rol_permisos' => ['omitir' => ['asignado_por', 'asignado_en']],
    'menus' => ['omitir' => []],
];

$config = require __DIR__ . '/config.php';
$descripcion = preg_replace('/[^a-z0-9_]+/', '_', strtolower($argv[1] ?? 'sincronizar_referenciales'));
$directorio = __DIR__ . DIRECTORY_SEPARATOR . 'migraciones';

$db = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
$db->set_charset('utf8mb4');

$version = 1;
foreach (scandir($directorio) ?: [] as $archivo) {
    if (preg_match('/^V([0-9]+)__/', $archivo, $m)) {
        $version = max($version, (int) $m[1] + 1);
    }
}

$sql = [];
$sql[] = "-- Sincroniza los catálogos referenciales con la base de referencia al "
    . date('Y-m-d H:i') . ".";
$sql[] = '-- Generado con db/generar_migracion_referenciales.php; no editar a mano.';
$sql[] = '-- Solo modifica filas distintas o faltantes y elimina las que ya no existen,';
$sql[] = '-- por lo que en la base de referencia no produce cambios.';
$sql[] = '';
$sql[] = 'SET @fk_checks_previo = @@FOREIGN_KEY_CHECKS;';
$sql[] = 'SET FOREIGN_KEY_CHECKS = 0;';

foreach (TABLAS_REFERENCIALES as $tabla => $opciones) {
    $columnas = [];
    $clave = [];
    $rs = $db->query(
        "SELECT column_name, column_key FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = '" . $db->real_escape_string($tabla) . "'
         ORDER BY ordinal_position"
    );
    while ([$columna, $tipoClave] = $rs->fetch_row()) {
        if (in_array($columna, $opciones['omitir'], true)) continue;
        $columnas[] = $columna;
    }
    $rs = $db->query(
        "SELECT column_name FROM information_schema.key_column_usage
         WHERE table_schema = DATABASE() AND table_name = '" . $db->real_escape_string($tabla) . "'
           AND constraint_name = 'PRIMARY' ORDER BY ordinal_position"
    );
    while ([$columna] = $rs->fetch_row()) $clave[] = $columna;
    if ($columnas === [] || $clave === []) {
        throw new RuntimeException("No se pudo leer la estructura de {$tabla}.");
    }

    $lista = implode(', ', array_map(static fn($c) => "`{$c}`", $columnas));
    $orden = implode(', ', array_map(static fn($c) => "`{$c}`", $clave));
    $filas = $db->query("SELECT {$lista} FROM `{$tabla}` ORDER BY {$orden}")->fetch_all(MYSQLI_NUM);
    $temporal = "tmp_ref_{$tabla}";
    $union = implode(' AND ', array_map(static fn($c) => "t.`{$c}` = r.`{$c}`", $clave));
    $iguales = implode(' AND ', array_map(static fn($c) => "t.`{$c}` <=> r.`{$c}`", $columnas));
    $primera = "`{$clave[0]}`";

    $sql[] = '';
    $sql[] = "-- {$tabla}: " . count($filas) . ' fila(s)';
    $sql[] = "DROP TEMPORARY TABLE IF EXISTS `{$temporal}`;";
    $sql[] = "CREATE TEMPORARY TABLE `{$temporal}` LIKE `{$tabla}`;";
    foreach (array_chunk($filas, 200) as $tanda) {
        $valores = array_map(static function (array $fila) use ($db): string {
            return '(' . implode(', ', array_map(
                static fn($v) => $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'",
                $fila
            )) . ')';
        }, $tanda);
        $sql[] = "INSERT INTO `{$temporal}` ({$lista}) VALUES\n    " . implode(",\n    ", $valores) . ';';
    }
    // Quita las filas que sobran o que difieren; luego inserta las que faltan.
    $sql[] = "DELETE t FROM `{$tabla}` t LEFT JOIN `{$temporal}` r ON {$union}\n"
        . "WHERE r.{$primera} IS NULL OR NOT ({$iguales});";
    $sql[] = "INSERT INTO `{$tabla}` ({$lista})\n"
        . "SELECT " . implode(', ', array_map(static fn($c) => "r.`{$c}`", $columnas))
        . " FROM `{$temporal}` r LEFT JOIN `{$tabla}` t ON {$union}\n"
        . "WHERE t.{$primera} IS NULL;";
    $sql[] = "DROP TEMPORARY TABLE `{$temporal}`;";

    // Los AUTO_INCREMENT siguen después del mayor id copiado.
    $auto = $db->query(
        "SELECT AUTO_INCREMENT FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = '" . $db->real_escape_string($tabla) . "'"
    )->fetch_row()[0] ?? null;
    if ($auto !== null && count($clave) === 1) {
        $sql[] = "ALTER TABLE `{$tabla}` AUTO_INCREMENT = " . (int) $auto . ';';
    }
}

$sql[] = '';
$sql[] = 'SET FOREIGN_KEY_CHECKS = @fk_checks_previo;';

$ruta = $directorio . DIRECTORY_SEPARATOR . "V{$version}__{$descripcion}.sql";
file_put_contents($ruta, implode("\n", $sql) . "\n");
echo "Migración generada: db/migraciones/" . basename($ruta) . "\n";
