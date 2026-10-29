<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'listar';

try {
    if ($q === 'listar') {
        requerirAlgunPermiso($conexion, ['permisos.ver', 'roles.ver'], true);
        $rs = $conexion->query(
            'SELECT id_permiso, codigo, nombre, descripcion, activo
             FROM permisos ORDER BY codigo'
        );
        responderJson($rs->fetch_all(MYSQLI_ASSOC));
    }

    if ($q === 'guardar') {
        $identidad = requerirAutenticacion($conexion, 'permisos.editar', true);
        validarCsrf();
        guardarPermiso($conexion, $identidad);
    }

    if ($q === 'cambiar_estado') {
        requerirAutenticacion($conexion, 'permisos.editar', true);
        validarCsrf();
        cambiarEstadoPermiso($conexion);
    }

    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo completar la operación.'], 422);
}

function guardarPermiso(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_permiso'] ?? 0);
    $codigo = strtolower(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;

    if (!preg_match('/^[a-z0-9_.-]{3,100}$/', $codigo) || $nombre === '') {
        responderJson(['status' => 'error', 'mensaje' => 'Código y nombre del permiso no son válidos.'], 400);
    }

    $esNuevo = $id === 0;
    if ($id > 0) {
        $stmt = $db->prepare('SELECT codigo FROM permisos WHERE id_permiso = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        if ($actual === null) {
            responderJson(['status' => 'error', 'mensaje' => 'Permiso no encontrado.'], 404);
        }
        // El código es la interfaz estable usada por PHP y no cambia una vez creado.
        $codigo = $actual['codigo'];
        $stmt = $db->prepare(
            'UPDATE permisos SET nombre = ?, descripcion = ? WHERE id_permiso = ?'
        );
        $stmt->bind_param('ssi', $nombre, $descripcion, $id);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO permisos (codigo, nombre, descripcion) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('sss', $codigo, $nombre, $descripcion);
    }
    $stmt->execute();
    $id = $id ?: $db->insert_id;

    if ($esNuevo) {
        $actorId = (int) $identidad['id_usuario'];
        $stmt = $db->prepare(
            "INSERT IGNORE INTO rol_permisos (id_rol, id_permiso, asignado_por)
             SELECT id_rol, ?, ? FROM roles WHERE codigo = 'ADMIN'"
        );
        $stmt->bind_param('ii', $id, $actorId);
        $stmt->execute();
    }

    responderJson(['status' => 'ok', 'id_permiso' => $id]);
}

function cambiarEstadoPermiso(mysqli $db): void
{
    $id = (int) ($_POST['id_permiso'] ?? 0);
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $stmt = $db->prepare('SELECT codigo FROM permisos WHERE id_permiso = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $permiso = $stmt->get_result()->fetch_assoc();
    if ($permiso === null) {
        responderJson(['status' => 'error', 'mensaje' => 'Permiso no encontrado.'], 404);
    }
    if ($permiso['codigo'] === 'seguridad.administrar' && $activo === 0) {
        responderJson(['status' => 'error', 'mensaje' => 'El permiso principal de seguridad no puede desactivarse.'], 400);
    }
    $stmt = $db->prepare('UPDATE permisos SET activo = ? WHERE id_permiso = ?');
    $stmt->bind_param('ii', $activo, $id);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}
