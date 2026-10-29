<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'listar':
            listarPacientes($conexion);
            break;

        case 'guardar':
            guardarPaciente($conexion);
            break;

        case 'eliminar':
            cambiarEstadoPaciente($conexion, false);
            break;

        case 'activar':
            cambiarEstadoPaciente($conexion, true);
            break;

        case 'buscar':
            buscarPacientes($conexion);
            break;

        default:
            responderPacienteError('Operación no válida.');
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function camposPacienteSql(): string
{
    return "p.id_paciente,
            p.id_paciente AS id_postulante,
            p.id_escuela,
            p.id_escuela AS id_designacion,
            p.id_pais,
            p.codigo,
            p.nombre,
            p.documento,
            p.documento AS ci,
            p.fecha_nacimiento,
            TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
            p.es_femenino,
            IF(p.es_femenino = 1, 'FEMENINO', 'MASCULINO') AS sexo,
            p.grupo_sanguineo,
            p.telefono,
            p.url_foto,
            DATE_FORMAT(p.creado_en, 'Dia:%d-%m-%Y Hora:%H:%i') AS fecha,
            CONCAT(
                e.nombre,
                ' <br> ',
                JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.anio')),
                '-',
                LPAD(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.mes')), 2, '0')
            ) AS designacion,
            e.nombre AS escuela";
}

function listarPacientes(mysqli $conexion): void
{
    $sql = 'SELECT ' . camposPacienteSql() . '
            FROM pacientes p
            LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
            WHERE p.activo = 1
            ORDER BY p.id_paciente DESC';
    $rs = $conexion->query($sql);
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
}

function guardarPaciente(mysqli $conexion): void
{
    $id = (int) ($_POST['id_paciente'] ?? $_POST['id_postulante'] ?? 0);
    $codigo = valorNullable($_POST['codigo'] ?? null);
    $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $documento = mb_strtoupper(trim($_POST['documento'] ?? $_POST['ci'] ?? ''), 'UTF-8');
    $grupo = valorNullable($_POST['grupo_sanguineo'] ?? null);
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $sexoRecibido = $_POST['es_femenino'] ?? $_POST['sexo'] ?? '';
    $idEscuelaRecibida = $_POST['id_escuela'] ?? $_POST['id_designacion'] ?? '';
    $idEscuela = $idEscuelaRecibida !== '' ? (int) $idEscuelaRecibida : null;
    $idPais = ($_POST['id_pais'] ?? '') !== '' ? (int) $_POST['id_pais'] : null;
    $telefono = valorNullable($_POST['telefono'] ?? null);
    $urlFoto = valorNullable($_POST['url_foto'] ?? null);

    if ($sexoRecibido === 'FEMENINO') {
        $esFemenino = 1;
    } elseif ($sexoRecibido === 'MASCULINO') {
        $esFemenino = 0;
    } elseif ((string) $sexoRecibido === '1' || (string) $sexoRecibido === '0') {
        $esFemenino = (int) $sexoRecibido;
    } else {
        responderPacienteError('Sexo no válido.');
    }

    if ($nombre === '' || $documento === '' || !fechaValida($fechaNacimiento)) {
        responderPacienteError('Nombre, documento y fecha de nacimiento son obligatorios.');
    }
    if ($fechaNacimiento > date('Y-m-d')) {
        responderPacienteError('La fecha de nacimiento no puede estar en el futuro.');
    }
    if ($telefono !== null && !preg_match('/^\+[1-9][0-9]{7,14}$/', $telefono)) {
        responderPacienteError('El teléfono debe usar formato internacional, por ejemplo +595981123456.');
    }
    if ($urlFoto !== null && filter_var($urlFoto, FILTER_VALIDATE_URL) === false) {
        responderPacienteError('La URL de la foto no es válida.');
    }

    if ($id > 0) {
        $sql = 'UPDATE pacientes
                SET codigo = ?, nombre = ?, documento = ?, grupo_sanguineo = ?,
                    fecha_nacimiento = ?, es_femenino = ?, id_escuela = ?, id_pais = ?,
                    telefono = ?, url_foto = ?
                WHERE id_paciente = ?';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            'sssssiiissi',
            $codigo, $nombre, $documento, $grupo, $fechaNacimiento,
            $esFemenino, $idEscuela, $idPais, $telefono, $urlFoto, $id
        );
    } else {
        $sql = 'INSERT INTO pacientes
                    (codigo, nombre, documento, grupo_sanguineo, fecha_nacimiento,
                     es_femenino, id_escuela, id_pais, telefono, url_foto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            'sssssiiiss',
            $codigo, $nombre, $documento, $grupo, $fechaNacimiento,
            $esFemenino, $idEscuela, $idPais, $telefono, $urlFoto
        );
    }

    $stmt->execute();
    echo json_encode(['status' => 'ok']);
}

function cambiarEstadoPaciente(mysqli $conexion, bool $activar): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        responderPacienteError('ID no recibido.');
    }

    $actor = isset($_SESSION['usuario']['id_usuario'])
        ? (int) $_SESSION['usuario']['id_usuario']
        : null;
    $estado = $activar ? 1 : 0;
    $sql = $activar
        ? 'UPDATE pacientes SET activo = 1, eliminado_en = NULL, eliminado_por = NULL WHERE id_paciente = ?'
        : 'UPDATE pacientes SET activo = 0, eliminado_en = NOW(), eliminado_por = ? WHERE id_paciente = ?';
    $stmt = $conexion->prepare($sql);

    if ($activar) {
        $stmt->bind_param('i', $id);
    } else {
        $stmt->bind_param('ii', $actor, $id);
    }

    $stmt->execute();
    echo json_encode(['status' => 'ok', 'activo' => $estado]);
}

function buscarPacientes(mysqli $conexion): void
{
    $texto = trim($_GET['texto'] ?? '');
    $idEscuela = (int) ($_GET['escuela'] ?? $_GET['designacion'] ?? 0);
    $fecha = trim($_GET['fecha'] ?? '');
    $where = ['p.activo = 1'];
    $params = [];
    $types = '';

    if ($texto !== '') {
        $where[] = '(p.nombre LIKE ? OR p.documento LIKE ?)';
        $params[] = "%{$texto}%";
        $params[] = "%{$texto}%";
        $types .= 'ss';
    }
    if ($idEscuela > 0) {
        $where[] = 'p.id_escuela = ?';
        $params[] = $idEscuela;
        $types .= 'i';
    }
    if ($fecha !== '') {
        $where[] = 'DATE(p.creado_en) = ?';
        $params[] = $fecha;
        $types .= 's';
    }

    $sql = 'SELECT ' . camposPacienteSql() . '
            FROM pacientes p
            LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.id_paciente DESC';
    $stmt = $conexion->prepare($sql);
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
}

function fechaValida(string $fecha): bool
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}

function valorNullable($valor): ?string
{
    $normalizado = trim((string) ($valor ?? ''));

    return $normalizado === '' ? null : $normalizado;
}

function responderPacienteError(string $mensaje): void
{
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
