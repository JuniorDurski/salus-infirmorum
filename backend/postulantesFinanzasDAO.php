<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? '';

if (in_array($q, ['resumen', 'configuracion', 'pagos'], true)) {
    requerirAutenticacion($conexion, 'pacientes.ver', true);
} else {
    requerirAutenticacion($conexion, 'pacientes.editar', true);
    validarCsrf();
}

try {
    switch ($q) {
        case 'resumen':
            resumenFinanciero($conexion);
            break;
        case 'configuracion':
            configuracionFinanciera($conexion);
            break;
        case 'pagos':
            listarPagos($conexion);
            break;
        case 'guardar_arreador':
            guardarArreador($conexion);
            break;
        case 'estado_arreador':
            cambiarEstadoArreador($conexion);
            break;
        case 'guardar_convenio':
            guardarConvenio($conexion);
            break;
        case 'estado_convenio':
            cambiarEstadoConvenio($conexion);
            break;
        case 'asignar':
            asignarPostulante($conexion);
            break;
        case 'pagar':
            registrarPago($conexion);
            break;
        case 'registrar_cobro':
            registrarCobroCompleto($conexion);
            break;
        default:
            errorFinanzas('Operación no válida.');
    }
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No fue posible guardar la operación: ' . $e->getMessage()], 422);
}

