<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'usuarios.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'usuarios.editar');
$tituloPagina = 'Usuarios';
include 'menu.php';
?>
<div class="page-header">
    <div><span class="eyebrow">Seguridad</span><h1>Usuarios</h1><p>Administre identidades, roles y el acceso inicial al sistema.</p></div>
    <div class="search-box"><input type="search" id="buscarUsuarios" placeholder="Buscar usuario, nombre, CI, correo o rol…" aria-label="Buscar usuarios"></div>
</div>
<main class="seguridad-grid">
    <section class="panel-seguridad">
        <h2>Usuario</h2>
        <form id="formUsuario">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo"><label>Usuario</label><input name="usuario" id="usuario" required pattern="[a-z0-9._-]{3,60}"></div>
            <div class="campo"><label>Nombre</label><input name="nombre" id="nombre" data-uppercase required></div>
            <div class="campo"><label>CI</label><input name="ci" id="ci" required></div>
            <div class="campo"><label>Correo</label><input type="email" name="correo" id="correo"></div>
            <div class="campo" id="grupoClave"><label>Contraseña inicial</label><input type="password" name="clave" id="clave" data-password-field><label class="password-visibility"><input type="checkbox" data-password-toggle="#clave"> Mostrar contraseña</label><small>Puede ser cualquier valor no vacío. El usuario deberá cambiarla al ingresar.</small></div>
            <div class="campo"><label>Roles</label><div id="listaRoles" class="checks"></div></div>
            <?php if ($puedeEditar): ?><button class="boton" type="submit">Guardar</button><?php endif; ?>
            <button class="boton secundario" type="button" onclick="limpiarUsuario()">Cancelar</button>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
    <section class="panel-seguridad">
        <div class="card-header"><div><h2>Usuarios</h2><p class="help-text" id="resumenUsuarios">Cargando…</p></div></div>
        <div class="table-wrap"><table class="tabla-seguridad">
            <thead><tr><th>Usuario</th><th>Nombre</th><th>CI / correo</th><th>Roles</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaUsuarios"></tbody>
        </table></div>
    </section>
</main>
<?php if ($puedeEditar): ?>
<dialog class="modal" id="modalRestablecerClave">
    <div class="modal-header">
        <div><span class="eyebrow">Acceso temporal</span><h2 id="tituloRestablecerClave">Restablecer contraseña</h2></div>
        <button class="icon-button" type="button" onclick="modalRestablecerClave.close()" aria-label="Cerrar">×</button>
    </div>
    <form id="formRestablecerClave" autocomplete="off">
        <input type="hidden" name="id_usuario" id="id_usuario_clave">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
        <div class="modal-body">
            <div class="campo">
                <label for="clave_temporal">Nueva contraseña temporal</label>
                <input type="password" name="clave" id="clave_temporal" required data-password-field autocomplete="new-password">
                <label class="password-visibility"><input type="checkbox" data-password-toggle="#clave_temporal"> Mostrar contraseña</label>
                <small>El usuario deberá cambiarla la próxima vez que ingrese.</small>
            </div>
            <div id="mensajeClaveTemporal" class="mensaje"></div>
        </div>
        <div class="modal-footer"><button class="btn-secondary" type="button" onclick="modalRestablecerClave.close()">Cancelar</button><button type="submit">Restablecer</button></div>
    </form>
</dialog>
<?php endif; ?>
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
    renderUsuarios();
}

function renderUsuarios() {
    const termino = buscarUsuarios.value.trim().toLowerCase();
    const datos = usuariosDatos.filter(u => !termino || `${u.usuario} ${u.nombre} ${u.ci} ${u.correo || ''} ${u.roles || ''}`.toLowerCase().includes(termino));
    resumenUsuarios.textContent = `${datos.length} de ${usuariosDatos.length} usuario(s)`;
    tablaUsuarios.innerHTML = datos.length ? datos.map(u => `<tr>
        <td>${esc(u.usuario)}</td><td>${esc(u.nombre)}</td>
        <td>${esc(u.ci)}<br><small>${esc(u.correo || '')}</small></td>
        <td>${esc(u.roles || '')}</td>
        <td>${Number(u.activo) ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'}${Number(u.clave_predeterminada) ? '<br><small>Clave temporal</small>' : ''}</td>
        <td><div class="table-actions">${puedeEditarUsuarios ? `<button class="btn-small btn-secondary" onclick="editarUsuario(${u.id_usuario})">Editar</button><button class="btn-small ${Number(u.activo) ? 'btn-danger' : 'btn-secondary'}" onclick="estadoUsuario(${u.id_usuario}, ${Number(u.activo) ? 0 : 1})">${Number(u.activo) ? 'Desactivar' : 'Activar'}</button><button class="btn-small btn-secondary" onclick="resetClave(${u.id_usuario})">Restablecer clave</button>` : ''}</div></td>
    </tr>`).join('') : '<tr><td colspan="6" class="empty-state">No se encontraron usuarios.</td></tr>';
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
    const usuario = usuariosDatos.find(item => Number(item.id_usuario) === Number(id));
    formRestablecerClave.reset(); id_usuario_clave.value = id; mensajeClaveTemporal.textContent = '';
    tituloRestablecerClave.textContent = `Restablecer contraseña · ${usuario?.nombre || usuario?.usuario || ''}`;
    modalRestablecerClave.showModal(); clave_temporal.focus();
}
if (puedeEditarUsuarios) formRestablecerClave.addEventListener('submit', async evento => {
    evento.preventDefault(); mensajeClaveTemporal.textContent = '';
    const r = await fetch('backend/usuariosDAO.php?q=restablecer_clave',{method:'POST',body:new FormData(formRestablecerClave)}); const d=await r.json();
    if(d.status!=='ok'){mensajeClaveTemporal.textContent=d.mensaje||'No se pudo restablecer la contraseña.';return;}
    modalRestablecerClave.close(); alert(d.mensaje||'Contraseña restablecida.'); cargarUsuarios();
});
function esc(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;}
buscarUsuarios.addEventListener('input', renderUsuarios);
cargarUsuarios();
</script>
<?php include 'footer.php'; ?>
