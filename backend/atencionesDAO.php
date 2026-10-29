<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/atenciones_service.php';

$q = $_GET['q'] ?? 'listar';
$identidad = requerirAlgunPermiso($conexion, [
    'postulantes_atencion.ver', 'postulantes_atencion.asignar',
    'postulantes_atencion.iniciar', 'postulantes_atencion.sincronizar',
    'postulantes_atencion.atender', 'postulantes_atencion.comentar',
    'postulantes_atencion.pagar', 'postulantes_atencion.estado',
    'postulantes_atencion.exportar',
], true);

if (!in_array($q, ['catalogos', 'listar', 'detalle'], true)) validarCsrf();

try {
    switch ($q) {
        case 'catalogos': catalogosAtencion($conexion, $identidad); break;
        case 'listar': listarAsignaciones($conexion, $identidad); break;
        case 'detalle':
            responderJson(['status' => 'ok', 'asignacion' => obtenerDetalleAsignacion($conexion, (int) ($_GET['id'] ?? 0), $identidad)]);
            break;
        case 'guardar': guardarAsignacion($conexion, $identidad); break;
        case 'sincronizar': sincronizarUna($conexion, $identidad); break;
        case 'sincronizar_dia': sincronizarDia($conexion, $identidad); break;
        case 'inicio': cambiarInicio($conexion, $identidad); break;
        case 'atender': atenderPostulante($conexion, $identidad); break;
        case 'comentar': comentarAtencion($conexion, $identidad); break;
        case 'pagar': pagarAsignacion($conexion, $identidad); break;
        case 'estado': cambiarEstadoAsignacion($conexion, $identidad); break;
        default: responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    if ($conexion->errno) $conexion->rollback();
    responderJson(['status' => 'error', 'mensaje' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if ($conexion->errno) $conexion->rollback();
    responderJson(['status' => 'error', 'mensaje' => $e->getMessage()], 422);
}

function exigirPermisoAtencion(array $identidad, string $permiso): void
{
    if (!tienePermiso($identidad, $permiso)) responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para esta operación.'], 403);
}

function catalogosAtencion(mysqli $db, array $identidad): void
{
    $escuelas = $db->query(
        "SELECT id_escuela, nombre,
                CONCAT(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')), '-',
                       LPAD(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')), 2, '0')) AS periodo
         FROM escuelas WHERE activo = 1 ORDER BY nombre, periodo DESC"
    )->fetch_all(MYSQLI_ASSOC);
    $estudios = $db->query(
        'SELECT id_estudio, estudio_referencial FROM postulantes_estudios_referenciales
         WHERE activo = 1 ORDER BY estudio_referencial'
    )->fetch_all(MYSQLI_ASSOC);
    $usuarios = $db->query(
        "SELECT DISTINCT u.id_usuario, u.nombre, u.usuario
         FROM usuarios u
         INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         INNER JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
         INNER JOIN rol_permisos rp ON rp.id_rol = r.id_rol
         INNER JOIN permisos p ON p.id_permiso = rp.id_permiso AND p.activo = 1
         WHERE u.activo = 1 AND p.codigo = 'postulantes_atencion.atender'
         ORDER BY u.nombre"
    )->fetch_all(MYSQLI_ASSOC);
    $permisos = [];
    foreach (['asignar', 'iniciar', 'sincronizar', 'atender', 'comentar', 'pagar', 'estado'] as $accion) {
        $permisos[$accion] = tienePermiso($identidad, 'postulantes_atencion.' . $accion);
    }
    $permisos['exportar'] = true;
    responderJson(['status' => 'ok', 'escuelas' => $escuelas, 'estudios' => $estudios,
        'usuarios' => $usuarios, 'permisos' => $permisos, 'hoy' => date('Y-m-d')]);
}

function listarAsignaciones(mysqli $db, array $identidad): void
{
    $filtroEstado = (string) ($_GET['estado'] ?? 'activas');
    if (!in_array($filtroEstado, ['activas', 'desactivadas'], true)) {
        responderJson(['status' => 'error', 'mensaje' => 'Estado de asignación no válido.'], 400);
    }
    if ($filtroEstado === 'desactivadas') {
        exigirPermisoAtencion($identidad, 'postulantes_atencion.estado');
    }
    $where = ['a.activo = ?'];
    $params = [$filtroEstado === 'activas' ? 1 : 0];
    $types = 'i';
    $fecha = trim((string) ($_GET['fecha'] ?? ''));
    if ($fecha !== '') {
        if (!fechaIsoValida($fecha)) responderJson(['status' => 'error', 'mensaje' => 'Fecha no válida.'], 400);
        $where[] = 'a.fecha = ?'; $params[] = $fecha; $types .= 's';
    }
    if (!esGestorAtenciones($identidad)) {
        $where[] = "EXISTS(SELECT 1 FROM postulantes_asignar_atencion_usuarios au
                            WHERE au.id_asignar_atencion = a.id_asignar_atencion AND au.id_usuario = ?)
                    AND (a.fecha < CURDATE() OR (a.fecha = CURDATE() AND a.empieza_atender = 1))";
        $params[] = (int) $identidad['id_usuario']; $types .= 'i';
    }
    $sql = "SELECT a.id_asignar_atencion, a.fecha, a.empieza_atender, a.pago_realizado, a.activo,
                   a.creado_en, u.nombre AS creado_por_nombre,
                   (SELECT GROUP_CONCAT(e.nombre ORDER BY e.nombre SEPARATOR ' · ')
                    FROM postulantes_asignar_atencion_escuelas ae
                    INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
                    WHERE ae.id_asignar_atencion = a.id_asignar_atencion) AS escuelas,
                   (SELECT GROUP_CONCAT(us.nombre ORDER BY us.nombre SEPARATOR ' · ')
                    FROM postulantes_asignar_atencion_usuarios au
                    INNER JOIN usuarios us ON us.id_usuario = au.id_usuario
                    WHERE au.id_asignar_atencion = a.id_asignar_atencion) AS profesionales,
                   (SELECT COUNT(*)
                    FROM postulantes_atencion pa
                    INNER JOIN pacientes p_total
                            ON p_total.id_paciente = pa.id_paciente AND p_total.activo = 1
                    WHERE pa.id_asignar_atencion = a.id_asignar_atencion) AS total,
                   (SELECT COUNT(*)
                    FROM postulantes_atencion pa
                    INNER JOIN pacientes p_atendidos
                            ON p_atendidos.id_paciente = pa.id_paciente AND p_atendidos.activo = 1
                    WHERE pa.id_asignar_atencion = a.id_asignar_atencion AND pa.atendido = 1) AS atendidos,
                   (SELECT COUNT(*)
                    FROM postulantes_atencion pa
                    INNER JOIN pacientes p_pagados
                            ON p_pagados.id_paciente = pa.id_paciente AND p_pagados.activo = 1
                    WHERE pa.id_asignar_atencion = a.id_asignar_atencion AND pa.pagado = 1) AS pagados
            FROM postulantes_asignar_atencion a
            INNER JOIN usuarios u ON u.id_usuario = a.creado_por" .
            ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY a.fecha DESC, a.id_asignar_atencion DESC';
    $stmt = $db->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($datos as &$dato) {
        foreach (['id_asignar_atencion', 'empieza_atender', 'pago_realizado', 'activo', 'total', 'atendidos', 'pagados'] as $campo) $dato[$campo] = (int) $dato[$campo];
    }
    responderJson(['status' => 'ok', 'asignaciones' => $datos]);
}

function validarIdsActivos(mysqli $db, array $ids, string $tabla, string $campo, string $etiqueta): void
{
    if (!$ids) responderJson(['status' => 'error', 'mensaje' => "Seleccione al menos {$etiqueta}."], 400);
    $lista = implode(',', array_map('intval', $ids));
    $total = (int) $db->query("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$campo} IN ({$lista}) AND activo = 1")->fetch_assoc()['total'];
    if ($total !== count($ids)) responderJson(['status' => 'error', 'mensaje' => "Hay {$etiqueta} inactivos o inexistentes."], 400);
}

function guardarAsignacion(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.asignar');
    $id = (int) ($_POST['id_asignar_atencion'] ?? 0);
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $usuarios = idsEnteros($_POST['usuarios'] ?? []);
    $escuelas = idsEnteros($_POST['escuelas'] ?? []);
    $estudios = idsEnteros($_POST['estudios'] ?? []);
    if (!fechaIsoValida($fecha)) responderJson(['status' => 'error', 'mensaje' => 'Seleccione una fecha válida.'], 400);
    validarIdsActivos($db, $escuelas, 'escuelas', 'id_escuela', 'una escuela');
    validarIdsActivos($db, $estudios, 'postulantes_estudios_referenciales', 'id_estudio', 'un estudio');
    if (!$usuarios) responderJson(['status' => 'error', 'mensaje' => 'Seleccione al menos un profesional.'], 400);
    $listaUsuarios = implode(',', array_map('intval', $usuarios));
    $elegibles = (int) $db->query(
        "SELECT COUNT(DISTINCT u.id_usuario) AS total FROM usuarios u
         INNER JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         INNER JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
         INNER JOIN rol_permisos rp ON rp.id_rol = r.id_rol
         INNER JOIN permisos p ON p.id_permiso = rp.id_permiso AND p.activo = 1
         WHERE u.activo = 1 AND u.id_usuario IN ({$listaUsuarios})
           AND p.codigo = 'postulantes_atencion.atender'"
    )->fetch_assoc()['total'];
    if ($elegibles !== count($usuarios)) responderJson(['status' => 'error', 'mensaje' => 'Todos los profesionales deben tener el permiso para atender.'], 400);

    $db->begin_transaction();
    if ($id > 0) {
        $actual = obtenerAsignacion($db, $id, $identidad, true);
        if ((int) $actual['pago_realizado'] === 1) responderJson(['status' => 'error', 'mensaje' => 'Una asignación pagada ya no puede editarse.'], 409);
        $stmt = $db->prepare('UPDATE postulantes_asignar_atencion SET fecha = ? WHERE id_asignar_atencion = ?');
        $stmt->bind_param('si', $fecha, $id); $stmt->execute();
    } else {
        $actor = (int) $identidad['id_usuario'];
        $stmt = $db->prepare('INSERT INTO postulantes_asignar_atencion (fecha, creado_por) VALUES (?, ?)');
        $stmt->bind_param('si', $fecha, $actor); $stmt->execute();
        $id = (int) $db->insert_id;
    }
    foreach (['usuarios', 'escuelas', 'estudios'] as $sufijo) {
        $db->query("DELETE FROM postulantes_asignar_atencion_{$sufijo} WHERE id_asignar_atencion = {$id}");
    }
    $mapas = [
        ['postulantes_asignar_atencion_usuarios', 'id_usuario', $usuarios],
        ['postulantes_asignar_atencion_escuelas', 'id_escuela', $escuelas],
        ['postulantes_asignar_atencion_estudios', 'id_estudio', $estudios],
    ];
    foreach ($mapas as [$tabla, $campo, $valores]) {
        $stmt = $db->prepare("INSERT INTO {$tabla} (id_asignar_atencion, {$campo}) VALUES (?, ?)");
        foreach ($valores as $valor) { $stmt->bind_param('ii', $id, $valor); $stmt->execute(); }
    }
    $db->query(
        "DELETE pae FROM postulantes_atencion_estudios pae
         INNER JOIN postulantes_atencion pa ON pa.id_postulante_atencion = pae.id_postulante_atencion
         LEFT JOIN postulantes_asignar_atencion_estudios ae
                ON ae.id_asignar_atencion = pa.id_asignar_atencion AND ae.id_estudio = pae.id_estudio
         WHERE pa.id_asignar_atencion = {$id} AND ae.id_estudio IS NULL AND pae.realizado = 0"
    );
    $agregados = sincronizarAsignacion($db, $id);
    $db->commit();
    responderJson(['status' => 'ok', 'mensaje' => "Asignación guardada; {$agregados} postulante(s) nuevo(s) sincronizado(s).", 'id_asignar_atencion' => $id]);
}

function sincronizarUna(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.sincronizar');
    $id = (int) ($_POST['id_asignar_atencion'] ?? 0);
    obtenerAsignacion($db, $id, $identidad);
    $agregados = sincronizarAsignacion($db, $id);
    responderJson(['status' => 'ok', 'mensaje' => "Sincronización terminada: {$agregados} postulante(s) agregado(s)."]);
}

function sincronizarDia(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.sincronizar');
    $fecha = trim((string) ($_POST['fecha'] ?? date('Y-m-d')));
    if (!fechaIsoValida($fecha)) responderJson(['status' => 'error', 'mensaje' => 'Fecha no válida.'], 400);
    $stmt = $db->prepare('SELECT id_asignar_atencion FROM postulantes_asignar_atencion WHERE fecha = ? AND activo = 1');
    $stmt->bind_param('s', $fecha); $stmt->execute();
    $total = 0; $cabeceras = 0;
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila) {
        $total += sincronizarAsignacion($db, (int) $fila['id_asignar_atencion']); $cabeceras++;
    }
    responderJson(['status' => 'ok', 'mensaje' => "Se actualizaron {$cabeceras} asignación(es) y se agregaron {$total} postulante(s)."]);
}

function cambiarInicio(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.iniciar');
    $id = (int) ($_POST['id_asignar_atencion'] ?? 0);
    $estado = ($_POST['empieza_atender'] ?? '0') === '1' ? 1 : 0;
    $asignacion = obtenerAsignacion($db, $id, $identidad);
    if ((int) $asignacion['pago_realizado'] === 1) responderJson(['status' => 'error', 'mensaje' => 'La asignación pagada está cerrada.'], 409);
    $stmt = $db->prepare('UPDATE postulantes_asignar_atencion SET empieza_atender = ? WHERE id_asignar_atencion = ?');
    $stmt->bind_param('ii', $estado, $id); $stmt->execute();
    responderJson(['status' => 'ok', 'mensaje' => $estado ? 'Atención habilitada.' : 'Atención pausada.']);
}

function atenderPostulante(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.atender');
    $id = (int) ($_POST['id_postulante_atencion'] ?? 0);
    $realizados = idsEnteros($_POST['estudios'] ?? []);
    $detalle = obtenerPostulanteAtencion($db, $id, $identidad);
    if (!asignacionEditablePorProfesional($detalle)) responderJson(['status' => 'error', 'mensaje' => 'La atención ya no puede modificarse.'], 409);
    $actor = (int) $identidad['id_usuario'];
    $db->begin_transaction();
    $db->query("UPDATE postulantes_atencion_estudios SET realizado = 0, realizado_en = NULL, actualizado_por = {$actor} WHERE id_postulante_atencion = {$id}");
    if ($realizados) {
        $lista = implode(',', array_map('intval', $realizados));
        $db->query("UPDATE postulantes_atencion_estudios SET realizado = 1, realizado_en = NOW(), actualizado_por = {$actor} WHERE id_postulante_atencion = {$id} AND id_estudio IN ({$lista})");
    }
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total, COALESCE(SUM(realizado = 1), 0) AS realizados
         FROM postulantes_atencion_estudios
         WHERE id_postulante_atencion = ?'
    );
    $stmt->bind_param('i', $id); $stmt->execute();
    $resumen = $stmt->get_result()->fetch_assoc();
    $atendido = (int) $resumen['total'] > 0 && (int) $resumen['realizados'] === (int) $resumen['total'] ? 1 : 0;
    $stmt = $db->prepare(
        'UPDATE postulantes_atencion
         SET atendido = ?,
             atendido_en = IF(? = 1, NOW(), NULL),
             atendido_por = IF(? = 1, ?, NULL)
         WHERE id_postulante_atencion = ?'
    );
    $stmt->bind_param('iiiii', $atendido, $atendido, $atendido, $actor, $id); $stmt->execute();
    $db->commit();
    responderJson([
        'status' => 'ok',
        'atendido' => $atendido,
        'mensaje' => $atendido ? 'Todos los estudios están completos. Postulante atendido.' : 'Estudios actualizados. La atención continúa pendiente.',
    ]);
}

function comentarAtencion(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.comentar');
    $id = (int) ($_POST['id_postulante_atencion'] ?? 0);
    $categoria = 'general';
    $texto = trim((string) ($_POST['texto'] ?? ''));
    if ($texto === '' || mb_strlen($texto) > 1500) responderJson(['status' => 'error', 'mensaje' => 'Escriba un comentario de hasta 1500 caracteres.'], 400);
    $db->begin_transaction();
    $detalle = obtenerPostulanteAtencion($db, $id, $identidad, true);
    $gestor = esGestorAtenciones($identidad);
    if ((int) $detalle['pago_realizado'] === 1 || (!$gestor && !asignacionEditablePorProfesional($detalle))) {
        responderJson(['status' => 'error', 'mensaje' => 'La conversación está en modo de solo lectura.'], 409);
    }
    $json = json_decode((string) $detalle['observacion'], true);
    if (!is_array($json)) $json = [];
    foreach (['clinica', 'general', 'doctor'] as $clave) if (!isset($json[$clave]) || !is_array($json[$clave])) $json[$clave] = [];
    $json[$categoria][] = [
        'id' => bin2hex(random_bytes(8)), 'texto' => $texto,
        'id_usuario' => (int) $identidad['id_usuario'], 'usuario' => (string) $identidad['nombre'],
        'fecha' => date(DATE_ATOM),
    ];
    $contenido = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $db->prepare('UPDATE postulantes_atencion SET observacion = ? WHERE id_postulante_atencion = ?');
    $stmt->bind_param('si', $contenido, $id); $stmt->execute();
    $db->commit();
    responderJson(['status' => 'ok', 'mensaje' => 'Comentario agregado.']);
}

function cambiarEstadoAsignacion(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.estado');
    $id = (int) ($_POST['id_asignar_atencion'] ?? 0);
    $valor = (string) ($_POST['activo'] ?? '');
    if ($id <= 0 || !in_array($valor, ['0', '1'], true)) {
        responderJson(['status' => 'error', 'mensaje' => 'Estado de asignación no válido.'], 400);
    }

    $activo = (int) $valor;
    $db->begin_transaction();
    $asignacion = obtenerAsignacion($db, $id, $identidad, true, true);
    if ((int) $asignacion['activo'] !== $activo) {
        $stmt = $db->prepare(
            'UPDATE postulantes_asignar_atencion
             SET activo = ?, empieza_atender = IF(? = 0, 0, empieza_atender)
             WHERE id_asignar_atencion = ?'
        );
        $stmt->bind_param('iii', $activo, $activo, $id);
        $stmt->execute();
    }
    $db->commit();

    responderJson([
        'status' => 'ok',
        'mensaje' => $activo
            ? 'Asignación reactivada. La atención permanece pausada hasta que vuelva a habilitarla.'
            : 'Asignación desactivada y atención pausada.',
    ]);
}

function pagarAsignacion(mysqli $db, array $identidad): void
{
    exigirPermisoAtencion($identidad, 'postulantes_atencion.pagar');
    $id = (int) ($_POST['id_asignar_atencion'] ?? 0);
    $pago = ($_POST['pago_realizado'] ?? '0') === '1' ? 1 : 0;
    $pagados = idsEnteros($_POST['postulantes'] ?? []);
    obtenerAsignacion($db, $id, $identidad);
    $db->begin_transaction();
    $db->query("UPDATE postulantes_atencion SET pagado = 0 WHERE id_asignar_atencion = {$id}");
    if ($pagados) {
        $lista = implode(',', array_map('intval', $pagados));
        $db->query("UPDATE postulantes_atencion SET pagado = 1 WHERE id_asignar_atencion = {$id} AND id_postulante_atencion IN ({$lista})");
    }
    $stmt = $db->prepare('UPDATE postulantes_asignar_atencion SET pago_realizado = ? WHERE id_asignar_atencion = ?');
    $stmt->bind_param('ii', $pago, $id); $stmt->execute();
    $db->commit();
    responderJson(['status' => 'ok', 'mensaje' => $pago ? 'Pago registrado; la atención quedó en solo lectura.' : 'Pago reabierto.']);
}
