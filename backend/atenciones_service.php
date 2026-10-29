<?php

declare(strict_types=1);

function idsEnteros($valor): array
{
    $valores = is_array($valor) ? $valor : ($valor === null || $valor === '' ? [] : [$valor]);
    $ids = [];
    foreach ($valores as $item) {
        $id = filter_var($item, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false) $ids[(int) $id] = (int) $id;
    }
    return array_values($ids);
}

function fechaIsoValida(string $fecha): bool
{
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
}

function esGestorAtenciones(array $identidad): bool
{
    foreach (['postulantes_atencion.asignar', 'postulantes_atencion.iniciar',
              'postulantes_atencion.sincronizar', 'postulantes_atencion.pagar'] as $permiso) {
        if (tienePermiso($identidad, $permiso)) return true;
    }
    return false;
}

function obtenerAsignacion(mysqli $db, int $id, array $identidad, bool $bloquear = false): array
{
    $sql = "SELECT a.*, u.nombre AS creado_por_nombre,
                   EXISTS(SELECT 1 FROM postulantes_asignar_atencion_usuarios au
                          WHERE au.id_asignar_atencion = a.id_asignar_atencion
                            AND au.id_usuario = ?) AS usuario_asignado
            FROM postulantes_asignar_atencion a
            INNER JOIN usuarios u ON u.id_usuario = a.creado_por
            WHERE a.id_asignar_atencion = ?" . ($bloquear ? ' FOR UPDATE' : '');
    $stmt = $db->prepare($sql);
    $usuarioId = (int) $identidad['id_usuario'];
    $stmt->bind_param('ii', $usuarioId, $id);
    $stmt->execute();
    $asignacion = $stmt->get_result()->fetch_assoc();
    if (!$asignacion) responderJson(['status' => 'error', 'mensaje' => 'La asignación no existe.'], 404);

    if (!esGestorAtenciones($identidad)) {
        $hoy = date('Y-m-d');
        $visible = (int) $asignacion['usuario_asignado'] === 1
            && ($asignacion['fecha'] < $hoy
                || ($asignacion['fecha'] === $hoy && (int) $asignacion['empieza_atender'] === 1));
        if (!$visible) responderJson(['status' => 'error', 'mensaje' => 'No puede acceder a esta asignación.'], 403);
    }
    return $asignacion;
}

function asignacionEditablePorProfesional(array $asignacion): bool
{
    return (int) $asignacion['usuario_asignado'] === 1
        && (int) $asignacion['empieza_atender'] === 1
        && (int) $asignacion['pago_realizado'] === 0
        && $asignacion['fecha'] <= date('Y-m-d');
}

function sincronizarAsignacion(mysqli $db, int $idAsignacion): int
{
    $stmt = $db->prepare(
        "INSERT IGNORE INTO postulantes_atencion
            (id_asignar_atencion, id_paciente, observacion)
         SELECT a.id_asignar_atencion, p.id_paciente,
                JSON_OBJECT('clinica', JSON_ARRAY(), 'general', JSON_ARRAY(), 'doctor', JSON_ARRAY())
         FROM postulantes_asignar_atencion a
         INNER JOIN postulantes_asignar_atencion_escuelas ae
                 ON ae.id_asignar_atencion = a.id_asignar_atencion
         INNER JOIN pacientes p ON p.id_escuela = ae.id_escuela
         WHERE a.id_asignar_atencion = ?
           AND p.activo = 1
           AND DATE(p.creado_en) = a.fecha"
    );
    $stmt->bind_param('i', $idAsignacion);
    $stmt->execute();
    $agregados = $stmt->affected_rows;

    $stmt = $db->prepare(
        'INSERT IGNORE INTO postulantes_atencion_estudios (id_postulante_atencion, id_estudio)
         SELECT pa.id_postulante_atencion, ae.id_estudio
         FROM postulantes_atencion pa
         INNER JOIN postulantes_asignar_atencion_estudios ae
                 ON ae.id_asignar_atencion = pa.id_asignar_atencion
         WHERE pa.id_asignar_atencion = ?'
    );
    $stmt->bind_param('i', $idAsignacion);
    $stmt->execute();
    return max(0, $agregados);
}

