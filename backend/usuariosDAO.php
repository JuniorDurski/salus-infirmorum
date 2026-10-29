<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$q = $_GET['q'] ?? '';

try {
    switch ($q) {
        case 'login':
            iniciarSesion($conexion);
            break;

        case 'logout':
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $parametros = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $parametros['path'],
                    $parametros['domain'],
                    $parametros['secure'],
                    $parametros['httponly']
                );
            }
            session_destroy();
            echo json_encode(['status' => 'ok']);
            break;

        case 'fuente_color':
            obtenerTema($conexion);
            break;

        case 'cambiar_fuente':
            cambiarTema($conexion);
            break;

        case 'configuracion':
            obtenerConfiguracion($conexion);
            break;

        case 'guardar_configuracion':
            guardarConfiguracion($conexion);
            break;

        case 'listar':
            listarUsuarios($conexion);
            break;

        case 'guardar':
            guardarUsuario($conexion);
            break;

        case 'eliminar':
            cambiarEstadoUsuario($conexion, false);
            break;

        case 'activar':
            cambiarEstadoUsuario($conexion, true);
            break;

        default:
            errorUsuario('Operación no válida.');
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function iniciarSesion(mysqli $conexion): void
{
    $ci = trim($_POST['ci'] ?? '');
    $clave = (string) ($_POST['clave'] ?? '');
    $stmt = $conexion->prepare(
        "SELECT u.id_usuario, u.nombre, u.ci, u.correo, u.clave_hash,
                COALESCE(GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', '), '') AS roles
         FROM usuarios u
         LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
         LEFT JOIN roles r ON r.id_rol = ur.id_rol AND r.activo = 1
         WHERE u.ci = ? AND u.activo = 1
         GROUP BY u.id_usuario, u.nombre, u.ci, u.correo, u.clave_hash
         LIMIT 1"
    );
    $stmt->bind_param('s', $ci);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $exitoso = $usuario !== null && password_verify($clave, $usuario['clave_hash']);
    $idUsuario = $usuario !== null ? (int) $usuario['id_usuario'] : null;

    registrarLogueo($conexion, $idUsuario, $ci, $exitoso, $exitoso ? null : 'Credenciales inválidas');

    if (!$exitoso) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'mensaje' => 'Credenciales inválidas.']);
        return;
    }

    unset($usuario['clave_hash']);
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
    $conexion->query('SET @app_usuario_id = ' . (int) $usuario['id_usuario']);
    echo json_encode(['status' => 'ok']);
}

function registrarLogueo(
    mysqli $conexion,
    ?int $idUsuario,
    string $identificador,
    bool $exitoso,
    ?string $motivo
): void {
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45) ?: null;
    $agente = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null;
    $estado = $exitoso ? 1 : 0;
    $stmt = $conexion->prepare(
        'INSERT INTO logueos
            (id_usuario, identificador, exitoso, ip, agente_usuario, motivo_fallo)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isisss', $idUsuario, $identificador, $estado, $ip, $agente, $motivo);
    $stmt->execute();
}

