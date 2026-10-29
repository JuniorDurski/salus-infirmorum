<?php

/**
 * Conexión de solo lectura al padrón de cédulas (cedulas_db.cedulas).
 *
 * Es una base separada de la principal. Se abre recién cuando se necesita
 * y devuelve null si no está disponible (por ejemplo, en un servidor donde
 * no se importó el padrón), para que el resto del sistema siga funcionando.
 */
function conexionCedulas(): ?mysqli
{
    static $conexionCedulas = null;
    static $intentado = false;

    if ($intentado) {
        return $conexionCedulas;
    }
    $intentado = true;

    $configDb = require __DIR__ . '/config.php';

    try {
        $db = new mysqli(
            $configDb['host'],
            $configDb['user'],
            $configDb['password'],
            $configDb['cedulas_database'],
            $configDb['port']
        );
    } catch (mysqli_sql_exception $e) {
        return null;
    }

    if ($db->connect_error) {
        return null;
    }

    $db->set_charset($configDb['charset']);
    $conexionCedulas = $db;

    return $conexionCedulas;
}
