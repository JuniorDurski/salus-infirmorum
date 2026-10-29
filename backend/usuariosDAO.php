<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'login':
            validarCsrf();
            iniciarSesion($conexion);
            break;

        case 'logout':
            requerirAutenticacion($conexion, null, true, true);
            validarCsrf();
            cerrarSesionSegura();
            responderJson(['status' => 'ok']);
            break;

        case 'sesion':
            $identidad = requerirAutenticacion($conexion, null, true, true);
            responderJson(['status' => 'ok', 'usuario' => $identidad, 'csrf_token' => tokenCsrf()]);
            break;

        case 'cambiar_clave':
            $identidad = requerirAutenticacion($conexion, null, true, true);
            validarCsrf();
            cambiarClave($conexion, $identidad);
            break;

        case 'fuente_color':
            $identidad = requerirAutenticacion($conexion, null, true);
            obtenerTema($conexion, $identidad['id_usuario']);
            break;

        case 'cambiar_fuente':
            $identidad = requerirAutenticacion($conexion, null, true);
            validarCsrf();
            cambiarTema($conexion, $identidad['id_usuario']);
            break;

        case 'configuracion':
            $identidad = requerirAutenticacion($conexion, 'perfil.editar', true);
            obtenerConfiguracion($conexion, $identidad['id_usuario']);
            break;

        case 'guardar_configuracion':
            $identidad = requerirAutenticacion($conexion, 'perfil.editar', true);
            validarCsrf();
            guardarConfiguracion($conexion, $identidad['id_usuario']);
            break;

        case 'listar':
            requerirAutenticacion($conexion, 'usuarios.ver', true);
            listarUsuarios($conexion);
            break;

        case 'guardar':
            $identidad = requerirAutenticacion($conexion, 'usuarios.editar', true);
            validarCsrf();
            guardarUsuario($conexion, $identidad);
            break;

        case 'restablecer_clave':
            $identidad = requerirAutenticacion($conexion, 'usuarios.editar', true);
            validarCsrf();
            restablecerClave($conexion, $identidad);
            break;

        case 'eliminar':
            $identidad = requerirAutenticacion($conexion, 'usuarios.editar', true);
            validarCsrf();
            cambiarEstadoUsuario($conexion, $identidad, false);
            break;

        case 'activar':
            $identidad = requerirAutenticacion($conexion, 'usuarios.editar', true);
            validarCsrf();
            cambiarEstadoUsuario($conexion, $identidad, true);
            break;

        default:
            responderJson(['status' => 'error', 'mensaje' => 'Operación no válida.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $mensaje = $e->getCode() === 1062
        ? 'El usuario, CI o correo ya está registrado.'
        : 'No se pudo completar la operación.';
    responderJson(['status' => 'error', 'mensaje' => $mensaje], 422);
} catch (Throwable $e) {
    error_log($e->getMessage());
    responderJson(['status' => 'error', 'mensaje' => 'Ocurrió un error inesperado.'], 500);
}

function iniciarSesion(mysqli $db): void
{
    $identificador = trim($_POST['identificador'] ?? $_POST['ci'] ?? '');
    $clave = (string) ($_POST['clave'] ?? '');

    if ($identificador === '' || $clave === '') {
        responderJson(['status' => 'error', 'mensaje' => 'Ingrese usuario y contraseña.'], 400);
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM logueos
         WHERE identificador = ? AND exitoso = 0
           AND fecha >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->bind_param('s', $identificador);
    $stmt->execute();
    if ((int) $stmt->get_result()->fetch_row()[0] >= 5) {
        responderJson([
            'status' => 'error',
            'mensaje' => 'Demasiados intentos fallidos. Intente nuevamente en 15 minutos.',
        ], 429);
    }

    $stmt = $db->prepare(
        'SELECT id_usuario, clave_hash, activo
         FROM usuarios
         WHERE usuario = ? OR ci = ? OR correo = ?
         LIMIT 1'
    );
    $stmt->bind_param('sss', $identificador, $identificador, $identificador);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $esActivo = $fila !== null && (int) $fila['activo'] === 1;
    $exitoso = $esActivo && password_verify($clave, $fila['clave_hash']);
    $idUsuario = $fila === null ? null : (int) $fila['id_usuario'];

    registrarLogueo(
        $db,
        $idUsuario,
        $identificador,
        $exitoso,
        $exitoso ? null : ($fila !== null && !$esActivo ? 'Usuario inactivo' : 'Credenciales inválidas')
    );

    if (!$exitoso) {
        responderJson(['status' => 'error', 'mensaje' => 'Credenciales inválidas.'], 401);
    }

    if (password_needs_rehash($fila['clave_hash'], PASSWORD_DEFAULT)) {
        $nuevoHash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE usuarios SET clave_hash = ? WHERE id_usuario = ?');
        $stmt->bind_param('si', $nuevoHash, $idUsuario);
        $stmt->execute();
    }

    $stmt = $db->prepare('UPDATE usuarios SET ultimo_login_en = NOW() WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();

    $identidad = cargarIdentidad($db, $idUsuario);
    if ($identidad === null) {
        responderJson(['status' => 'error', 'mensaje' => 'No se pudo cargar la identidad del usuario.'], 401);
    }

    iniciarIdentidadSesion($identidad);
    $db->query('SET @app_usuario_id = ' . $idUsuario);

    responderJson([
        'status' => 'ok',
        'requiere_cambio_clave' => $identidad['clave_predeterminada'] === 1,
        'redirect' => $identidad['clave_predeterminada'] === 1 ? 'cambiar_clave.php' : 'index.php',
    ]);
}

function registrarLogueo(
    mysqli $db,
    ?int $idUsuario,
    string $identificador,
    bool $exitoso,
    ?string $motivo
): void {
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45) ?: null;
    $agente = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null;
    $estado = $exitoso ? 1 : 0;
    $stmt = $db->prepare(
        'INSERT INTO logueos
            (id_usuario, identificador, exitoso, ip, agente_usuario, motivo_fallo)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isisss', $idUsuario, $identificador, $estado, $ip, $agente, $motivo);
    $stmt->execute();
}

function cambiarClave(mysqli $db, array $identidad): void
{
    $actual = (string) ($_POST['clave_actual'] ?? '');
    $nueva = (string) ($_POST['clave_nueva'] ?? '');
    $confirmacion = (string) ($_POST['clave_confirmacion'] ?? '');

    if ($nueva !== $confirmacion) {
        responderJson(['status' => 'error', 'mensaje' => 'La confirmación no coincide.'], 400);
    }
    validarFortalezaClave($nueva);

    $stmt = $db->prepare('SELECT clave_hash FROM usuarios WHERE id_usuario = ? AND activo = 1');
    $stmt->bind_param('i', $identidad['id_usuario']);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();

    if ($fila === null || !password_verify($actual, $fila['clave_hash'])) {
        responderJson(['status' => 'error', 'mensaje' => 'La contraseña actual no es correcta.'], 400);
    }
    if (password_verify($nueva, $fila['clave_hash'])) {
        responderJson(['status' => 'error', 'mensaje' => 'La nueva contraseña debe ser diferente.'], 400);
    }

    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        'UPDATE usuarios
         SET clave_hash = ?, clave_predeterminada = 0, version_sesion = version_sesion + 1
         WHERE id_usuario = ?'
    );
    $stmt->bind_param('si', $hash, $identidad['id_usuario']);
    $stmt->execute();

    $identidadNueva = cargarIdentidad($db, $identidad['id_usuario']);
    iniciarIdentidadSesion($identidadNueva);
    responderJson(['status' => 'ok', 'redirect' => 'index.php']);
}

function validarFortalezaClave(string $clave): void
{
    if ($clave === '') {
        responderJson([
            'status' => 'error',
            'mensaje' => 'La contraseña no puede estar vacía.',
        ], 400);
    }
}

function obtenerTema(mysqli $db, int $idUsuario): void
{
    $stmt = $db->prepare(
        'SELECT modo_oscuro, IF(modo_oscuro = 1, 0, 1) AS fuente_claro
         FROM usuario_configuraciones WHERE id_usuario = ?'
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    responderJson($stmt->get_result()->fetch_assoc() ?: ['fuente_claro' => 1, 'modo_oscuro' => 0]);
}

function cambiarTema(mysqli $db, int $idUsuario): void
{
    $stmt = $db->prepare(
        'INSERT INTO usuario_configuraciones (id_usuario, modo_oscuro)
         VALUES (?, 1)
         ON DUPLICATE KEY UPDATE modo_oscuro = IF(modo_oscuro = 1, 0, 1)'
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function obtenerConfiguracion(mysqli $db, int $idUsuario): void
{
    $stmt = $db->prepare(
        'SELECT c.id_usuario, c.id_titulo, c.id_universidad, c.numero_registro,
                c.url_firma, c.url_firma_sello, c.url_sello, c.modo_oscuro,
                t.titulo, t.abreviatura AS titulo_abreviatura, t.es_femenino,
                u.nombre AS universidad, u.abreviatura AS universidad_abreviatura
         FROM usuario_configuraciones c
         LEFT JOIN titulos t ON t.id_titulo = c.id_titulo
         LEFT JOIN universidades u ON u.id_universidad = c.id_universidad
         WHERE c.id_usuario = ?'
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    responderJson($stmt->get_result()->fetch_assoc() ?: []);
}

function guardarConfiguracion(mysqli $db, int $idUsuario): void
{
    $idTitulo = ($_POST['id_titulo'] ?? '') !== '' ? (int) $_POST['id_titulo'] : null;
    $idUniversidad = ($_POST['id_universidad'] ?? '') !== '' ? (int) $_POST['id_universidad'] : null;
    $registro = textoNullable($_POST['numero_registro'] ?? null);
    $modoOscuro = !empty($_POST['modo_oscuro']) ? 1 : 0;
    $stmt = $db->prepare(
        'INSERT INTO usuario_configuraciones
            (id_usuario, id_titulo, id_universidad, numero_registro, modo_oscuro)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            id_titulo = VALUES(id_titulo),
            id_universidad = VALUES(id_universidad),
            numero_registro = VALUES(numero_registro),
            modo_oscuro = VALUES(modo_oscuro)'
    );
    $stmt->bind_param('iiisi', $idUsuario, $idTitulo, $idUniversidad, $registro, $modoOscuro);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function listarUsuarios(mysqli $db): void
{
    $rs = $db->query(
        "SELECT u.id_usuario, u.usuario, u.nombre, u.ci, u.correo,
                u.clave_predeterminada, u.activo, u.ultimo_login_en,
                COALESCE(GROUP_CONCAT(DISTINCT r.id_rol ORDER BY r.nombre), '') AS rol_ids,
                COALESCE(GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre SEPARATOR ', '), '') AS roles
         FROM usuarios u
         LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         LEFT JOIN roles r ON r.id_rol = ur.id_rol
         GROUP BY u.id_usuario, u.usuario, u.nombre, u.ci, u.correo,
                  u.clave_predeterminada, u.activo, u.ultimo_login_en
         ORDER BY u.nombre"
    );
    $datos = $rs->fetch_all(MYSQLI_ASSOC);
    foreach ($datos as &$fila) {
        $fila['rol_ids'] = $fila['rol_ids'] === '' ? [] : array_map('intval', explode(',', $fila['rol_ids']));
    }
    unset($fila);
    responderJson($datos);
}

function guardarUsuario(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_usuario'] ?? 0);
    $usuario = strtolower(trim($_POST['usuario'] ?? ''));
    $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $ci = trim($_POST['ci'] ?? '');
    $correo = textoNullable($_POST['correo'] ?? null);
    $clave = (string) ($_POST['clave'] ?? '');
    $roles = array_values(array_unique(array_filter(array_map('intval', $_POST['roles'] ?? []))));

    if (!preg_match('/^[a-z0-9._-]{3,60}$/', $usuario)) {
        responderJson(['status' => 'error', 'mensaje' => 'El usuario debe tener entre 3 y 60 caracteres: letras, números, punto, guion o guion bajo.'], 400);
    }
    if ($nombre === '' || $ci === '') {
        responderJson(['status' => 'error', 'mensaje' => 'Nombre y CI son obligatorios.'], 400);
    }
    if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        responderJson(['status' => 'error', 'mensaje' => 'El correo no es válido.'], 400);
    }
    if ($id === 0) {
        validarFortalezaClave($clave);
    }
    if ($id === $identidad['id_usuario'] && !rolAdminSeleccionado($db, $roles) && in_array('ADMIN', $identidad['roles'], true)) {
        responderJson(['status' => 'error', 'mensaje' => 'No puede quitarse a sí mismo el rol ADMIN.'], 400);
    }

    $db->begin_transaction();
    try {
        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE usuarios SET usuario = ?, nombre = ?, ci = ?, correo = ? WHERE id_usuario = ?'
            );
            $stmt->bind_param('ssssi', $usuario, $nombre, $ci, $correo, $id);
            $stmt->execute();
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                'INSERT INTO usuarios
                    (usuario, nombre, ci, correo, clave_hash, clave_predeterminada)
                 VALUES (?, ?, ?, ?, ?, 1)'
            );
            $stmt->bind_param('sssss', $usuario, $nombre, $ci, $correo, $hash);
            $stmt->execute();
            $id = $db->insert_id;
            $stmt = $db->prepare('INSERT INTO usuario_configuraciones (id_usuario) VALUES (?)');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }

        $stmt = $db->prepare('DELETE FROM usuario_rol WHERE id_usuario = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $actorId = (int) $identidad['id_usuario'];
        $stmtRol = $db->prepare(
            'INSERT INTO usuario_rol (id_usuario, id_rol, asignado_por) VALUES (?, ?, ?)'
        );
        foreach ($roles as $idRol) {
            $stmtRol->bind_param('iii', $id, $idRol, $actorId);
            $stmtRol->execute();
        }

        $db->commit();
        responderJson(['status' => 'ok', 'id_usuario' => $id]);
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function rolAdminSeleccionado(mysqli $db, array $roles): bool
{
    if ($roles === []) {
        return false;
    }
    $marcadores = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE codigo = 'ADMIN' AND id_rol IN ({$marcadores})");
    $tipos = str_repeat('i', count($roles));
    $stmt->bind_param($tipos, ...$roles);
    $stmt->execute();

    return (int) $stmt->get_result()->fetch_row()[0] > 0;
}

function restablecerClave(mysqli $db, array $identidad): void
{
    $id = (int) ($_POST['id_usuario'] ?? 0);
    $clave = (string) ($_POST['clave'] ?? '');
    if ($id <= 0 || $id === $identidad['id_usuario']) {
        responderJson(['status' => 'error', 'mensaje' => 'Use Mi perfil para cambiar su propia contraseña.'], 400);
    }
    validarFortalezaClave($clave);
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        'UPDATE usuarios
         SET clave_hash = ?, clave_predeterminada = 1, version_sesion = version_sesion + 1
         WHERE id_usuario = ?'
    );
    $stmt->bind_param('si', $hash, $id);
    $stmt->execute();
    responderJson(['status' => 'ok']);
}

function cambiarEstadoUsuario(mysqli $db, array $identidad, bool $activar): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        responderJson(['status' => 'error', 'mensaje' => 'ID no recibido.'], 400);
    }
    if ($id === $identidad['id_usuario']) {
        responderJson(['status' => 'error', 'mensaje' => 'No puede desactivar su propio usuario.'], 400);
    }

    if ($activar) {
        $stmt = $db->prepare(
            'UPDATE usuarios SET activo = 1, eliminado_en = NULL, eliminado_por = NULL WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $db->prepare(
            'UPDATE usuarios
             SET activo = 0, eliminado_en = NOW(), eliminado_por = ?, version_sesion = version_sesion + 1
             WHERE id_usuario = ?'
        );
        $actorId = (int) $identidad['id_usuario'];
        $stmt->bind_param('ii', $actorId, $id);
    }
    $stmt->execute();
    responderJson(['status' => 'ok', 'activo' => $activar ? 1 : 0]);
}

function textoNullable($valor): ?string
{
    $texto = trim((string) ($valor ?? ''));
    return $texto === '' ? null : $texto;
}
