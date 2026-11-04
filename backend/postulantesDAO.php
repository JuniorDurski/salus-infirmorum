<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/codigos_pacientes.php';

$q = $_GET['q'] ?? '';

if (in_array($q, ['listar', 'buscar'], true)) {
    requerirAutenticacion($conexion, 'pacientes.ver', true);
} else {
    requerirAutenticacion($conexion, 'pacientes.editar', true);
    validarCsrf();
}

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
    // codigo: el cargado a mano (para editar); codigo_generado: el que se muestra e imprime.
    return "p.id_paciente,
            p.id_paciente AS id_postulante,
            " . sqlIdEscuelaPaciente('p') . " AS id_escuela,
            " . sqlIdEscuelaPaciente('p') . " AS id_designacion,
            p.id_pais,
            " . sqlCodigoManualPaciente('p') . " AS codigo,

            " . sqlCodigoPaciente('p') . " AS codigo_generado,
            " . sqlCodigoInforme('p') . " AS codigo_informe,
            " . sqlOrdenDiaPacienteTexto('p') . " AS orden_dia,
            DATE_FORMAT(" . sqlMomentoRegistroPaciente('p') . ", '%Y-%m-%d') AS fecha_alta,
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
            DATE_FORMAT(" . sqlMomentoRegistroPaciente('p') . ", 'Dia:%d-%m-%Y Hora:%H:%i') AS fecha,
            e.nombre AS designacion,
            e.nombre AS escuela";
}

function listarPacientes(mysqli $conexion): void
{
    // El módulo Pacientes pide todos; los de postulantes ocultan los registros desactivados.
    $filtroPostulantes = ($_GET['todos'] ?? '') === '1' ? '' : ' AND ' . sqlVisibleEnPostulantes('p');

    $sql = 'SELECT ' . camposPacienteSql() . '
            FROM pacientes p
            LEFT JOIN escuelas e ON e.id_escuela = ' . sqlIdEscuelaPaciente('p') . '
            WHERE p.activo = 1' . $filtroPostulantes . '
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
    // Sin país elegido se asume Paraguay.
    $idPais = ($_POST['id_pais'] ?? '') !== '' ? (int) $_POST['id_pais'] : idPaisParaguay($conexion);
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
    $esRutaLocal = $urlFoto !== null && preg_match('#^uploads/pacientes/[0-9]+/[a-f0-9]{32}\.(jpg|png|webp)$#', $urlFoto);
    if ($urlFoto !== null && !$esRutaLocal && filter_var($urlFoto, FILTER_VALIDATE_URL) === false) {
        responderPacienteError('La URL de la foto no es válida.');
    }

    // Volver a cargar a alguien cuyo registro en postulantes fue desactivado: se
    // reutiliza su paciente (el documento es único) y se actualizan sus datos.
    if ($id === 0) {
        $stmt = $conexion->prepare(
            'SELECT p.id_paciente FROM pacientes p
             WHERE p.id_pais <=> ? AND p.documento = ? AND p.activo = 1
               AND NOT ' . sqlVisibleEnPostulantes('p') . '
             LIMIT 1'
        );
        $stmt->bind_param('is', $idPais, $documento);
        $stmt->execute();
        $id = (int) ($stmt->get_result()->fetch_assoc()['id_paciente'] ?? 0);
        $reingreso = $id > 0;
    }

    if ($id > 0) {
        $sql = 'UPDATE pacientes
                SET nombre = ?, documento = ?, grupo_sanguineo = ?,
                    fecha_nacimiento = ?, es_femenino = ?, id_pais = ?,
                    telefono = ?, url_foto = ?
                WHERE id_paciente = ?';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            'ssssiissi',
            $nombre, $documento, $grupo, $fechaNacimiento,
            $esFemenino, $idPais, $telefono, $urlFoto, $id
        );
    } else {
        $sql = 'INSERT INTO pacientes
                    (nombre, documento, grupo_sanguineo, fecha_nacimiento,
                     es_femenino, id_pais, telefono, url_foto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            'ssssiiss',
            $nombre, $documento, $grupo, $fechaNacimiento,
            $esFemenino, $idPais, $telefono, $urlFoto
        );
    }

    $stmt->execute();
    $idGuardado = $id > 0 ? $id : (int) $conexion->insert_id;
    // Re-ingreso: vuelve a aparecer como pendiente desde hoy y sigue el flujo normal.
    // El registro desactivado anterior no se toca.
    if ($reingreso ?? false) {
        $stmt = $conexion->prepare('UPDATE pacientes SET reingresado_en = NOW() WHERE id_paciente = ?');
        $stmt->bind_param('i', $idGuardado);
        $stmt->execute();
    }
    // El código vive en el registro de postulantes. Si todavía no está registrado,
    // la pantalla lo envía junto con el cobro que crea el registro.
    if ($codigo !== null && mb_strlen($codigo, 'UTF-8') > 20) {
        responderPacienteError('El código admite hasta 20 caracteres.');
    }
    $stmt = $conexion->prepare(
        'UPDATE postulantes_arreador_postulante SET codigo = ?
         WHERE id_arreador_postulante = (
             SELECT id FROM (
                 SELECT id_arreador_postulante AS id FROM postulantes_arreador_postulante
                 WHERE id_paciente = ? AND activo = 1 ORDER BY derivado_en DESC, id_arreador_postulante DESC LIMIT 1
             ) ultimo)'
    );
    $stmt->bind_param('si', $codigo, $idGuardado);
    $stmt->execute();
    recalcularOrdenPacientes($conexion);
    echo json_encode(['status' => 'ok', 'id_paciente' => $idGuardado]);
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
    recalcularOrdenPacientes($conexion);
    echo json_encode(['status' => 'ok', 'activo' => $estado]);
}

