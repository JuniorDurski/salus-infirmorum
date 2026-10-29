<?php

$configDb = require __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$conexion = new mysqli(
    $configDb['host'],
    $configDb['user'],
    $configDb['password'],
    $configDb['database'],
    $configDb['port']
);

if ($conexion->connect_error) {
    die('Error en la conexión: ' . $conexion->connect_error);
}

$conexion->set_charset($configDb['charset']);
$conexion->query("SET time_zone = '-03:00'");

// Los triggers de auditoría toman el actor desde esta variable de sesión SQL.
$actorAuditoria = isset($_SESSION['usuario']['id_usuario'])
    ? (int) $_SESSION['usuario']['id_usuario']
    : null;
$conexion->query(
    'SET @app_usuario_id = ' . ($actorAuditoria === null ? 'NULL' : $actorAuditoria)
);
