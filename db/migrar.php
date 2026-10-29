<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Este comando solo puede ejecutarse desde la consola.\n");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$config = require __DIR__ . '/config.php';
$comando = strtolower($argv[1] ?? 'migrate');
$comandosValidos = ['migrate', 'info', 'validate', 'repair'];

if (!in_array($comando, $comandosValidos, true)) {
    fwrite(STDERR, "Uso: php db/migrar.php [migrate|info|validate|repair]\n");
    exit(2);
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['database'])) {
    fwrite(STDERR, "DB_NAME contiene caracteres no permitidos.\n");
    exit(2);
}

try {
    $db = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        '',
        $config['port']
    );
    $db->set_charset($config['charset']);
    $db->query(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $config['database']
        )
    );
    $db->select_db($config['database']);
    $db->query("SET time_zone = '-03:00'");

    crearTablaHistorial($db);
    $migraciones = descubrirMigraciones(__DIR__ . '/migraciones');

    if ($comando === 'info') {
        mostrarInfo($db, $migraciones);
        exit(0);
    }

    if ($comando === 'validate') {
        validarMigraciones($db, $migraciones);
        echo "Validación correcta: historial y archivos coinciden.\n";
        exit(0);
    }

    if ($comando === 'repair') {
        $eliminadas = repararHistorial($db);
        echo "Repair terminado: {$eliminadas} registro(s) fallido(s) eliminado(s).\n";
        exit(0);
    }

    validarMigraciones($db, $migraciones);
    ejecutarPendientes($db, $migraciones, $config['user']);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error de migración: ' . $e->getMessage() . "\n");
    exit(1);
}

