<?php
declare(strict_types=1);
require_once __DIR__ . '/conexion.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}

function usuarioActual(mysqli $db): ?array {
    $id = (int)($_SESSION['usuario_id'] ?? 0);
    if (!$id) return null;
    $s = $db->prepare('SELECT u.id_usuario, u.nombre, u.ci, COALESCE(c.modo_oscuro, 0) modo_oscuro FROM usuarios u LEFT JOIN usuario_configuracion c ON c.id_usuario=u.id_usuario AND c.eliminado_en IS NULL WHERE u.id_usuario=? AND u.activo=1 AND u.eliminado_en IS NULL');
    $s->bind_param('i', $id);
    $s->execute();
    return $s->get_result()->fetch_assoc() ?: null;
}

function exigirUsuario(mysqli $db): array {
    $u = usuarioActual($db);
    if (!$u) {
        http_response_code(401);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            exit(json_encode(['error' => 'Sesión requerida'], JSON_UNESCAPED_UNICODE));
        }
        header('Location: /salus-infirmorum/login.php');
        exit;
    }
    return $u;
}

function puede(mysqli $db, int $usuarioId, string $codigo): bool {
    $s = $db->prepare('SELECT 1 FROM usuario_rol ur JOIN roles r ON r.id_rol=ur.id_rol JOIN rol_permiso rp ON rp.id_rol=r.id_rol JOIN permisos p ON p.id_permiso=rp.id_permiso WHERE ur.id_usuario=? AND p.codigo=? AND ur.eliminado_en IS NULL AND r.eliminado_en IS NULL AND r.activo=1 AND rp.eliminado_en IS NULL AND p.eliminado_en IS NULL AND p.activo=1 LIMIT 1');
    $s->bind_param('is', $usuarioId, $codigo);
    $s->execute();
    return (bool)$s->get_result()->fetch_row();
}

function exigirPermiso(mysqli $db, string $codigo): array {
    $u = exigirUsuario($db);
    if (!puede($db, (int)$u['id_usuario'], $codigo)) {
        http_response_code(403);
        exit('No tenés permiso para esta operación.');
    }
    return $u;
}

function csrf(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verificarCsrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf(), $token)) {
        http_response_code(419);
        throw new RuntimeException('Sesión vencida. Actualizá la página.');
    }
}

function auditar(mysqli $db, int $actorId, string $modulo, string $entidad, array $clave, string $accion, ?array $antes, ?array $despues): void {
    if ($antes !== null) unset($antes['password_hash']);
    if ($despues !== null) unset($despues['password_hash']);
    $s = $db->prepare('INSERT INTO auditoria_seguridad (id_usuario_actor, actor_identificador, modulo, entidad, clave_registro, accion, datos_anteriores, datos_nuevos, direccion_ip, user_agent, creado_por) VALUES (?, (SELECT ci FROM usuarios WHERE id_usuario=?), ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $claveJson = json_encode($clave, JSON_UNESCAPED_UNICODE);
    $antesJson = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE);
    $despuesJson = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $s->bind_param('iissssssssi', $actorId, $actorId, $modulo, $entidad, $claveJson, $accion, $antesJson, $despuesJson, $ip, $agent, $actorId);
    $s->execute();
}
