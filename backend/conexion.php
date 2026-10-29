<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conexion = new mysqli(
        getenv('SALUS_DB_HOST') ?: 'localhost',
        getenv('SALUS_DB_USER') ?: 'root',
        getenv('SALUS_DB_PASSWORD') ?: '',
        getenv('SALUS_DB_NAME') ?: 'salus_infirmorum_db_01'
    );
    $conexion->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('No se pudo conectar a la base de datos.');
}
