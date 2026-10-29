<?php

function usuarioSesionId(): ?int
{
    return isset($_SESSION['usuario']['id_usuario'])
        ? (int) $_SESSION['usuario']['id_usuario']
        : null;
}

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validarCsrf(): void
{
    $recibido = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($recibido === '' || !hash_equals(tokenCsrf(), $recibido)) {
        responderJson(['status' => 'error', 'mensaje' => 'La sesión del formulario expiró. Recargue la página.'], 419);
    }
}

function cargarIdentidad(mysqli $db, int $idUsuario): ?array
{
    $stmt = $db->prepare(
        "SELECT u.id_usuario, u.usuario, u.nombre, u.ci, u.correo,
                u.clave_predeterminada, u.version_sesion, u.activo,
                COALESCE(GROUP_CONCAT(DISTINCT r.codigo ORDER BY r.codigo SEPARATOR ','), '') AS roles,
                COALESCE(GROUP_CONCAT(DISTINCT p.codigo ORDER BY p.codigo SEPARATOR ','), '') AS permisos
         FROM usuarios u
         LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         LEFT JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
         LEFT JOIN rol_permisos rp ON rp.id_rol = r.id_rol
         LEFT JOIN permisos p ON p.id_permiso = rp.id_permiso AND p.activo = 1
         WHERE u.id_usuario = ?
         GROUP BY u.id_usuario, u.usuario, u.nombre, u.ci, u.correo,
                  u.clave_predeterminada, u.version_sesion, u.activo
         LIMIT 1"
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();

    if ($fila === null || (int) $fila['activo'] !== 1) {
        return null;
    }

    $fila['id_usuario'] = (int) $fila['id_usuario'];
    $fila['clave_predeterminada'] = (int) $fila['clave_predeterminada'];
    $fila['version_sesion'] = (int) $fila['version_sesion'];
    $fila['roles'] = $fila['roles'] === '' ? [] : explode(',', $fila['roles']);
    $fila['permisos'] = $fila['permisos'] === '' ? [] : explode(',', $fila['permisos']);
    unset($fila['activo']);

    return $fila;
}

function identidadActual(mysqli $db): ?array
{
    $idUsuario = usuarioSesionId();
    if ($idUsuario === null) {
        return null;
    }

    $identidad = cargarIdentidad($db, $idUsuario);
    $versionGuardada = (int) ($_SESSION['usuario']['version_sesion'] ?? 0);

    if ($identidad === null || $identidad['version_sesion'] !== $versionGuardada) {
        cerrarSesionSegura();
        return null;
    }

    $huellaActual = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'cli');
    if (!empty($_SESSION['huella_agente']) && !hash_equals($_SESSION['huella_agente'], $huellaActual)) {
        cerrarSesionSegura();
        return null;
    }

    $_SESSION['usuario'] = $identidad;

    return $identidad;
}

function tienePermiso(array $identidad, string $permiso): bool
{
    return in_array('ADMIN', $identidad['roles'], true)
        || in_array($permiso, $identidad['permisos'], true);
}

function requerirAutenticacion(
    mysqli $db,
    ?string $permiso = null,
    bool $api = false,
    bool $permitirClavePredeterminada = false
): array {
    $identidad = identidadActual($db);

    if ($identidad === null) {
        if ($api) {
            responderJson(['status' => 'error', 'mensaje' => 'Debe iniciar sesión.'], 401);
        }

        $retorno = rawurlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        header('Location: ' . rutaBaseAplicacion() . 'login.php?retorno=' . $retorno);
        exit;
    }

    if (!$permitirClavePredeterminada && $identidad['clave_predeterminada'] === 1) {
        if ($api) {
            responderJson([
                'status' => 'error',
                'codigo' => 'CAMBIO_CLAVE_REQUERIDO',
                'mensaje' => 'Debe cambiar la contraseña temporal.',
            ], 403);
        }

        header('Location: ' . rutaBaseAplicacion() . 'cambiar_clave.php');
        exit;
    }

    if ($permiso !== null && !tienePermiso($identidad, $permiso)) {
        if ($api) {
            responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para esta operación.'], 403);
        }

        http_response_code(403);
        exit('Acceso denegado');
    }

    return $identidad;
}

function rutaBaseAplicacion(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    foreach (['/backend/', '/reportes/'] as $segmento) {
        $posicion = strpos($script, $segmento);
        if ($posicion !== false) {
            return rtrim(substr($script, 0, $posicion), '/') . '/';
        }
    }

    $directorio = str_replace('\\', '/', dirname($script));
    return rtrim($directorio === '.' ? '/' : $directorio, '/') . '/';
}

function requerirAlgunPermiso(mysqli $db, array $permisos, bool $api = false): array
{
    $identidad = requerirAutenticacion($db, null, $api);
    foreach ($permisos as $permiso) {
        if (tienePermiso($identidad, $permiso)) {
            return $identidad;
        }
    }

    if ($api) {
        responderJson(['status' => 'error', 'mensaje' => 'No tiene permiso para esta operación.'], 403);
    }
    http_response_code(403);
    exit('Acceso denegado');
}

function cerrarSesionSegura(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parametros['path'],
            $parametros['domain'],
            (bool) $parametros['secure'],
            (bool) $parametros['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function iniciarIdentidadSesion(array $identidad): void
{
    session_regenerate_id(true);
    $_SESSION = [
        'usuario' => $identidad,
        'huella_agente' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'cli'),
        'ultima_actividad' => time(),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
}

function responderJson(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