function obtenerTema(mysqli $conexion): void
{
    $id = usuarioActualId();
    if ($id === null) {
        echo json_encode(['fuente_claro' => 1, 'modo_oscuro' => 0]);
        return;
    }

    $stmt = $conexion->prepare(
        'SELECT modo_oscuro, IF(modo_oscuro = 1, 0, 1) AS fuente_claro
         FROM usuario_configuraciones WHERE id_usuario = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $configuracion = $stmt->get_result()->fetch_assoc();
    echo json_encode($configuracion ?? ['fuente_claro' => 1, 'modo_oscuro' => 0]);
}

function cambiarTema(mysqli $conexion): void
{
    $id = usuarioActualId();
    if ($id === null) {
        errorUsuario('Debe iniciar sesión para guardar el aspecto.', 401);
    }

    $stmt = $conexion->prepare(
        'INSERT INTO usuario_configuraciones (id_usuario, modo_oscuro)
         VALUES (?, 1)
         ON DUPLICATE KEY UPDATE modo_oscuro = IF(modo_oscuro = 1, 0, 1)'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['status' => 'ok']);
}

function obtenerConfiguracion(mysqli $conexion): void
{
    $id = usuarioActualId();
    if ($id === null) {
        errorUsuario('Debe iniciar sesión.', 401);
    }

    $stmt = $conexion->prepare(
        'SELECT c.id_usuario, c.id_titulo, c.id_universidad, c.numero_registro,
                c.url_firma, c.url_firma_sello, c.url_sello, c.modo_oscuro,
                t.titulo, t.abreviatura AS titulo_abreviatura, t.es_femenino,
                u.nombre AS universidad, u.abreviatura AS universidad_abreviatura
         FROM usuario_configuraciones c
         LEFT JOIN titulos t ON t.id_titulo = c.id_titulo
         LEFT JOIN universidades u ON u.id_universidad = c.id_universidad
         WHERE c.id_usuario = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_assoc() ?: [], JSON_UNESCAPED_UNICODE);
}

function guardarConfiguracion(mysqli $conexion): void
{
    $id = usuarioActualId();
    if ($id === null) {
        errorUsuario('Debe iniciar sesión.', 401);
    }

    $idTitulo = ($_POST['id_titulo'] ?? '') !== '' ? (int) $_POST['id_titulo'] : null;
    $idUniversidad = ($_POST['id_universidad'] ?? '') !== '' ? (int) $_POST['id_universidad'] : null;
    $registro = textoNullableUsuario($_POST['numero_registro'] ?? null);
    $urlFirma = textoNullableUsuario($_POST['url_firma'] ?? null);
    $urlFirmaSello = textoNullableUsuario($_POST['url_firma_sello'] ?? null);
    $urlSello = textoNullableUsuario($_POST['url_sello'] ?? null);
    $modoOscuro = !empty($_POST['modo_oscuro']) ? 1 : 0;

    foreach ([$urlFirma, $urlFirmaSello, $urlSello] as $url) {
        if ($url !== null && filter_var($url, FILTER_VALIDATE_URL) === false) {
            errorUsuario('Una de las URLs de firma o sello no es válida.');
        }
    }

    $stmt = $conexion->prepare(
        'INSERT INTO usuario_configuraciones
            (id_usuario, id_titulo, id_universidad, numero_registro,
             url_firma, url_firma_sello, url_sello, modo_oscuro)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            id_titulo = VALUES(id_titulo),
            id_universidad = VALUES(id_universidad),
            numero_registro = VALUES(numero_registro),
            url_firma = VALUES(url_firma),
            url_firma_sello = VALUES(url_firma_sello),
            url_sello = VALUES(url_sello),
            modo_oscuro = VALUES(modo_oscuro)'
    );
    $stmt->bind_param(
        'iiissssi',
        $id,
        $idTitulo,
        $idUniversidad,
        $registro,
        $urlFirma,
        $urlFirmaSello,
        $urlSello,
        $modoOscuro
    );
    $stmt->execute();
    echo json_encode(['status' => 'ok']);
}

function listarUsuarios(mysqli $conexion): void
{
    $sql = "SELECT u.id_usuario, u.nombre, u.ci, u.correo, u.activo,
                   COALESCE(GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ', '), '') AS roles,
                   COALESCE(c.modo_oscuro, 0) AS modo_oscuro,
                   IF(COALESCE(c.modo_oscuro, 0) = 1, 0, 1) AS fuente_claro
            FROM usuarios u
            LEFT JOIN usuario_configuraciones c ON c.id_usuario = u.id_usuario
            LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
            LEFT JOIN roles r ON r.id_rol = ur.id_rol
            GROUP BY u.id_usuario, u.nombre, u.ci, u.correo, u.activo, c.modo_oscuro
            ORDER BY u.id_usuario DESC";
    $rs = $conexion->query($sql);
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
}

function guardarUsuario(mysqli $conexion): void
{
    $id = (int) ($_POST['id_usuario'] ?? 0);
    $nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
    $ci = trim($_POST['ci'] ?? '');
    $correo = trim($_POST['correo'] ?? '') ?: null;
    $clave = (string) ($_POST['clave'] ?? '');
    $idRol = (int) ($_POST['id_rol'] ?? 0);
    $actor = usuarioActualId();

    if ($nombre === '' || $ci === '' || ($id === 0 && $clave === '')) {
        errorUsuario('Nombre, CI y clave inicial son obligatorios.');
    }
    if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        errorUsuario('El correo no es válido.');
    }

    $conexion->begin_transaction();
    try {
        if ($id > 0) {
            if ($clave !== '') {
                $hash = password_hash($clave, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare(
                    'UPDATE usuarios SET nombre = ?, ci = ?, correo = ?, clave_hash = ? WHERE id_usuario = ?'
                );
                $stmt->bind_param('ssssi', $nombre, $ci, $correo, $hash, $id);
            } else {
                $stmt = $conexion->prepare(
                    'UPDATE usuarios SET nombre = ?, ci = ?, correo = ? WHERE id_usuario = ?'
                );
                $stmt->bind_param('sssi', $nombre, $ci, $correo, $id);
            }
            $stmt->execute();
        } else {
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare(
                'INSERT INTO usuarios (nombre, ci, correo, clave_hash) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('ssss', $nombre, $ci, $correo, $hash);
            $stmt->execute();
            $id = $conexion->insert_id;

            $stmt = $conexion->prepare(
                'INSERT INTO usuario_configuraciones (id_usuario) VALUES (?)'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }

        if ($idRol > 0) {
            $stmt = $conexion->prepare(
                'INSERT IGNORE INTO usuario_rol (id_usuario, id_rol, asignado_por) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('iii', $id, $idRol, $actor);
            $stmt->execute();
        }

        $conexion->commit();
        echo json_encode(['status' => 'ok', 'id_usuario' => $id]);
    } catch (Throwable $e) {
        $conexion->rollback();
        throw $e;
    }
}

function cambiarEstadoUsuario(mysqli $conexion, bool $activar): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        errorUsuario('ID no recibido.');
    }
    $actor = usuarioActualId();

    if ($activar) {
        $stmt = $conexion->prepare(
            'UPDATE usuarios SET activo = 1, eliminado_en = NULL, eliminado_por = NULL WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $conexion->prepare(
            'UPDATE usuarios SET activo = 0, eliminado_en = NOW(), eliminado_por = ? WHERE id_usuario = ?'
        );
        $stmt->bind_param('ii', $actor, $id);
    }

    $stmt->execute();
    echo json_encode(['status' => 'ok', 'activo' => $activar ? 1 : 0]);
}

function usuarioActualId(): ?int
{
    return isset($_SESSION['usuario']['id_usuario'])
        ? (int) $_SESSION['usuario']['id_usuario']
        : null;
}

function textoNullableUsuario($valor): ?string
{
    $texto = trim((string) ($valor ?? ''));

    return $texto === '' ? null : $texto;
}

function errorUsuario(string $mensaje, int $codigo = 400): void
{
    http_response_code($codigo);
    echo json_encode(['status' => 'error', 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}
