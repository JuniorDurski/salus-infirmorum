<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/rotulos_service.php';
require_once __DIR__ . '/codigos_pacientes.php';

requerirAutenticacion($conexion, 'reportes.rotulos.imprimir', true);

$q = $_GET['q'] ?? '';

try {
    if ($q === 'formatos') {
        echo json_encode(catalogoPublicoFormatosRotulos(), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($q === 'escuelas') {
        listarEscuelasRotulos($conexion);
    }
    if ($q === 'buscar') {
        buscarPostulantesRotulos($conexion);
    }
    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No fue posible consultar los postulantes.'], 422);
}

function listarEscuelasRotulos(mysqli $db): void
{
    $rs = $db->query(
        "SELECT id_escuela, nombre,
                CONCAT(
                    JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')),
                    '-',
                    LPAD(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')), 2, '0')
                ) AS periodo
         FROM escuelas
         WHERE activo = 1
         ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')) AS UNSIGNED) DESC,
                  CAST(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')) AS UNSIGNED) DESC,
                  nombre"
    );
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

function buscarPostulantesRotulos(mysqli $db): void
{
    $idEscuela = (int) ($_GET['escuela'] ?? 0);
    $fecha = trim((string) ($_GET['fecha'] ?? ''));
    $texto = trim((string) ($_GET['texto'] ?? ''));

    if ($fecha !== '' && !fechaRotuloValida($fecha)) {
        responderJson(['status' => 'error', 'mensaje' => 'La fecha del filtro no es válida.'], 400);
    }

    $where = ['p.activo = 1'];
    $params = [];
    $types = '';

    if ($idEscuela > 0) {
        $where[] = 'p.id_escuela = ?';
        $params[] = $idEscuela;
        $types .= 'i';
    }
    if ($fecha !== '') {
        $where[] = sqlFechaRegistroPaciente('p') . ' = ?';
        $params[] = $fecha;
        $types .= 's';
    }
    if ($texto !== '') {
        $where[] = '(p.nombre LIKE ? OR p.documento LIKE ?)';
        $params[] = "%{$texto}%";
        $params[] = "%{$texto}%";
        $types .= 'ss';
    }

    $sql = 'SELECT p.id_paciente AS id_postulante,
                   p.nombre,
                   p.documento AS cedula,
                   DATE_FORMAT(' . sqlMomentoRegistroPaciente('p') . ', \'%Y-%m-%d\') AS fecha_alta,
                   e.nombre AS escuela
            FROM pacientes p
            LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.id_paciente DESC';
    $stmt = $db->prepare($sql);
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

function fechaRotuloValida(string $fecha): bool
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}
