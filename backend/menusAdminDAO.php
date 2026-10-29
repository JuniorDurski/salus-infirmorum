<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? 'listar';
try {
    if ($q === 'listar') {
        requerirAutenticacion($conexion, 'menus.ver', true);
        listarMenusAdministrables($conexion);
    }
    if ($q === 'guardar') {
        $identidad = requerirAutenticacion($conexion, 'menus.editar', true);
        validarCsrf();
        guardarMenu($conexion, $identidad);
    }
    responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $mensaje = $e->getCode() === 1062 ? 'Ya existe un menú o permiso con esa ruta.' : 'No se pudo completar la operación.';
    responderJson(['status' => 'error', 'mensaje' => $mensaje], 422);
} catch (InvalidArgumentException $e) {
    responderJson(['status' => 'error', 'mensaje' => $e->getMessage()], 400);
}

function listarMenusAdministrables(mysqli $db): void
{
    $rs = $db->query(
        "SELECT m.id_menu, m.id_menu_padre, m.codigo, m.etiqueta, m.url, m.icono,
                m.orden, m.visible_todos, m.activo, m.id_permiso,
                padre.etiqueta AS padre_etiqueta,
                p.codigo AS permiso_codigo, p.activo AS permiso_activo,
                COALESCE(GROUP_CONCAT(DISTINCT rp.id_rol ORDER BY rp.id_rol), '') AS rol_ids
         FROM menus m
         LEFT JOIN menus padre ON padre.id_menu = m.id_menu_padre
         LEFT JOIN permisos p ON p.id_permiso = m.id_permiso
         LEFT JOIN rol_permisos rp ON rp.id_permiso = m.id_permiso
         GROUP BY m.id_menu, m.id_menu_padre, m.codigo, m.etiqueta, m.url, m.icono,
                  m.orden, m.visible_todos, m.activo, m.id_permiso,
                  padre.etiqueta, p.codigo, p.activo
         ORDER BY m.codigo"
    );
    $menus = $rs->fetch_all(MYSQLI_ASSOC);
    foreach ($menus as &$menu) {
        $partes = explode('.', $menu['codigo']);
        $menu['segmento'] = end($partes);
        $menu['rol_ids'] = $menu['rol_ids'] === '' ? [] : array_map('intval', explode(',', $menu['rol_ids']));
    }
    unset($menu);
    $roles = $db->query('SELECT id_rol, codigo, nombre, activo FROM roles ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
    responderJson(['menus' => $menus, 'roles' => $roles]);
}

function guardarMenu(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_menu'] ?? 0);
    $idPadre = (int) ($_POST['id_menu_padre'] ?? 0);
    $idPadre = $idPadre > 0 ? $idPadre : null;
    $segmento = strtolower(trim((string) ($_POST['segmento'] ?? '')));
    $etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
    $url = validarUrlMenu(trim((string) ($_POST['url'] ?? '')));
    $icono = trim((string) ($_POST['icono'] ?? '')) ?: null;
    $orden = max(0, min(65535, (int) ($_POST['orden'] ?? 0)));
    $visibleTodos = !empty($_POST['visible_todos']) ? 1 : 0;
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $roles = array_values(array_unique(array_filter(array_map('intval', $_POST['roles'] ?? []))));

    if (!preg_match('/^[a-z0-9_-]{2,40}$/', $segmento)) {
        throw new InvalidArgumentException('El segmento admite letras minúsculas, números, guion y guion bajo.');
    }
    if ($etiqueta === '' || mb_strlen($etiqueta) > 100) {
        throw new InvalidArgumentException('La etiqueta es obligatoria y admite hasta 100 caracteres.');
    }
    if ($id > 0 && $idPadre === $id) {
        throw new InvalidArgumentException('Un menú no puede ser su propio padre.');
    }

    $menuActual = $id > 0 ? buscarMenu($db, $id) : null;
    if ($id > 0 && $menuActual === null) throw new InvalidArgumentException('Menú no encontrado.');
    $codigoPadre = '';
    if ($idPadre !== null) {
        $padre = buscarMenu($db, $idPadre);
        if ($padre === null) throw new InvalidArgumentException('El menú padre no existe.');
        if ($id > 0 && esDescendienteDe($db, $idPadre, $id)) {
            throw new InvalidArgumentException('No puede mover un menú dentro de uno de sus descendientes.');
        }
        $codigoPadre = $padre['codigo'] . '.';
    }
    $codigo = $codigoPadre . $segmento;
    if (mb_strlen($codigo) > 80) throw new InvalidArgumentException('La ruta jerárquica supera los 80 caracteres.');
    $codigoPermiso = 'menu.' . $codigo;
    $actorId = (int) $identidad['id_usuario'];

    $db->query('SET @app_usuario_id = ' . $actorId);
    $db->begin_transaction();
    try {
        if ($menuActual !== null) {
            $idPermiso = (int) $menuActual['id_permiso'];
            if ($idPermiso > 0) {
                // El estado global se administra exclusivamente desde el módulo Permisos.
                $stmt = $db->prepare('UPDATE permisos SET codigo = ?, nombre = ?, descripcion = ? WHERE id_permiso = ?');
                $nombrePermiso = 'Ver menú ' . $etiqueta;
                $descripcion = 'Permite visualizar el menú ' . $codigo;
                $stmt->bind_param('sssi', $codigoPermiso, $nombrePermiso, $descripcion, $idPermiso);
                $stmt->execute();
            } else {
                $idPermiso = crearPermisoMenu($db, $codigoPermiso, $etiqueta);
            }
            $stmt = $db->prepare(
                'UPDATE menus SET id_menu_padre = ?, id_permiso = ?, codigo = ?, etiqueta = ?, url = ?, icono = ?, orden = ?, visible_todos = ?, activo = ? WHERE id_menu = ?'
            );
            $stmt->bind_param('iissssiiii', $idPadre, $idPermiso, $codigo, $etiqueta, $url, $icono, $orden, $visibleTodos, $activo, $id);
            $stmt->execute();
            actualizarDescendientes($db, $id, $codigo);
        } else {
            $idPermiso = crearPermisoMenu($db, $codigoPermiso, $etiqueta);
            $stmt = $db->prepare(
                'INSERT INTO menus (id_menu_padre, id_permiso, codigo, etiqueta, url, icono, orden, visible_todos, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('iissssiii', $idPadre, $idPermiso, $codigo, $etiqueta, $url, $icono, $orden, $visibleTodos, $activo);
            $stmt->execute();
            $id = $db->insert_id;
        }
        asignarRolesMenu($db, $idPermiso, $roles, $actorId);
        $db->commit();
        responderJson(['status' => 'ok', 'id_menu' => $id, 'codigo' => $codigo, 'permiso' => $codigoPermiso, 'mensaje' => 'Menú guardado correctamente.']);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function validarUrlMenu(string $url): ?string
{
    if ($url === '') return null;
    if (mb_strlen($url) > 255 || str_contains($url, ':') || str_starts_with($url, '//')
        || !preg_match('#^[a-zA-Z0-9_./?=&%+\-\#]+$#', $url) || str_contains($url, '..')) {
        throw new InvalidArgumentException('La URL debe ser una ruta interna segura.');
    }
    return ltrim($url, '/');
}

function buscarMenu(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT id_menu, id_menu_padre, id_permiso, codigo FROM menus WHERE id_menu = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function esDescendienteDe(mysqli $db, int $posibleDescendiente, int $ancestro): bool
{
    $actual = $posibleDescendiente;
    $visitados = [];
    while ($actual > 0 && !isset($visitados[$actual])) {
        if ($actual === $ancestro) return true;
        $visitados[$actual] = true;
        $menu = buscarMenu($db, $actual);
        $actual = (int) ($menu['id_menu_padre'] ?? 0);
    }
    return false;
}

function crearPermisoMenu(mysqli $db, string $codigo, string $etiqueta): int
{
    $nombre = 'Ver menú ' . $etiqueta;
    $descripcion = 'Permite visualizar el menú ' . substr($codigo, 5);
    $stmt = $db->prepare('INSERT INTO permisos (codigo, nombre, descripcion, activo) VALUES (?, ?, ?, 1)');
    $stmt->bind_param('sss', $codigo, $nombre, $descripcion);
    $stmt->execute();
    return $db->insert_id;
}

function actualizarDescendientes(mysqli $db, int $idPadre, string $codigoPadre): void
{
    $stmt = $db->prepare('SELECT id_menu, id_permiso, codigo FROM menus WHERE id_menu_padre = ? ORDER BY id_menu');
    $stmt->bind_param('i', $idPadre);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $hijo) {
        $partes = explode('.', $hijo['codigo']);
        $codigo = $codigoPadre . '.' . end($partes);
        if (mb_strlen($codigo) > 80) throw new InvalidArgumentException('La ruta de un submenú supera los 80 caracteres.');
        $codigoPermiso = 'menu.' . $codigo;
        $descripcion = 'Permite visualizar el menú ' . $codigo;
        $idPermiso = (int) $hijo['id_permiso'];
        $stmtPermiso = $db->prepare('UPDATE permisos SET codigo = ?, descripcion = ? WHERE id_permiso = ?');
        $stmtPermiso->bind_param('ssi', $codigoPermiso, $descripcion, $idPermiso);
        $stmtPermiso->execute();
        $idHijo = (int) $hijo['id_menu'];
        $stmtMenu = $db->prepare('UPDATE menus SET codigo = ? WHERE id_menu = ?');
        $stmtMenu->bind_param('si', $codigo, $idHijo);
        $stmtMenu->execute();
        actualizarDescendientes($db, $idHijo, $codigo);
    }
}

function asignarRolesMenu(mysqli $db, int $idPermiso, array $roles, int $actorId): void
{
    $stmt = $db->prepare('DELETE FROM rol_permisos WHERE id_permiso = ?');
    $stmt->bind_param('i', $idPermiso);
    $stmt->execute();
    $admin = $db->query("SELECT id_rol FROM roles WHERE codigo = 'ADMIN' LIMIT 1")->fetch_assoc();
    if ($admin !== null) $roles[] = (int) $admin['id_rol'];
    $roles = array_values(array_unique($roles));
    $stmt = $db->prepare('INSERT INTO rol_permisos (id_rol, id_permiso, asignado_por) VALUES (?, ?, ?)');
    foreach ($roles as $idRol) {
        $stmt->bind_param('iii', $idRol, $idPermiso, $actorId);
        $stmt->execute();
    }
}
