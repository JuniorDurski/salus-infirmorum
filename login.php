<?php
require_once __DIR__ . '/backend/auth.php';
if (usuarioActual($conexion)) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificarCsrf();
        $ci = trim((string)($_POST['ci'] ?? ''));
        $password = (string)($_POST['clave'] ?? '');
        $row = $conexion->execute_query('SELECT id_usuario,password_hash FROM usuarios WHERE ci=? AND activo=1 AND eliminado_en IS NULL', [$ci])->fetch_assoc();
        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int)$row['id_usuario'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $conexion->execute_query('UPDATE usuarios SET ultimo_acceso_en=NOW(6) WHERE id_usuario=?', [(int)$row['id_usuario']]);
            auditar($conexion,(int)$row['id_usuario'],'SEGURIDAD','usuarios',['id_usuario'=>(int)$row['id_usuario']],'INICIAR_SESION',null,null);
            header('Location: index.php'); exit;
        }
        $error = 'Credenciales inválidas.';
    } catch (Throwable $e) { $error = 'No se pudo iniciar sesión.'; }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ingresar | Salus Infirmorum</title><link rel="stylesheet" href="assets/app.css"></head>
<body class="login-body"><main class="login-card"><div class="brand-mark">S</div><p class="eyebrow">Sistema de gestión clínica</p><h1>Salus Infirmorum</h1><p class="muted">Ingresá con tu documento y contraseña para continuar.</p>
<?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf()) ?>"><label>Documento<input name="ci" autocomplete="username" required autofocus></label><label>Contraseña<input name="clave" type="password" autocomplete="current-password" required></label><button class="primary full" type="submit">Ingresar</button></form></main></body></html>
