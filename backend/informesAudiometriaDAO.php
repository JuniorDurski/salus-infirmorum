<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/informes_audiometria_service.php';

$identidad = requerirAutenticacion($conexion, 'reportes.audiometria.imprimir', true);
$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'detalle':
            obtenerDetalleAudiometria($conexion);
            break;
        case 'estados':
            listarEstadosAudiometria($conexion);
            break;
        case 'guardar':
            if (!tienePermiso($identidad, 'reportes.audiometria.editar')) {
                responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para editar informes de audiometría.'], 403);
            }
            validarCsrf();
            guardarDxAudiometria($conexion, $identidad);
            break;
        default:
            responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo guardar el informe de audiometría.'], 422);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo procesar el informe de audiometría.'], 500);
}

function obtenerDetalleAudiometria(mysqli $db): void
{
    $id = (int) ($_GET['id_postulante'] ?? 0);
    exigirPostulanteActivoAudiometria($db, $id);
    $stmt = $db->prepare(
        'SELECT dx, actualizado_en FROM postulantes_informes_audiometria WHERE id_postulante = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    responderJson([
        'status' => 'ok',
        'guardado' => $fila !== null,
        'actualizado_en' => $fila['actualizado_en'] ?? null,
        'dx' => $fila['dx'] ?? DX_AUDIOMETRIA_PREDETERMINADO,
    ]);
}

/** Indica qué postulantes ya tienen un DX guardado. */
function listarEstadosAudiometria(mysqli $db): void
{
    $valores = $_GET['ids'] ?? [];
    if (!is_array($valores)) $valores = [$valores];
    $ids = array_slice(array_values(array_unique(array_filter(
        array_map('intval', $valores),
        static fn(int $id): bool => $id > 0
    ))), 0, 1000);
    if ($ids === []) responderJson(['status' => 'ok', 'guardados' => []]);

    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id_postulante FROM postulantes_informes_audiometria WHERE id_postulante IN ({$marcas})");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $guardados = array_map(
        static fn(array $fila): int => (int) $fila['id_postulante'],
        $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    );
    responderJson(['status' => 'ok', 'guardados' => $guardados]);
}

function guardarDxAudiometria(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_postulante'] ?? 0);
    exigirPostulanteActivoAudiometria($db, $id);
    $dx = normalizarDxAudiometria((string) ($_POST['dx'] ?? ''));
    if ($dx === '') {
        responderJson(['status' => 'error', 'mensaje' => 'Escriba el DX antes de guardar.'], 400);
    }
    if (mb_strlen($dx) > 2000) {
        responderJson(['status' => 'error', 'mensaje' => 'El DX no puede superar los 2000 caracteres.'], 400);
    }

    $actor = (int) $identidad['id_usuario'];
    $stmt = $db->prepare(
        'INSERT INTO postulantes_informes_audiometria (id_postulante, dx, actualizado_por)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            dx = VALUES(dx),
            actualizado_por = VALUES(actualizado_por),
            actualizado_en = CURRENT_TIMESTAMP'
    );
    $stmt->bind_param('isi', $id, $dx, $actor);
    $stmt->execute();
    responderJson(['status' => 'ok', 'mensaje' => 'DX de audiometría guardado.']);
}

function exigirPostulanteActivoAudiometria(mysqli $db, int $id): void
{
    if ($id <= 0) responderJson(['status' => 'error', 'mensaje' => 'Postulante no válido.'], 400);
    $stmt = $db->prepare('SELECT 1 FROM pacientes WHERE id_paciente = ? AND activo = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row() === null) {
        responderJson(['status' => 'error', 'mensaje' => 'No se encontró el postulante activo.'], 404);
    }
}