function resumenFinanciero(mysqli $db): void
{
    $anio = anioActualDb($db);
    $arreadores = $db->query(
        'SELECT a.id_arreador, a.id_pais, a.nombre, a.institucion, a.telefono, a.correo, a.activo,
                COALESCE(p.nombre, \'\') AS pais
         FROM postulantes_arreadores a
         LEFT JOIN paises p ON p.id_pais = a.id_pais
         ORDER BY a.activo DESC, a.nombre'
    )->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare(
        'SELECT ae.id_arreador_escuela, ae.id_arreador, ae.id_escuela, ae.anio,
                ae.monto_total_hombre, ae.monto_total_mujer,
                ae.comision_arreador, ae.monto_clinica, ae.activo,
                a.nombre AS arreador, e.nombre AS escuela
         FROM postulantes_arreador_escuela ae
         INNER JOIN postulantes_arreadores a ON a.id_arreador = ae.id_arreador
         INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
         WHERE ae.anio = ?
         ORDER BY ae.activo DESC, e.nombre, a.nombre'
    );
    $stmt->bind_param('i', $anio);
    $stmt->execute();
    $convenios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->prepare(
        'SELECT ap.id_arreador_postulante, ap.id_paciente, ap.id_arreador,
                ap.id_arreador_escuela, ap.derivado_en, ap.observacion,
                p.nombre AS postulante, p.documento, p.id_escuela,
                a.nombre AS arreador, e.nombre AS escuela,
                ae.anio, p.es_femenino,
                CASE WHEN p.es_femenino = 1
                     THEN ae.monto_total_mujer ELSE ae.monto_total_hombre END AS monto_total,
                ae.comision_arreador, ae.monto_clinica,
                COALESCE(SUM(pg.monto), 0) AS pagado,
                GREATEST(
                    CASE WHEN p.es_femenino = 1
                         THEN ae.monto_total_mujer ELSE ae.monto_total_hombre END
                    - COALESCE(SUM(pg.monto), 0), 0
                ) AS saldo,
                MAX(pg.fecha) AS ultimo_pago
         FROM postulantes_arreador_postulante ap
         INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
         INNER JOIN postulantes_arreadores a ON a.id_arreador = ap.id_arreador
         INNER JOIN postulantes_arreador_escuela ae
                 ON ae.id_arreador_escuela = ap.id_arreador_escuela
         INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
         LEFT JOIN postulantes_pago pg
                ON pg.id_arreador_postulante = ap.id_arreador_postulante
         WHERE ae.anio = ?
         GROUP BY ap.id_arreador_postulante, ap.id_paciente, ap.id_arreador,
                  ap.id_arreador_escuela, ap.derivado_en, ap.observacion,
                  p.nombre, p.documento, p.id_escuela, a.nombre, e.nombre,
                  ae.anio, p.es_femenino, ae.monto_total_hombre,
                  ae.monto_total_mujer, ae.comision_arreador, ae.monto_clinica
         ORDER BY p.nombre'
    );
    $stmt->bind_param('i', $anio);
    $stmt->execute();
    $asignaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $metodos = $db->query(
        'SELECT id_metodo_pago, codigo, nombre
         FROM metodos_pago WHERE activo = 1 ORDER BY nombre'
    )->fetch_all(MYSQLI_ASSOC);

    responderJson([
        'status' => 'ok',
        'anio' => $anio,
        'arreadores' => $arreadores,
        'convenios' => $convenios,
        'asignaciones' => $asignaciones,
        'metodos_pago' => $metodos,
    ]);
}

function listarPagos(mysqli $db): void
{
    $id = enteroPositivo($_GET['id'] ?? 0, 'Asignación no válida.');
    $stmt = $db->prepare(
        'SELECT pg.id_pago, pg.fecha, pg.monto, pg.saldo, pg.observacion,
                mp.nombre AS metodo_pago, COALESCE(u.nombre, \'SISTEMA\') AS registrado_por
         FROM postulantes_pago pg
         INNER JOIN metodos_pago mp ON mp.id_metodo_pago = pg.id_metodo_pago
         LEFT JOIN usuarios u ON u.id_usuario = pg.registrado_por
         WHERE pg.id_arreador_postulante = ?
         ORDER BY pg.fecha DESC, pg.id_pago DESC'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    responderJson(['status' => 'ok', 'pagos' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function configuracionFinanciera(mysqli $db): void
{
    $anio = anioActualDb($db);
    $arreadores = $db->query(
        'SELECT a.id_arreador, a.id_pais, a.nombre, a.institucion, a.telefono, a.correo, a.activo,
                COALESCE(p.nombre, \'\') AS pais
         FROM postulantes_arreadores a
         LEFT JOIN paises p ON p.id_pais = a.id_pais
         ORDER BY a.activo DESC, a.nombre'
    )->fetch_all(MYSQLI_ASSOC);
    $convenios = $db->query(
        'SELECT ae.id_arreador_escuela, ae.id_arreador, ae.id_escuela, ae.anio,
                ae.monto_total_hombre, ae.monto_total_mujer,
                ae.comision_arreador, ae.monto_clinica, ae.activo,
                a.nombre AS arreador, e.nombre AS escuela
         FROM postulantes_arreador_escuela ae
         INNER JOIN postulantes_arreadores a ON a.id_arreador = ae.id_arreador
         INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
         ORDER BY ae.anio DESC, ae.activo DESC, e.nombre, a.nombre'
    )->fetch_all(MYSQLI_ASSOC);

    responderJson([
        'status' => 'ok',
        'anio' => $anio,
        'arreadores' => $arreadores,
        'convenios' => $convenios,
    ]);
}

function guardarArreador(mysqli $db): void
{
    $id = (int) ($_POST['id_arreador'] ?? 0);
    $nombre = mb_strtoupper(trim((string) ($_POST['nombre'] ?? '')), 'UTF-8');
    $institucion = textoNullable($_POST['institucion'] ?? null, true);
    $telefono = textoNullable($_POST['telefono'] ?? null);
    $correo = textoNullable($_POST['correo'] ?? null);
    $idPais = ($_POST['id_pais'] ?? '') === '' ? null : (int) $_POST['id_pais'];

    if ($nombre === '') {
        errorFinanzas('El nombre del arreador es obligatorio.');
    }
    if ($telefono !== null && !preg_match('/^\+[1-9][0-9]{7,14}$/', $telefono)) {
        errorFinanzas('El teléfono debe usar formato internacional, por ejemplo +595981123456.');
    }
    if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        errorFinanzas('El correo no es válido.');
    }

    if ($id > 0) {
        $stmt = $db->prepare(
            'UPDATE postulantes_arreadores
             SET nombre = ?, institucion = ?, telefono = ?, correo = ?, id_pais = ?
             WHERE id_arreador = ?'
        );
        $stmt->bind_param('ssssii', $nombre, $institucion, $telefono, $correo, $idPais, $id);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO postulantes_arreadores (nombre, institucion, telefono, correo, id_pais)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssi', $nombre, $institucion, $telefono, $correo, $idPais);
    }
    $stmt->execute();
    responderJson(['status' => 'ok', 'id_arreador' => $id > 0 ? $id : $db->insert_id]);
}

function cambiarEstadoArreador(mysqli $db): void
{
    $id = enteroPositivo($_POST['id_arreador'] ?? 0, 'Arreador no válido.');
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $stmt = $db->prepare('UPDATE postulantes_arreadores SET activo = ? WHERE id_arreador = ?');
    $stmt->bind_param('ii', $activo, $id);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function guardarConvenio(mysqli $db): void
{
    $id = (int) ($_POST['id_arreador_escuela'] ?? 0);
    $idArreador = enteroPositivo($_POST['id_arreador'] ?? 0, 'Seleccione un arreador.');
    $idEscuela = enteroPositivo($_POST['id_escuela'] ?? 0, 'Seleccione una escuela.');
    $totalHombre = montoValido($_POST['monto_total_hombre'] ?? '', 'El cobro para hombres');
    $totalMujer = montoValido($_POST['monto_total_mujer'] ?? '', 'El cobro para mujeres');
    $comision = montoValido($_POST['comision_arreador'] ?? '', 'La comisión', true);
    $clinica = montoValido($_POST['monto_clinica'] ?? '', 'El importe para la clínica', true);
    $anio = anioActualDb($db);

    if (montoCentavos($comision) > montoCentavos($totalHombre)
        || montoCentavos($comision) > montoCentavos($totalMujer)) {
        errorFinanzas('La comisión no puede superar el cobro de hombres ni el de mujeres.');
    }
    if ($id > 0) {
        $stmt = $db->prepare(
            'SELECT ae.id_arreador, ae.id_escuela,
                    COUNT(DISTINCT ap.id_arreador_postulante) AS asignaciones,
                    COUNT(pg.id_pago) AS pagos
             FROM postulantes_arreador_escuela ae
             LEFT JOIN postulantes_arreador_postulante ap
                    ON ap.id_arreador_escuela = ae.id_arreador_escuela
             LEFT JOIN postulantes_pago pg
                    ON pg.id_arreador_postulante = ap.id_arreador_postulante
             WHERE ae.id_arreador_escuela = ?
             GROUP BY ae.id_arreador_escuela, ae.id_arreador, ae.id_escuela'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        if ($actual === null) {
            errorFinanzas('No se encontró el convenio.');
        }
        if ((int) $actual['pagos'] > 0) {
            errorFinanzas('El convenio ya tiene pagos y sus importes no pueden modificarse.');
        }
        if ((int) $actual['asignaciones'] > 0
            && ((int) $actual['id_arreador'] !== $idArreador || (int) $actual['id_escuela'] !== $idEscuela)) {
            errorFinanzas('Un convenio con postulantes asignados no puede cambiar de arreador ni de escuela.');
        }
        $stmt = $db->prepare(
            'UPDATE postulantes_arreador_escuela
             SET id_arreador = ?, id_escuela = ?, monto_total_hombre = ?,
                 monto_total_mujer = ?, comision_arreador = ?, monto_clinica = ?
             WHERE id_arreador_escuela = ? AND anio = ?'
        );
        $stmt->bind_param(
            'iissssii',
            $idArreador, $idEscuela, $totalHombre, $totalMujer,
            $comision, $clinica, $id, $anio
        );
    } else {
        $stmt = $db->prepare(
            'INSERT INTO postulantes_arreador_escuela
                (id_arreador, id_escuela, anio, monto_total_hombre,
                 monto_total_mujer, comision_arreador, monto_clinica)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iiissss',
            $idArreador, $idEscuela, $anio, $totalHombre,
            $totalMujer, $comision, $clinica
        );
    }
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function cambiarEstadoConvenio(mysqli $db): void
{
    $id = enteroPositivo($_POST['id_arreador_escuela'] ?? 0, 'Convenio no válido.');
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $anio = anioActualDb($db);
    $stmt = $db->prepare(
        'UPDATE postulantes_arreador_escuela SET activo = ?
         WHERE id_arreador_escuela = ? AND anio = ?'
    );
    $stmt->bind_param('iii', $activo, $id, $anio);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function asignarPostulante(mysqli $db): void
{
    $idPaciente = enteroPositivo($_POST['id_paciente'] ?? 0, 'Postulante no válido.');
    $idConvenio = enteroPositivo($_POST['id_arreador_escuela'] ?? 0, 'Seleccione un convenio.');
    $observacion = textoNullable($_POST['observacion'] ?? null);
    $anio = anioActualDb($db);
    $actor = usuarioSesionId();

    $db->begin_transaction();
    try {
        $stmt = $db->prepare(
            'SELECT ae.id_arreador, ae.id_escuela
             FROM postulantes_arreador_escuela ae
             INNER JOIN postulantes_arreadores a ON a.id_arreador = ae.id_arreador
             INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
             INNER JOIN pacientes p ON p.id_paciente = ? AND p.id_escuela = ae.id_escuela
             WHERE ae.id_arreador_escuela = ? AND ae.anio = ?
               AND ae.activo = 1 AND a.activo = 1 AND e.activo = 1 AND p.activo = 1
             FOR UPDATE'
        );
        $stmt->bind_param('iii', $idPaciente, $idConvenio, $anio);
        $stmt->execute();
        $convenio = $stmt->get_result()->fetch_assoc();
        if ($convenio === null) {
            errorFinanzas('El convenio debe estar activo, ser del año actual y corresponder a la escuela del postulante.');
        }

        $stmt = $db->prepare(
            'SELECT ap.id_arreador_postulante,
                    EXISTS(SELECT 1 FROM postulantes_pago pg
                           WHERE pg.id_arreador_postulante = ap.id_arreador_postulante) AS tiene_pagos
             FROM postulantes_arreador_postulante ap
             INNER JOIN postulantes_arreador_escuela ae
                     ON ae.id_arreador_escuela = ap.id_arreador_escuela
             WHERE ap.id_paciente = ? AND ae.anio = ?
             LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('ii', $idPaciente, $anio);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();

        if ($actual !== null) {
            if ((int) $actual['tiene_pagos'] === 1) {
                errorFinanzas('La asignación ya tiene pagos y no puede cambiarse de convenio.');
            }
            $idAsignacion = (int) $actual['id_arreador_postulante'];
            $stmt = $db->prepare(
                'UPDATE postulantes_arreador_postulante
                 SET id_arreador = ?, id_arreador_escuela = ?, observacion = ?,
                     derivado_en = NOW(), registrado_por = ?
                 WHERE id_arreador_postulante = ?'
            );
            $stmt->bind_param('iisii', $convenio['id_arreador'], $idConvenio, $observacion, $actor, $idAsignacion);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO postulantes_arreador_postulante
                    (id_arreador, id_arreador_escuela, id_paciente, observacion, registrado_por)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('iiisi', $convenio['id_arreador'], $idConvenio, $idPaciente, $observacion, $actor);
        }
        $stmt->execute();
        $db->commit();
        responderJson(['status' => 'ok']);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function registrarPago(mysqli $db): void
{
    $idAsignacion = enteroPositivo($_POST['id_arreador_postulante'] ?? 0, 'Asignación no válida.');
    $idMetodo = enteroPositivo($_POST['id_metodo_pago'] ?? 0, 'Seleccione un método de pago.');
    $monto = montoValido($_POST['monto'] ?? '', 'El importe');
    $fechaRecibida = trim((string) ($_POST['fecha'] ?? ''));
    $observacion = textoNullable($_POST['observacion'] ?? null);
    $actor = usuarioSesionId();

    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaRecibida);
    if ($fecha === false || $fecha->format('Y-m-d') !== $fechaRecibida || $fechaRecibida > fechaActualDb($db)) {
        errorFinanzas('La fecha de pago no es válida ni puede estar en el futuro.');
    }
    $db->begin_transaction();
    try {
        $stmt = $db->prepare(
            'SELECT CASE WHEN p.es_femenino = 1
                         THEN ae.monto_total_mujer ELSE ae.monto_total_hombre END AS monto_total
             FROM postulantes_arreador_postulante ap
             INNER JOIN postulantes_arreador_escuela ae
                     ON ae.id_arreador_escuela = ap.id_arreador_escuela
             INNER JOIN pacientes p ON p.id_paciente = ap.id_paciente
             WHERE ap.id_arreador_postulante = ?
             FOR UPDATE'
        );
        $stmt->bind_param('i', $idAsignacion);
        $stmt->execute();
        $asignacion = $stmt->get_result()->fetch_assoc();
        if ($asignacion === null) {
            errorFinanzas('No se encontró la asignación del postulante.');
        }

        $stmt = $db->prepare('SELECT id_metodo_pago FROM metodos_pago WHERE id_metodo_pago = ? AND activo = 1');
        $stmt->bind_param('i', $idMetodo);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc() === null) {
            errorFinanzas('El método de pago no está disponible.');
        }

        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(monto), 0) AS pagado
             FROM postulantes_pago WHERE id_arreador_postulante = ?'
        );
        $stmt->bind_param('i', $idAsignacion);
        $stmt->execute();
        $pagado = $stmt->get_result()->fetch_assoc()['pagado'];
        $saldoCentavos = montoCentavos($asignacion['monto_total'])
            - montoCentavos($pagado)
            - montoCentavos($monto);
        if ($saldoCentavos < 0) {
            errorFinanzas('El importe supera el saldo pendiente.');
        }
        $saldo = number_format($saldoCentavos / 100, 2, '.', '');

        $stmt = $db->prepare(
            'INSERT INTO postulantes_pago
                (id_arreador_postulante, id_metodo_pago, fecha, monto, saldo, observacion, registrado_por)
             VALUES (?, ?, CONCAT(?, \' \', DATE_FORMAT(NOW(), \'%H:%i:%s\')), ?, ?, ?, ?)'
        );
        $stmt->bind_param('iissssi', $idAsignacion, $idMetodo, $fechaRecibida, $monto, $saldo, $observacion, $actor);
        $stmt->execute();
        $db->commit();
        responderJson(['status' => 'ok', 'saldo' => $saldo]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function registrarCobroCompleto(mysqli $db): void
{
    $idPaciente = enteroPositivo($_POST['id_paciente'] ?? 0, 'Seleccione un postulante.');
    $idConvenio = enteroPositivo($_POST['id_arreador_escuela'] ?? 0, 'Seleccione un convenio.');
    $fechaRecibida = trim((string) ($_POST['fecha'] ?? ''));
    $observacion = textoNullable($_POST['observacion'] ?? null);
    $pagosRecibidos = json_decode((string) ($_POST['pagos'] ?? ''), true);
    $actor = usuarioSesionId();
    $anio = anioActualDb($db);

    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaRecibida);
    if ($fecha === false || $fecha->format('Y-m-d') !== $fechaRecibida || $fechaRecibida > fechaActualDb($db)) {
        errorFinanzas('La fecha de pago no es válida ni puede estar en el futuro.');
    }
    if (!is_array($pagosRecibidos) || $pagosRecibidos === [] || count($pagosRecibidos) > 20) {
        errorFinanzas('Agregue entre uno y veinte métodos de pago.');
    }

    $pagos = [];
    $metodosUsados = [];
    foreach ($pagosRecibidos as $posicion => $pago) {
        if (!is_array($pago)) {
            errorFinanzas('Los datos de pago no son válidos.');
        }
        $idMetodo = enteroPositivo($pago['id_metodo_pago'] ?? 0, 'Seleccione el método de cada pago.');
        if (isset($metodosUsados[$idMetodo])) {
            errorFinanzas('Cada método de pago debe aparecer una sola vez.');
        }
        $metodosUsados[$idMetodo] = true;
        $pagos[] = [
            'id_metodo_pago' => $idMetodo,
            'monto' => montoValido($pago['monto'] ?? '', 'El importe del pago ' . ($posicion + 1)),
        ];
    }

    $db->begin_transaction();
    try {
        $stmt = $db->prepare(
            'SELECT ae.id_arreador, ae.id_escuela,
                    CASE WHEN p.es_femenino = 1
                         THEN ae.monto_total_mujer ELSE ae.monto_total_hombre END AS monto_total
             FROM postulantes_arreador_escuela ae
             INNER JOIN postulantes_arreadores a ON a.id_arreador = ae.id_arreador
             INNER JOIN escuelas e ON e.id_escuela = ae.id_escuela
             INNER JOIN pacientes p ON p.id_paciente = ? AND p.id_escuela = ae.id_escuela
             WHERE ae.id_arreador_escuela = ? AND ae.anio = ?
               AND ae.activo = 1 AND a.activo = 1 AND e.activo = 1 AND p.activo = 1
             FOR UPDATE'
        );
        $stmt->bind_param('iii', $idPaciente, $idConvenio, $anio);
        $stmt->execute();
        $convenio = $stmt->get_result()->fetch_assoc();
        if ($convenio === null) {
            errorFinanzas('El convenio debe estar activo, ser del año actual y corresponder a la escuela del postulante.');
        }

        $stmt = $db->prepare(
            'SELECT ap.id_arreador_postulante, ap.id_arreador_escuela,
                    COALESCE(SUM(pg.monto), 0) AS pagado
             FROM postulantes_arreador_postulante ap
             INNER JOIN postulantes_arreador_escuela ae
                     ON ae.id_arreador_escuela = ap.id_arreador_escuela
             LEFT JOIN postulantes_pago pg
                    ON pg.id_arreador_postulante = ap.id_arreador_postulante
             WHERE ap.id_paciente = ? AND ae.anio = ?
             GROUP BY ap.id_arreador_postulante, ap.id_arreador_escuela
             LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('ii', $idPaciente, $anio);
        $stmt->execute();
        $asignacion = $stmt->get_result()->fetch_assoc();
        $pagadoCentavos = $asignacion === null ? 0 : montoCentavos($asignacion['pagado']);

        if ($asignacion !== null) {
            if ((int) $asignacion['id_arreador_escuela'] !== $idConvenio && $pagadoCentavos > 0) {
                errorFinanzas('El postulante ya tiene pagos en otro convenio y no puede cambiarse.');
            }
            $idAsignacion = (int) $asignacion['id_arreador_postulante'];
            $stmt = $db->prepare(
                'UPDATE postulantes_arreador_postulante
                 SET id_arreador = ?, id_arreador_escuela = ?, observacion = ?,
                     derivado_en = NOW(), registrado_por = ?
                 WHERE id_arreador_postulante = ?'
            );
            $stmt->bind_param('iisii', $convenio['id_arreador'], $idConvenio, $observacion, $actor, $idAsignacion);
            $stmt->execute();
        } else {
            $stmt = $db->prepare(
                'INSERT INTO postulantes_arreador_postulante
                    (id_arreador, id_arreador_escuela, id_paciente, observacion, registrado_por)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('iiisi', $convenio['id_arreador'], $idConvenio, $idPaciente, $observacion, $actor);
            $stmt->execute();
            $idAsignacion = (int) $db->insert_id;
        }

        $saldoCentavos = montoCentavos($convenio['monto_total']) - $pagadoCentavos;
        $stmtMetodo = $db->prepare(
            'SELECT id_metodo_pago FROM metodos_pago WHERE id_metodo_pago = ? AND activo = 1'
        );
        $stmtPago = $db->prepare(
            'INSERT INTO postulantes_pago
                (id_arreador_postulante, id_metodo_pago, fecha, monto, saldo, observacion, registrado_por)
             VALUES (?, ?, CONCAT(?, \' \', DATE_FORMAT(NOW(), \'%H:%i:%s\')), ?, ?, ?, ?)'
        );
        foreach ($pagos as $pago) {
            $idMetodo = $pago['id_metodo_pago'];
            $stmtMetodo->bind_param('i', $idMetodo);
            $stmtMetodo->execute();
            if ($stmtMetodo->get_result()->fetch_assoc() === null) {
                errorFinanzas('Uno de los métodos de pago ya no está disponible.');
            }

            $saldoCentavos -= montoCentavos($pago['monto']);
            if ($saldoCentavos < 0) {
                errorFinanzas('La suma de los pagos supera el saldo pendiente.');
            }
            $saldo = number_format($saldoCentavos / 100, 2, '.', '');
            $monto = $pago['monto'];
            $stmtPago->bind_param(
                'iissssi',
                $idAsignacion, $idMetodo, $fechaRecibida, $monto, $saldo, $observacion, $actor
            );
            $stmtPago->execute();
        }

        $db->commit();
        responderJson([
            'status' => 'ok',
            'id_arreador_postulante' => $idAsignacion,
            'saldo' => number_format($saldoCentavos / 100, 2, '.', ''),
        ]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function montoValido($valor, string $campo, bool $permiteCero = false): string
{
    $normalizado = preg_replace('/\s+/', '', trim((string) $valor));
    if (preg_match('/^\d{1,10}$/', $normalizado)) {
        $unidades = $normalizado;
    } elseif (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $normalizado)) {
        $unidades = str_replace('.', '', $normalizado);
    } elseif (preg_match('/^(\d{1,10})\.00$/', $normalizado, $partes)) {
        $unidades = $partes[1];
    } else {
        errorFinanzas("{$campo} debe ser un importe entero válido.");
    }
    $monto = number_format((int) $unidades, 2, '.', '');
    if ((!$permiteCero && montoCentavos($monto) <= 0) || ($permiteCero && montoCentavos($monto) < 0)) {
        errorFinanzas($permiteCero ? "{$campo} no puede ser negativa." : "{$campo} debe ser mayor que cero.");
    }
    return $monto;
}

function anioActualDb(mysqli $db): int
{
    return (int) $db->query('SELECT YEAR(CURDATE()) AS anio')->fetch_assoc()['anio'];
}

function fechaActualDb(mysqli $db): string
{
    return (string) $db->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-%d') AS hoy")->fetch_assoc()['hoy'];
}

function montoCentavos($valor): int
{
    return (int) round(((float) $valor) * 100);
}

function enteroPositivo($valor, string $mensaje): int
{
    $id = (int) $valor;
    if ($id <= 0) {
        errorFinanzas($mensaje);
    }
    return $id;
}

function textoNullable($valor, bool $mayusculas = false): ?string
{
    $texto = trim((string) ($valor ?? ''));
    if ($texto === '') {
        return null;
    }
    return $mayusculas ? mb_strtoupper($texto, 'UTF-8') : $texto;
}

function errorFinanzas(string $mensaje): void
{
    responderJson(['status' => 'error', 'mensaje' => $mensaje], 400);
}
