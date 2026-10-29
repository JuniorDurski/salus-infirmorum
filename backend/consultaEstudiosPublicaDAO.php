<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'escuelas';
if ($q === 'escuelas') {
    $rs = $conexion->query(
        "SELECT id_escuela, nombre,
                CONCAT(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.anio')), '-',
                       LPAD(JSON_UNQUOTE(JSON_EXTRACT(periodo, '$.mes')), 2, '0')) AS periodo
         FROM escuelas WHERE activo = 1 ORDER BY nombre, periodo DESC"
    );
    responderJson(['status' => 'ok', 'escuelas' => $rs->fetch_all(MYSQLI_ASSOC)]);
}
if ($q !== 'consultar') responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);

$ahora = time();
$intentos = array_values(array_filter($_SESSION['consulta_publica_intentos'] ?? [], static fn($marca) => $marca > $ahora - 60));
if (count($intentos) >= 20) responderJson(['status' => 'error', 'mensaje' => 'Demasiadas consultas. Intente nuevamente en un minuto.'], 429);
$intentos[] = $ahora;
$_SESSION['consulta_publica_intentos'] = $intentos;

$documento = mb_strtoupper(trim((string) ($_POST['documento'] ?? '')), 'UTF-8');
$idEscuela = (int) ($_POST['id_escuela'] ?? 0);
if ($documento === '' || mb_strlen($documento) > 30 || $idEscuela <= 0) {
    responderJson(['status' => 'error', 'mensaje' => 'Ingrese su documento y seleccione su escuela.'], 400);
}
$stmt = $conexion->prepare(
    'SELECT p.id_paciente, p.nombre, p.documento, e.nombre AS escuela
     FROM pacientes p INNER JOIN escuelas e ON e.id_escuela = p.id_escuela
     WHERE p.documento = ? AND p.id_escuela = ? AND p.activo = 1 LIMIT 1'
);
$stmt->bind_param('si', $documento, $idEscuela); $stmt->execute();
$paciente = $stmt->get_result()->fetch_assoc();
if (!$paciente) responderJson(['status' => 'error', 'mensaje' => 'No se encontró un postulante con esos datos.'], 404);

$idPaciente = (int) $paciente['id_paciente'];
$stmt = $conexion->prepare(
    "SELECT er.id_estudio, er.estudio_referencial,
            MAX(pae.realizado) AS realizado,
            MAX(a.fecha) AS fecha_asignada,
            MAX(pa.atendido) AS atendido
     FROM postulantes_atencion pa
     INNER JOIN postulantes_asignar_atencion a ON a.id_asignar_atencion = pa.id_asignar_atencion
     INNER JOIN postulantes_atencion_estudios pae ON pae.id_postulante_atencion = pa.id_postulante_atencion
     INNER JOIN postulantes_estudios_referenciales er ON er.id_estudio = pae.id_estudio
     WHERE pa.id_paciente = ?
     GROUP BY er.id_estudio, er.estudio_referencial
     ORDER BY er.estudio_referencial"
);
$stmt->bind_param('i', $idPaciente); $stmt->execute();
$estudios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($estudios as &$estudio) {
    $estudio['id_estudio'] = (int) $estudio['id_estudio'];
    $estudio['realizado'] = (int) $estudio['realizado'];
    $estudio['atendido'] = (int) $estudio['atendido'];
}
unset($estudio, $paciente['id_paciente']);
responderJson(['status' => 'ok', 'paciente' => $paciente, 'estudios' => $estudios]);
