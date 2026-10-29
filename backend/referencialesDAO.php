<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

requerirAutenticacion($conexion, null, true);

$q = $_GET['q'] ?? '';

if ($q === 'paises') {
    $rs = $conexion->query(
        'SELECT id_pais, codigo_iso2, nombre, prefijo_telefonico
         FROM paises WHERE activo = 1 ORDER BY nombre'
    );
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($q === 'titulos') {
    $rs = $conexion->query(
        'SELECT id_titulo, titulo, abreviatura, es_femenino
         FROM titulos WHERE activo = 1 ORDER BY titulo'
    );
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($q === 'universidades') {
    $rs = $conexion->query(
        'SELECT u.id_universidad, u.nombre, u.abreviatura, p.nombre AS pais
         FROM universidades u
         INNER JOIN paises p ON p.id_pais = u.id_pais
         WHERE u.activo = 1 ORDER BY u.nombre'
    );
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'mensaje' => 'Referencial no válida']);
