<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'listar':
            $sql = "SELECT
                        id_escuela,
                        id_pais,
                        id_escuela AS id_designacion,
                        nombre,
                        nombre AS designacion,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')) AS UNSIGNED) AS periodo_anio,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')) AS UNSIGNED) AS periodo_mes,
                        CONCAT(
                            JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')),
                            '-',
                            LPAD(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')), 2, '0')
                        ) AS periodo
                    FROM escuelas
                    WHERE activo = 1
                    ORDER BY periodo_anio DESC, periodo_mes DESC, nombre";
            $rs = $conexion->query($sql);
            echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
            break;

        case 'guardar':
            $id = (int) ($_POST['id_escuela'] ?? $_POST['id_designacion'] ?? 0);
            $nombre = mb_strtoupper(trim($_POST['nombre'] ?? $_POST['designacion'] ?? ''), 'UTF-8');
            $anio = (int) ($_POST['periodo_anio'] ?? 0);
            $mes = (int) ($_POST['periodo_mes'] ?? 0);
            $idPais = ($_POST['id_pais'] ?? '') !== '' ? (int) $_POST['id_pais'] : null;

            // Compatibilidad con clientes que todavía envían periodo=AAAA-MM.
            if (($anio === 0 || $mes === 0) && preg_match('/^(\d{4})-(\d{1,2})$/', $_POST['periodo'] ?? '', $partes)) {
                $anio = (int) $partes[1];
                $mes = (int) $partes[2];
            }

            if ($nombre === '' || $anio < 1900 || $anio > 2200 || $mes < 1 || $mes > 12) {
                responderError('Nombre, año o mes no válidos.');
            }

            $periodo = json_encode(['anio' => $anio, 'mes' => $mes], JSON_UNESCAPED_UNICODE);

            if ($id > 0) {
                $stmt = $conexion->prepare(
                    'UPDATE escuelas SET nombre = ?, periodo = ?, id_pais = ? WHERE id_escuela = ?'
                );
                $stmt->bind_param('ssii', $nombre, $periodo, $idPais, $id);
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO escuelas (nombre, periodo, id_pais) VALUES (?, ?, ?)'
                );
                $stmt->bind_param('ssi', $nombre, $periodo, $idPais);
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