function buscarPacientes(mysqli $conexion): void
{
    $texto = trim($_GET['texto'] ?? '');
    $idEscuela = (int) ($_GET['escuela'] ?? $_GET['designacion'] ?? 0);
    $fecha = trim($_GET['fecha'] ?? '');
    $where = ['p.activo = 1', sqlVisibleEnPostulantes('p')];
    $params = [];
    $types = '';

    if ($texto !== '') {
        $where[] = '(p.nombre LIKE ? OR p.documento LIKE ? OR ' . sqlCodigoPaciente('p') . ' LIKE ?)';
        $params[] = "%{$texto}%";
        $params[] = "%{$texto}%";
        $params[] = "%{$texto}%";
        $types .= 'sss';
    }
    if ($idEscuela > 0) {
        $where[] = sqlIdEscuelaPaciente('p') . ' = ?';
        $params[] = $idEscuela;
        $types .= 'i';
    }
    if ($fecha !== '') {
        $where[] = sqlFechaRegistroPaciente('p') . ' = ?';
        $params[] = $fecha;
        $types .= 's';
    }

    // Informes: por día (reciente primero) y dentro del día por número de orden.
    $orden = ($_GET['orden'] ?? '') === 'orden_dia'
        ? sqlFechaRegistroPaciente('p') . ' DESC, ' . sqlOrdenDiaPaciente('p') . ' ASC, p.id_paciente ASC'
        : 'p.id_paciente DESC';

    $sql = 'SELECT ' . camposPacienteSql() . '
            FROM pacientes p
            LEFT JOIN escuelas e ON e.id_escuela = ' . sqlIdEscuelaPaciente('p') . '
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $orden;
    $stmt = $conexion->prepare($sql);
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
}

function idPaisParaguay(mysqli $conexion): ?int
{
    $fila = $conexion->query("SELECT id_pais FROM paises WHERE codigo_iso2 = 'PY' LIMIT 1")->fetch_assoc();
    return $fila !== null ? (int) $fila['id_pais'] : null;
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
