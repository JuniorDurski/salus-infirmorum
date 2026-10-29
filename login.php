<?php

require_once __DIR__ . '/backend/conexion.php';
require_once __DIR__ . '/backend/seguridad.php';

$identidad = identidadActual($conexion);
if ($identidad !== null) {
    header('Location: ' . ($identidad['clave_predeterminada'] ? 'cambiar_clave.php' : 'index.php'));
    exit;
}

$retorno = (string) ($_GET['retorno'] ?? 'index.php');
if ($retorno === '' || !preg_match('#^/?[a-zA-Z0-9_./?=&%+\-]+$#', $retorno) || str_contains($retorno, ':')) {
    $retorno = 'index.php';
}
$sesionExpiro = !empty($_SESSION['sesion_expirada']);
unset($_SESSION['sesion_expirada']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Salus Infirmorum</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
<main class="auth-contenedor">
    <section class="auth-panel">
        <img class="auth-logo" src="images/icono_menu.png" alt="Salus Infirmorum">
        <span class="eyebrow">Acceso seguro</span>
        <h1>Iniciar sesión</h1>
        <p>Ingrese con su usuario, CI o correo.</p>
        <form id="formLogin" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <input type="hidden" id="retorno" value="<?= htmlspecialchars($retorno, ENT_QUOTES) ?>">
            <div class="campo">
                <label for="identificador">Usuario</label>
                <input id="identificador" name="identificador" required autofocus autocomplete="username">
            </div>
            <div class="campo">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required autocomplete="current-password">
            </div>
            <div id="mensaje" class="mensaje"><?= $sesionExpiro ? 'La sesión expiró por inactividad.' : '' ?></div>
            <button class="boton" type="submit">Ingresar al sistema</button>
        </form>
    </section>
</main>
<script>
document.getElementById('formLogin').addEventListener('submit', async function (evento) {
    evento.preventDefault();
    const boton = this.querySelector('button');
    const mensaje = document.getElementById('mensaje');
    boton.disabled = true;
    mensaje.textContent = '';
    try {
        const respuesta = await fetch('backend/usuariosDAO.php?q=login', { method: 'POST', body: new FormData(this) });
        const datos = await respuesta.json();
        if (!respuesta.ok || datos.status !== 'ok') throw new Error(datos.mensaje || 'No se pudo iniciar sesión.');
        const retorno = document.getElementById('retorno').value;
        location.href = datos.requiere_cambio_clave ? 'cambiar_clave.php' : (retorno || datos.redirect);
    } catch (error) {
        mensaje.textContent = error.message;
        boton.disabled = false;
    }
});
</script>
</body>
</html>
