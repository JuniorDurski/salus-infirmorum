<?php

function obtenerMenusVisibles(mysqli $db, int $idUsuario): array
{
    $stmt = $db->prepare(
        "SELECT m.id_menu, m.id_menu_padre, m.codigo, m.etiqueta, m.url,
                m.icono, m.orden, p.codigo AS permiso
         FROM menus m
         LEFT JOIN permisos p ON p.id_permiso = m.id_permiso
         WHERE m.activo = 1
           AND (m.id_permiso IS NULL OR p.activo = 1)
           AND (
                m.visible_todos = 1
                OR EXISTS (
                    SELECT 1
                    FROM usuario_rol ur
                    INNER JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
                    INNER JOIN rol_permisos rp
                        ON rp.id_rol = ur.id_rol AND rp.id_permiso = m.id_permiso
                    WHERE ur.id_usuario = ?
                )
           )
         ORDER BY m.orden, m.etiqueta, m.id_menu"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    return construirArbolMenus($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

function construirArbolMenus(array $filas): array
{
    $porPadre = [];
    foreach ($filas as $fila) {
        $fila['id_menu'] = (int) $fila['id_menu'];
        $fila['id_menu_padre'] = $fila['id_menu_padre'] === null ? null : (int) $fila['id_menu_padre'];
        $fila['orden'] = (int) $fila['orden'];
        $porPadre[$fila['id_menu_padre'] ?? 0][] = $fila;
    }
    $armar = static function (int $padre, array $visitados = []) use (&$armar, &$porPadre): array {
        $resultado = [];
        foreach ($porPadre[$padre] ?? [] as $fila) {
            $id = $fila['id_menu'];
            if (isset($visitados[$id])) continue;
            $rama = $visitados;
            $rama[$id] = true;
            $fila['hijos'] = $armar($id, $rama);
            $resultado[] = $fila;
        }
        return $resultado;
    };
    // Un hijo nunca se muestra si su padre no es visible para el usuario.
    return $armar(0);
}
