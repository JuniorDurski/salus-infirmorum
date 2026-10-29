<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'listar';

try {
    if ($q === 'listar') {
        requerirAlgunPermiso($conexion, ['roles.ver', 'usuarios.ver'], true);
        listarRoles($conexion);
    }

    if ($q === 'guardar') {
        $identidad = requerirAutenticacion($conexion, 'roles.editar', true);
        validarCsrf();
        guardarRol($conexion, $identidad);
    }

    if ($q === 'cambiar_estado') {
        requerirAutenticacion($conexion, 'roles.editar', true);
        validarCsrf();
        cambiarEstadoRol($conexion);
    }

    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    responderJson(['status' => 'error', 'mensaje' => 'No se pudo completar la operación.'], 422);
}

function listarRoles(mysqli $db): void
{
    $rs = $db->query(
        "SELECT r.id_rol, r.codigo, r.nombre, r.descripcion, r.activo,
                COALESCE(GROUP_CONCAT(DISTINCT p.id_permiso ORDER BY p.nombre), '') AS permiso_ids,
                COALESCE(GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', '), '') AS permisos
         FROM roles r
         LEFT JOIN rol_permisos rp ON rp.id_rol = r.id_rol
         LEFT JOIN permisos p ON p.id_permiso = rp.id_permiso
         GROUP BY r.id_rol, r.codigo, r.nombre, r.descripcion, r.activo
         ORDER BY r.nombre"
    );
    $datos = $rs->fetch_all(MYSQLI_ASSOC);
    foreach ($datos as &$fila) {
        $fila['permiso_ids'] = $fila['permiso_ids'] === ''
            ? []
            : array_map('intval', explode(',', $fila['permiso_ids']));
    }
    unset($fila);
    responderJson($datos);
}

function guardarRol(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_rol'] ?? 0);
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $permisos = array_values(array_unique(array_filter(array_map('intval', $_POST['permisos'] ?? []))));

    if (!preg_match('/^[A-Z0-9_.-]{3,60}$/', $codigo) || $nombre === '') {
        responderJson(['status' => 'error', 'mensaje' => 'Código y nombre del rol no son válidos.'], 400);
    }

    if ($id > 0) {
        $stmt = $db->prepare('SELECT codigo FROM roles WHERE id_rol = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        if ($actual === null) {
            responderJson(['status' => 'error', 'mensaje' => 'Rol no encontrado.'], 404);
        }
        if ($actual['codigo'] === 'ADMIN') {
            $codigo = 'ADMIN';
            $permisos = todosLosPermisosActivos($db);
        }
    }

    $db->begin_transaction();
    try {
        if ($id > 0) {
            $stmt = $db->prepare('UPDATE roles SET codigo = ?, nombre = ?, descripcion = ? WHERE id_rol = ?');
            $stmt->bind_param('sssi', $codigo, $nombre, $descripcion, $id);
            $stmt->execute();
        } else {
            $stmt = $db->prepare('INSERT INTO roles (codigo, nombre, descripcion) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $codigo, $nombre, $descripcion);
            $stmt->execute();
            $id = $db->insert_id;
        }

        $stmt = $db->prepare('DELETE FROM rol_permisos WHERE id_rol = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $actorId = (int) $identidad['id_usuario'];
        $stmtPermiso = $db->prepare(
            'INSERT INTO rol_permisos (id_rol, id_permiso, asignado_por) VALUES (?, ?, ?)'
        );
        foreach ($permisos as $idPermiso) {
            $stmtPermiso->bind_param('iii', $id, $idPermiso, $actorId);
            $stmtPermiso->execute();
        }

        $db->commit();
        responderJson(['status' => 'ok', 'id_rol' => $id]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function todosLosPermisosActivos(mysqli $db): array
{
    $rs = $db->query('SELECT id_permiso FROM permisos WHERE activo = 1');
    return array_map('intval', array_column($rs->fetch_all(MYSQLI_ASSOC), 'id_permiso'));
}

function cambiarEstadoRol(mysqli $db): void
{
    $id = (int) ($_POST['id_rol'] ?? 0);
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $stmt = $db->prepare('SELECT codigo FROM roles WHERE id_rol = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rol = $stmt->get_result()->fetch_assoc();
    if ($rol === null) {
        responderJson(['status' => 'error', 'mensaje' => 'Rol no encontrado.'], 404);
    }
    if ($rol['codigo'] === 'ADMIN' && $activo === 0) {
        responderJson(['status' => 'error', 'mensaje' => 'El rol ADMIN no puede desactivarse.'], 400);
    }
    $stmt = $db->prepare('UPDATE roles SET activo = ? WHERE id_rol = ?');
    $stmt->bind_param('ii', $activo, $id);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}