function obtenerDetalleAsignacion(mysqli $db, int $id, array $identidad): array
{
    $asignacion = obtenerAsignacion($db, $id, $identidad);
    foreach (['empieza_atender', 'pago_realizado', 'usuario_asignado'] as $campo) {
        $asignacion[$campo] = (int) $asignacion[$campo];
    }

    $consultas = [
        'usuarios' => "SELECT u.id_usuario, u.nombre, u.usuario
                       FROM postulantes_asignar_atencion_usuarios au
                       INNER JOIN usuarios u ON u.id_usuario = au.id_usuario
                       WHERE au.id_asignar_atencion = ? ORDER BY u.nombre",
        'escuelas' => "SELECT e.id_escuela, e.nombre,
                              CONCAT(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.anio')), '-',
                                     LPAD(JSON_UNQUOTE(JSON_EXTRACT(e.periodo, '$.mes')), 2, '0')) AS periodo
                       FROM postulantes_asignar_atencion_escuelas ae
                       INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
                       WHERE ae.id_asignar_atencion = ? ORDER BY e.nombre",
        'estudios' => "SELECT er.id_estudio, er.estudio_referencial
                       FROM postulantes_asignar_atencion_estudios ae
                       INNER JOIN postulantes_estudios_referenciales er ON er.id_estudio = ae.id_estudio
                       WHERE ae.id_asignar_atencion = ? ORDER BY er.estudio_referencial",
    ];
    foreach ($consultas as $clave => $sql) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $asignacion[$clave] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $stmt = $db->prepare(
        "SELECT pa.id_postulante_atencion, pa.id_paciente, p.nombre, p.documento,
                p.codigo, e.nombre AS escuela, pa.llamado, pa.llamado_en,
                pa.atendido, pa.atendido_en, pa.pagado, pa.observacion,
                COALESCE(ua.nombre, '') AS atendido_por_nombre
         FROM postulantes_atencion pa
         INNER JOIN pacientes p ON p.id_paciente = pa.id_paciente
         LEFT JOIN escuelas e ON e.id_escuela = p.id_escuela
         LEFT JOIN usuarios ua ON ua.id_usuario = pa.atendido_por
         WHERE pa.id_asignar_atencion = ?
         ORDER BY pa.atendido, pa.llamado DESC, p.nombre"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $postulantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmtEstudios = $db->prepare(
        'SELECT pae.id_estudio, er.estudio_referencial, pae.realizado, pae.realizado_en
         FROM postulantes_atencion_estudios pae
         INNER JOIN postulantes_estudios_referenciales er ON er.id_estudio = pae.id_estudio
         WHERE pae.id_postulante_atencion = ? ORDER BY er.estudio_referencial'
    );
    foreach ($postulantes as &$postulante) {
        foreach (['id_postulante_atencion', 'id_paciente', 'llamado', 'atendido', 'pagado'] as $campo) {
            $postulante[$campo] = (int) $postulante[$campo];
        }
        $detalleId = $postulante['id_postulante_atencion'];
        $stmtEstudios->bind_param('i', $detalleId);
        $stmtEstudios->execute();
        $postulante['estudios'] = $stmtEstudios->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($postulante['estudios'] as &$estudio) {
            $estudio['id_estudio'] = (int) $estudio['id_estudio'];
            $estudio['realizado'] = (int) $estudio['realizado'];
        }
        unset($estudio);
        $comentarios = json_decode((string) $postulante['observacion'], true);
        $postulante['observacion'] = is_array($comentarios)
            ? $comentarios : ['clinica' => [], 'general' => [], 'doctor' => []];
    }
    unset($postulante);
    $asignacion['postulantes'] = $postulantes;
    $asignacion['editable_profesional'] = asignacionEditablePorProfesional($asignacion);
    return $asignacion;
}

function obtenerPostulanteAtencion(mysqli $db, int $detalleId, array $identidad, bool $bloquear = false): array
{
    $stmt = $db->prepare(
        'SELECT pa.*, a.fecha, a.empieza_atender, a.pago_realizado
         FROM postulantes_atencion pa
         INNER JOIN postulantes_asignar_atencion a ON a.id_asignar_atencion = pa.id_asignar_atencion
         WHERE pa.id_postulante_atencion = ?' . ($bloquear ? ' FOR UPDATE' : '')
    );
    $stmt->bind_param('i', $detalleId);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    if (!$detalle) responderJson(['status' => 'error', 'mensaje' => 'El postulante no pertenece a la lista.'], 404);
    $asignacion = obtenerAsignacion($db, (int) $detalle['id_asignar_atencion'], $identidad, false);
    $detalle['usuario_asignado'] = $asignacion['usuario_asignado'];
    return $detalle;
}
