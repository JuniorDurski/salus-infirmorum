<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? '';

if ($q === 'listar') {
    requerirAutenticacion($conexion, 'escuelas.ver', true);
} else {
    requerirAutenticacion($conexion, 'escuelas.editar', true);
    validarCsrf();
}

try {
    switch ($q) {
        case 'listar':
            $sql = "SELECT
                        id_escuela,
                        id_pais,
                        id_escuela AS id_designacion,
                        nombre,
                        nombre AS designacion
                    FROM escuelas
                    WHERE activo = 1
                    ORDER BY nombre";
            $rs = $conexion->query($sql);
            echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
            break;

        case 'guardar':
            $id = (int) ($_POST['id_escuela'] ?? $_POST['id_designacion'] ?? 0);
            $nombre = mb_strtoupper(trim($_POST['nombre'] ?? $_POST['designacion'] ?? ''), 'UTF-8');
            $idPais = ($_POST['id_pais'] ?? '') !== '' ? (int) $_POST['id_pais'] : null;

            if ($nombre === '') {
                responderError('Ingrese el nombre de la escuela.');
            }

            if ($id > 0) {
                $stmt = $conexion->prepare(
                    'UPDATE escuelas SET nombre = ?, id_pais = ? WHERE id_escuela = ?'
                );
                $stmt->bind_param('sii', $nombre, $idPais, $id);
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO escuelas (nombre, id_pais) VALUES (?, ?)'
                );
                $stmt->bind_param('si', $nombre, $idPais);
            }

            $stmt->execute();
            echo json_encode(['status' => 'ok']);
            break;

        case 'eliminar':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                responderError('ID no recibido.');
            }

            $stmt = $conexion->prepare('UPDATE escuelas SET activo = 0 WHERE id_escuela = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo json_encode(['status' => 'ok']);
            break;

        default:
            responderError('Operación no válida.');
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function responderError(string $mensaje): void
{
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
