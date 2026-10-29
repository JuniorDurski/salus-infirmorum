<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'consultar';
if ($q !== 'consultar') responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);

$ahora = time();
$intentos = array_values(array_filter($_SESSION['consulta_publica_intentos'] ?? [], static fn($marca) => $marca > $ahora - 60));
if (count($intentos) >= 20) responderJson(['status' => 'error', 'mensaje' => 'Demasiadas consultas. Intente nuevamente en un minuto.'], 429);
$intentos[] = $ahora;
$_SESSION['consulta_publica_intentos'] = $intentos;

$documento = mb_strtoupper(trim((string) ($_POST['documento'] ?? '')), 'UTF-8');
$fecha = trim((string) ($_POST['fecha'] ?? ''));
$fechaValida = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
if ($documento === '' || mb_strlen($documento) > 30
    || $fechaValida === false || $fechaValida->format('Y-m-d') !== $fecha) {
    responderJson(['status' => 'error', 'mensaje' => 'Ingrese su documento y una fecha válida.'], 400);
}
$stmt = $conexion->prepare(
    'SELECT p.id_paciente, p.nombre, p.documento, e.nombre AS escuela
     FROM postulantes_atencion pa
     INNER JOIN postulantes_asignar_atencion a
             ON a.id_asignar_atencion = pa.id_asignar_atencion AND a.activo = 1
     INNER JOIN pacientes p ON p.id_paciente = pa.id_paciente AND p.activo = 1
     INNER JOIN escuelas e ON e.id_escuela = p.id_escuela
     WHERE p.documento = ? AND a.fecha = ?
     ORDER BY p.id_paciente
     LIMIT 1'
);
$stmt->bind_param('ss', $documento, $fecha); $stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
if (!$paciente) {
    responderJson(['status' => 'error', 'mensaje' => 'No se encontró una asignación activa para ese documento en la fecha seleccionada.'], 404);
}

$idPaciente = (int) $paciente['id_paciente'];
$stmt = $conexion->prepare(
    "SELECT er.id_estudio, er.estudio_referencial,
            MAX(pae.realizado) AS realizado,
            MAX(a.fecha) AS fecha_asignada
     FROM postulantes_atencion pa
     INNER JOIN postulantes_asignar_atencion a ON a.id_asignar_atencion = pa.id_asignar_atencion
     INNER JOIN postulantes_atencion_estudios pae ON pae.id_postulante_atencion = pa.id_postulante_atencion
     INNER JOIN postulantes_estudios_referenciales er ON er.id_estudio = pae.id_estudio
     WHERE pa.id_paciente = ? AND a.fecha = ? AND a.activo = 1
     GROUP BY er.id_estudio, er.estudio_referencial
     ORDER BY realizado ASC, er.estudio_referencial"
);
$stmt->bind_param('is', $idPaciente, $fecha); $stmt->execute();
$estudios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($estudios as &$estudio) {
    $estudio['id_estudio'] = (int) $estudio['id_estudio'];
    $estudio['realizado'] = (int) $estudio['realizado'];
}
unset($estudio, $paciente['id_paciente']);
responderJson(['status' => 'ok', 'fecha' => $fecha, 'paciente' => $paciente, 'estudios' => $estudios]);
