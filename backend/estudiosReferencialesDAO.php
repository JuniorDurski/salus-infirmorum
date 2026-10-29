<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'listar';
$identidad = $q === 'listar'
    ? requerirAlgunPermiso($conexion, ['postulantes_estudios.ver', 'postulantes_estudios.editar', 'postulantes_atencion.asignar'], true)
    : requerirAutenticacion($conexion, 'postulantes_estudios.editar', true);
if ($q !== 'listar') validarCsrf();

try {
    if ($q === 'listar') {
        $incluirInactivos = tienePermiso($identidad, 'postulantes_estudios.editar') && ($_GET['todos'] ?? '') === '1';
        $sql = 'SELECT id_estudio, estudio_referencial, activo, creado_en, actualizado_en
                FROM postulantes_estudios_referenciales' . ($incluirInactivos ? '' : ' WHERE activo = 1') .
               ' ORDER BY estudio_referencial';
        responderJson(['status' => 'ok', 'estudios' => $conexion->query($sql)->fetch_all(MYSQLI_ASSOC)]);
    }

    if ($q === 'guardar') {
        $id = (int) ($_POST['id_estudio'] ?? 0);
        $nombre = mb_strtoupper(trim((string) ($_POST['estudio_referencial'] ?? '')), 'UTF-8');
        if ($nombre === '' || mb_strlen($nombre) > 180) responderJson(['status' => 'error', 'mensaje' => 'Ingrese un estudio válido.'], 400);
        if ($id > 0) {
            $stmt = $conexion->prepare('UPDATE postulantes_estudios_referenciales SET estudio_referencial = ? WHERE id_estudio = ?');
            $stmt->bind_param('si', $nombre, $id);
        } else {
            $stmt = $conexion->prepare('INSERT INTO postulantes_estudios_referenciales (estudio_referencial) VALUES (?)');
            $stmt->bind_param('s', $nombre);
        }
        $stmt->execute();
        responderJson(['status' => 'ok', 'mensaje' => 'Estudio guardado.', 'id_estudio' => $id ?: $conexion->insert_id]);
    }

    if ($q === 'estado') {
        $id = (int) ($_POST['id_estudio'] ?? 0);
        $activo = ($_POST['activo'] ?? '0') === '1' ? 1 : 0;
        if ($id <= 0) responderJson(['status' => 'error', 'mensaje' => 'Estudio no válido.'], 400);
        $stmt = $conexion->prepare('UPDATE postulantes_estudios_referenciales SET activo = ? WHERE id_estudio = ?');
        $stmt->bind_param('ii', $activo, $id);
        $stmt->execute();
        responderJson(['status' => 'ok', 'mensaje' => $activo ? 'Estudio activado.' : 'Estudio desactivado.']);
    }
    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    responderJson(['status' => 'error', 'mensaje' => $e->getCode() === 1062 ? 'Ya existe un estudio con ese nombre.' : $e->getMessage()], 422);
}
