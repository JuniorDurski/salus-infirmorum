<?php

require_once __DIR__ . '/backend/conexion.php';
require_once __DIR__ . '/backend/seguridad.php';
$identidad = requerirAutenticacion($conexion, null, false, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar contraseña | Salus Infirmorum</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js"></script>
</head>
<body class="auth-page">
<main class="auth-contenedor">
    <section class="auth-panel">
        <span class="eyebrow">Protección de la cuenta</span>
        <h1>Cambiar contraseña</h1>
        <p>Usuario: <strong><?= htmlspecialchars($identidad['usuario']) ?></strong></p>
        <?php if ($identidad['clave_predeterminada']): ?>
            <p>Debe reemplazar la contraseña temporal antes de continuar.</p>
        <?php endif; ?>
        <form id="formClave">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo">
                <label for="clave_actual">Contraseña actual</label>
                <input type="password" id="clave_actual" name="clave_actual" required autocomplete="current-password" data-password-field>
            </div>
            <div class="campo">
                <label for="clave_nueva">Nueva contraseña</label>
                <input type="password" id="clave_nueva" name="clave_nueva" required autocomplete="new-password" data-password-field>
                <small>Puede utilizar cualquier contraseña que no esté vacía.</small>
            </div>
            <div class="campo">
                <label for="clave_confirmacion">Confirmar nueva contraseña</label>
                <input type="password" id="clave_confirmacion" name="clave_confirmacion" required autocomplete="new-password" data-password-field>
            </div>
            <label class="password-visibility"><input type="checkbox" data-password-toggle="#formClave [data-password-field]"> Mostrar contraseñas</label>
            <div id="mensaje" class="mensaje"></div>
            <button class="boton" type="submit">Guardar contraseña</button>
        </form>
    </section>
</main>
<script>
document.getElementById('formClave').addEventListener('submit', async function (evento) {
    evento.preventDefault();
    const mensaje = document.getElementById('mensaje');
    const respuesta = await fetch('backend/usuariosDAO.php?q=cambiar_clave', { method: 'POST', body: new FormData(this) });
    const datos = await respuesta.json();
    if (!respuesta.ok || datos.status !== 'ok') {
        mensaje.textContent = datos.mensaje || 'No se pudo cambiar la contraseña.';
        return;
    }
    location.href = datos.redirect || 'index.php';
});
</script>
</body>
</html>