function crearTablaHistorial(mysqli $db): void
{
    $db->query(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            installed_rank INT UNSIGNED NOT NULL AUTO_INCREMENT,
            version VARCHAR(50) NOT NULL,
            description VARCHAR(200) NOT NULL,
            script VARCHAR(255) NOT NULL,
            checksum CHAR(64) NOT NULL,
            installed_by VARCHAR(100) NOT NULL,
            installed_on DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            execution_time_ms INT UNSIGNED NOT NULL,
            success TINYINT(1) NOT NULL,
            PRIMARY KEY (installed_rank),
            UNIQUE KEY uk_schema_migrations_version (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/** @return array<string, array<string, string>> */
function descubrirMigraciones(string $directorio): array
{
    if (!is_dir($directorio)) {
        throw new RuntimeException("No existe el directorio de migraciones: {$directorio}");
    }

    $resultado = [];
    $archivos = scandir($directorio);

    if ($archivos === false) {
        throw new RuntimeException('No se pudo leer el directorio de migraciones.');
    }

    foreach ($archivos as $archivo) {
        if (!preg_match('/^V([0-9]+(?:\.[0-9]+)*)__([a-zA-Z0-9_]+)\.sql$/', $archivo, $partes)) {
            continue;
        }

        $version = $partes[1];
        if (isset($resultado[$version])) {
            throw new RuntimeException("La versión {$version} está repetida.");
        }

        $ruta = $directorio . DIRECTORY_SEPARATOR . $archivo;
        $resultado[$version] = [
            'version' => $version,
            'description' => str_replace('_', ' ', $partes[2]),
            'script' => $archivo,
            'path' => $ruta,
            'checksum' => hash_file('sha256', $ruta),
        ];
    }

    uksort($resultado, 'version_compare');

    return $resultado;
}

/** @return array<string, array<string, mixed>> */
function leerHistorial(mysqli $db): array
{
    $historial = [];
    $rs = $db->query(
        'SELECT version, description, script, checksum, installed_on, execution_time_ms, success
         FROM schema_migrations ORDER BY installed_rank'
    );

    while ($fila = $rs->fetch_assoc()) {
        $historial[$fila['version']] = $fila;
    }

    return $historial;
}

/** @param array<string, array<string, string>> $migraciones */
function validarMigraciones(mysqli $db, array $migraciones): void
{
    foreach (leerHistorial($db) as $version => $aplicada) {
        if ((int) $aplicada['success'] !== 1) {
            throw new RuntimeException(
                "La migración V{$version} falló anteriormente. Corrija la causa y ejecute repair."
            );
        }

        if (!isset($migraciones[$version])) {
            throw new RuntimeException("Falta el archivo ya aplicado para V{$version}.");
        }

        if (!hash_equals($aplicada['checksum'], $migraciones[$version]['checksum'])) {
            throw new RuntimeException(
                "El checksum de V{$version} cambió. No modifique migraciones ya aplicadas; cree una nueva."
            );
        }
    }
}

/** @param array<string, array<string, string>> $migraciones */
function ejecutarPendientes(mysqli $db, array $migraciones, string $usuario): void
{
    $historial = leerHistorial($db);
    $pendientes = array_diff_key($migraciones, $historial);

    if ($pendientes === []) {
        echo "La base de datos ya está actualizada.\n";
        return;
    }

    foreach ($pendientes as $migracion) {
        echo "Migrando V{$migracion['version']} - {$migracion['description']}... ";
        $inicio = microtime(true);
        $exito = false;

        try {
            $sql = file_get_contents($migracion['path']);
            if ($sql === false) {
                throw new RuntimeException("No se pudo leer {$migracion['script']}.");
            }

            ejecutarSql($db, $sql);
            $exito = true;
        } finally {
            $milisegundos = max(0, (int) round((microtime(true) - $inicio) * 1000));
            registrarEjecucion($db, $migracion, $usuario, $milisegundos, $exito);
        }

        echo "OK ({$milisegundos} ms)\n";
    }

    echo 'Migraciones aplicadas: ' . count($pendientes) . ".\n";
}

function ejecutarSql(mysqli $db, string $sql): void
{
    if (!$db->multi_query($sql)) {
        throw new RuntimeException($db->error);
    }

    do {
        $resultado = $db->store_result();
        if ($resultado instanceof mysqli_result) {
            $resultado->free();
        }

        if (!$db->more_results()) {
            break;
        }

        if (!$db->next_result()) {
            throw new RuntimeException($db->error);
        }
    } while (true);
}

/** @param array<string, string> $migracion */
function registrarEjecucion(
    mysqli $db,
    array $migracion,
    string $usuario,
    int $milisegundos,
    bool $exito
): void {
    $stmt = $db->prepare(
        'INSERT INTO schema_migrations
            (version, description, script, checksum, installed_by, execution_time_ms, success)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $estado = $exito ? 1 : 0;
    $stmt->bind_param(
        'sssssii',
        $migracion['version'],
        $migracion['description'],
        $migracion['script'],
        $migracion['checksum'],
        $usuario,
        $milisegundos,
        $estado
    );
    $stmt->execute();
}

/** @param array<string, array<string, string>> $migraciones */
function mostrarInfo(mysqli $db, array $migraciones): void
{
    $historial = leerHistorial($db);
    echo str_pad('Versión', 12) . str_pad('Estado', 12) . "Descripción\n";
    echo str_repeat('-', 64) . "\n";

    foreach ($migraciones as $version => $migracion) {
        if (!isset($historial[$version])) {
            $estado = 'Pendiente';
        } elseif ((int) $historial[$version]['success'] === 1) {
            $estado = hash_equals($historial[$version]['checksum'], $migracion['checksum'])
                ? 'Aplicada'
                : 'Modificada';
        } else {
            $estado = 'Fallida';
        }

        echo str_pad('V' . $version, 12) . str_pad($estado, 12) . $migracion['description'] . "\n";
    }
}

function repararHistorial(mysqli $db): int
{
    $db->query('DELETE FROM schema_migrations WHERE success = 0');

    return $db->affected_rows;
}
