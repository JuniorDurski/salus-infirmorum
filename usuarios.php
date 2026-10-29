<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'usuarios.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'usuarios.editar');
include 'menu.php';
?>
<link rel="stylesheet" href="css/seguridad.css">
<main class="seguridad-grid">
    <section class="panel-seguridad">
        <h2>Usuario</h2>
        <form id="formUsuario">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo"><label>Usuario</label><input name="usuario" id="usuario" required pattern="[a-z0-9._-]{3,60}"></div>
            <div class="campo"><label>Nombre</label><input name="nombre" id="nombre" required></div>
            <div class="campo"><label>CI</label><input name="ci" id="ci" required></div>
            <div class="campo"><label>Correo</label><input type="email" name="correo" id="correo"></div>
            <div class="campo" id="grupoClave"><label>Contraseña inicial</label><input type="password" name="clave" id="clave"><small>Puede ser cualquier valor no vacío. El usuario deberá cambiarla al ingresar.</small></div>
            <div class="campo"><label>Roles</label><div id="listaRoles" class="checks"></div></div>
            <?php if ($puedeEditar): ?><button class="boton" type="submit">Guardar</button><?php endif; ?>
            <button class="boton secundario" type="button" onclick="limpiarUsuario()">Cancelar</button>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
    <section class="panel-seguridad">
        <h2>Usuarios</h2>
        <table class="tabla-seguridad">
            <thead><tr><th>Usuario</th><th>Nombre</th><th>CI / correo</th><th>Roles</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaUsuarios"></tbody>
        </table>
    </section>
</main>
<script>
const puedeEditarUsuarios = <?= $puedeEditar ? 'true' : 'false' ?>;
const csrfUsuarios = <?= json_encode(tokenCsrf()) ?>;
let usuariosDatos = [];
let rolesDatos = [];

async function cargarUsuarios() {
    const [usuarios, roles] = await Promise.all([
        fetch('backend/usuariosDAO.php?q=listar').then(r => r.json()),
        fetch('backend/rolesDAO.php?q=listar').then(r => r.json())
    ]);
    usuariosDatos = usuarios;
    rolesDatos = roles.filter(r => Number(r.activo) === 1);
    listaRoles.innerHTML = rolesDatos.map(r => `<label><input type="checkbox" name="roles[]" value="${r.id_rol}"> ${esc(r.nombre)}</label>`).join('');
    tablaUsuarios.innerHTML = usuariosDatos.map(u => `<tr>
        <td>${esc(u.usuario)}</td><td>${esc(u.nombre)}</td>
        <td>${esc(u.ci)}<br><small>${esc(u.correo || '')}</small></td>
        <td>${esc(u.roles || '')}</td>
        <td class="${Number(u.activo) ? 'estado-activo' : 'estado-inactivo'}">${Number(u.activo) ? 'ACTIVO' : 'INACTIVO'}${Number(u.clave_predeterminada) ? '<br><small>Clave temporal</small>' : ''}</td>
        <td>${puedeEditarUsuarios ? `<button onclick="editarUsuario(${u.id_usuario})">Editar</button> <button onclick="estadoUsuario(${u.id_usuario}, ${Number(u.activo) ? 0 : 1})">${Number(u.activo) ? 'Desactivar' : 'Activar'}</button> <button onclick="resetClave(${u.id_usuario})">Restablecer clave</button>` : ''}</td>
    </tr>`).join('');
}

function editarUsuario(id) {
    const u = usuariosDatos.find(x => Number(x.id_usuario) === Number(id));
    id_usuario.value = u.id_usuario; usuario.value = u.usuario; nombre.value = u.nombre;
    ci.value = u.ci; correo.value = u.correo || ''; clave.value = ''; grupoClave.style.display = 'none';
    document.querySelectorAll('#listaRoles input').forEach(c => c.checked = u.rol_ids.map(Number).includes(Number(c.value)));
}
function limpiarUsuario() { formUsuario.reset(); id_usuario.value = ''; grupoClave.style.display = ''; }
formUsuario.addEventListener('submit', async e => {
    e.preventDefault();
    const r = await fetch('backend/usuariosDAO.php?q=guardar', {method:'POST', body:new FormData(formUsuario)}); const d = await r.json();
    mensaje.textContent = d.mensaje || (d.status === 'ok' ? 'Usuario guardado.' : 'Error');
    if (d.status === 'ok') { limpiarUsuario(); cargarUsuarios(); }
});
async function estadoUsuario(id, activo) {
    if (!confirm(activo ? '¿Activar usuario?' : '¿Desactivar usuario?')) return;
    const f = new FormData(); f.append('id', id); f.append('csrf_token', csrfUsuarios);
    const r = await fetch(`backend/usuariosDAO.php?q=${activo ? 'activar' : 'eliminar'}`, {method:'POST',body:f}); const d=await r.json();
    if (d.status !== 'ok') alert(d.mensaje); else cargarUsuarios();
}
async function resetClave(id) {
    const clave = prompt('Nueva contraseña temporal:'); if (!clave) return;
    const f = new FormData(); f.append('id_usuario', id); f.append('clave', clave); f.append('csrf_token', csrfUsuarios);
    const r = await fetch('backend/usuariosDAO.php?q=restablecer_clave',{method:'POST',body:f}); const d=await r.json();
    alert(d.mensaje || (d.status === 'ok' ? 'Contraseña restablecida.' : 'Error')); if(d.status==='ok') cargarUsuarios();
}
function esc(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;}
cargarUsuarios();
</script>
<?php include 'footer.php'; ?>
