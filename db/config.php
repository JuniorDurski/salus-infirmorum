<?php

/**
 * Configuración compartida de la base de datos.
 *
 * En producción se recomienda definir DB_HOST, DB_PORT, DB_NAME, DB_USER y
 * DB_PASSWORD en el entorno. Los valores por defecto conservan XAMPP local.
 */
$dbEnv = static function (string $nombre, string $predeterminado): string {
    $valor = getenv($nombre);

    return $valor === false ? $predeterminado : $valor;
};

return [
    'host' => $dbEnv('DB_HOST', 'localhost'),
    'port' => (int) $dbEnv('DB_PORT', '3306'),
    'database' => $dbEnv('DB_NAME', 'salus_infirmorum_db_01'),
    'user' => $dbEnv('DB_USER', 'root'),
    'password' => $dbEnv('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    // Padrón de cédulas (solo lectura, para autocompletar pacientes).
    // Usa el mismo servidor y usuario que la base principal.
    'cedulas_database' => $dbEnv('DB_CEDULAS_NAME', 'cedulas_db'),
];
