<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$idUsuario = isset($_SESSION['usuario']['id_usuario'])
    ? (int) $_SESSION['usuario']['id_usuario']
    : 0;

$sql = "SELECT DISTINCT
            m.id_menu, m.id_menu_padre, m.codigo, m.etiqueta,
            m.url, m.icono, m.orden
        FROM menus m
        WHERE m.activo = 1
          AND (
              m.visible_todos = 1
              OR EXISTS (
                  SELECT 1
                  FROM usuario_rol ur
                  INNER JOIN rol_permisos rp ON rp.id_rol = ur.id_rol
                  INNER JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
                  INNER JOIN permisos p ON p.id_permiso = rp.id_permiso AND p.activo = 1
                  WHERE ur.id_usuario = ?
                    AND rp.id_permiso = m.id_permiso
              )
          )
        ORDER BY COALESCE(m.id_menu_padre, m.id_menu), m.id_menu_padre, m.orden, m.etiqueta";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$porId = [];
$raices = [];

foreach ($filas as $fila) {
    $fila['hijos'] = [];
    $porId[(int) $fila['id_menu']] = $fila;
}

foreach ($porId as $id => &$menu) {
    $padre = (int) ($menu['id_menu_padre'] ?? 0);
    if ($padre !== 0 && isset($porId[$padre])) {
        $porId[$padre]['hijos'][] = &$menu;
    } else {
        $raices[] = &$menu;
    }
}
unset($menu);

echo json_encode($raices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
