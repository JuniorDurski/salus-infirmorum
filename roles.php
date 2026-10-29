<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'roles.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'roles.editar');
$tituloPagina = 'Roles';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Seguridad</span><h1>Roles</h1><p>Agrupe permisos funcionales y de navegación según cada perfil.</p></div>
    <div class="search-box"><input type="search" id="buscarRoles" placeholder="Buscar rol…" aria-label="Buscar roles"></div>
</div>

<main class="seguridad-grid">
    <section class="panel-seguridad" id="panelRol">
        <h2>Datos del rol</h2>
        <form id="formRol">
            <input type="hidden" name="id_rol" id="id_rol">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="codigo">Código</label><input name="codigo" id="codigo" required></div>
            <div class="campo"><label for="nombre">Nombre</label><input name="nombre" id="nombre" required></div>
            <div class="campo"><label for="descripcion">Descripción</label><textarea name="descripcion" id="descripcion"></textarea></div>
            <div class="campo">
                <label for="buscarPermisosRol">Asignar permisos</label>
                <input type="search" id="buscarPermisosRol" placeholder="Buscar permiso funcional o menu.*">
                <div id="listaPermisos" class="checks permission-checks"></div>
                <small>Los permisos <span class="code">menu.*</span> controlan qué opciones de navegación puede ver el rol.</small>
            </div>
            <div class="form-actions">
                <?php if ($puedeEditar): ?><button type="submit">Guardar rol</button><?php endif; ?>
                <button class="btn-secondary" type="button" onclick="limpiarRol()">Cancelar</button>
            </div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>

    <section class="panel-seguridad">
        <div class="card-header"><div><h2>Roles configurados</h2><p class="help-text" id="resumenRoles">Cargando…</p></div></div>
        <div class="table-wrap"><table class="tabla-seguridad">
            <thead><tr><th>Código</th><th>Nombre</th><th>Permisos</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaRoles"></tbody>
        </table></div>
    </section>
</main>

<dialog id="modalPermisosRol" class="modal">
    <div class="modal-header">
        <div><span class="eyebrow" id="modalPermisosTipo">Permisos</span><h2 id="modalPermisosTitulo">Permisos del rol</h2></div>
        <button class="icon-button" type="button" onclick="cerrarModalPermisos()" aria-label="Cerrar">×</button>
    </div>
    <div class="campo"><label for="buscarPermisosModal">Buscar en esta lista</label><input type="search" id="buscarPermisosModal" placeholder="Código o nombre del permiso…"></div>
    <div id="contenidoPermisosModal" class="permission-list"></div>
    <div class="modal-footer"><button class="btn-secondary" type="button" onclick="cerrarModalPermisos()">Cerrar</button></div>
</dialog>

<script>
const puedeEditarRoles = <?= $puedeEditar ? 'true' : 'false' ?>;
let roles = [], permisos = [], rolModal = null, tipoModal = 'funcional';

async function cargarRoles() {
    try {
        [roles, permisos] = await Promise.all([
            Salus.json('backend/rolesDAO.php?q=listar'),
            Salus.json('backend/permisosDAO.php?q=listar')
        ]);
        renderSelectorPermisos(); renderTablaRoles();
    } catch (error) { Salus.toast(error.message, 'error'); }
}

function renderSelectorPermisos() {
    const seleccionados = new Set([...document.querySelectorAll('#listaPermisos input:checked')].map(c => Number(c.value)));
    listaPermisos.innerHTML = permisos.map(p => {
        const esMenu = p.codigo.startsWith('menu.');
        return `<label data-search="${Salus.escape(`${p.codigo} ${p.nombre}`.toLowerCase())}">
            <input type="checkbox" name="permisos[]" value="${p.id_permiso}" ${seleccionados.has(Number(p.id_permiso)) ? 'checked' : ''}>
            <span><strong>${Salus.escape(p.nombre)}</strong><br><span class="code">${Salus.escape(p.codigo)}</span> ${esMenu ? '<span class="badge badge-muted">Menú</span>' : ''} ${Number(p.activo) ? '' : '<span class="badge badge-danger">Inactivo globalmente</span>'}</span>
        </label>`;
    }).join('');
    filtrarSelectorPermisos();
}

function renderTablaRoles() {
    const termino = buscarRoles.value.trim().toLowerCase();
    const datos = roles.filter(r => !termino || `${r.codigo} ${r.nombre} ${r.descripcion || ''}`.toLowerCase().includes(termino));
    resumenRoles.textContent = `${datos.length} de ${roles.length} rol(es)`;
    tablaRoles.innerHTML = datos.length ? datos.map(r => {
        const asignados = permisos.filter(p => r.permiso_ids.map(Number).includes(Number(p.id_permiso)));
        const funcionales = asignados.filter(p => !p.codigo.startsWith('menu.')).length;
        const menus = asignados.filter(p => p.codigo.startsWith('menu.')).length;
        return `<tr>
            <td><span class="code">${Salus.escape(r.codigo)}</span></td><td><strong>${Salus.escape(r.nombre)}</strong><br><small>${Salus.escape(r.descripcion || '')}</small></td>
            <td><div class="table-actions"><button class="btn-small btn-secondary" type="button" onclick="verPermisosRol(${r.id_rol},'funcional')">Ver permisos (${funcionales})</button><button class="btn-small btn-secondary" type="button" onclick="verPermisosRol(${r.id_rol},'menu')">Permisos de menú (${menus})</button></div></td>
            <td>${Number(r.activo) ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'}</td>
            <td><div class="table-actions">${puedeEditarRoles ? `<button class="btn-small" onclick="editarRol(${r.id_rol})">Editar</button><button class="btn-small ${Number(r.activo) ? 'btn-danger' : 'btn-secondary'}" onclick="estadoRol(${r.id_rol},${Number(r.activo) ? 0 : 1})">${Number(r.activo) ? 'Desactivar' : 'Activar'}</button>` : ''}</div></td>
        </tr>`;
    }).join('') : '<tr><td colspan="5" class="empty-state">No se encontraron roles.</td></tr>';
}

function filtrarSelectorPermisos() {
    const termino = buscarPermisosRol.value.trim().toLowerCase();
    document.querySelectorAll('#listaPermisos label').forEach(label => { label.hidden = Boolean(termino) && !label.dataset.search.includes(termino); });
}

function editarRol(id) {
    const rol = roles.find(r => Number(r.id_rol) === Number(id)); if (!rol) return;
    id_rol.value=rol.id_rol; codigo.value=rol.codigo; nombre.value=rol.nombre; descripcion.value=rol.descripcion||'';
    document.querySelectorAll('#listaPermisos input').forEach(c => c.checked=rol.permiso_ids.map(Number).includes(Number(c.value)));
    panelRol.scrollIntoView({behavior:'smooth'});
}
function limpiarRol(){formRol.reset();id_rol.value='';codigo.disabled=false;buscarPermisosRol.value='';filtrarSelectorPermisos();mensaje.textContent='';}

async function estadoRol(id,activo){
    try{await Salus.json('backend/rolesDAO.php?q=cambiar_estado',{method:'POST',body:Salus.form({id_rol:id,activo})});Salus.toast(activo?'Rol activado.':'Rol desactivado.');cargarRoles();}
    catch(error){Salus.toast(error.message,'error');}
}
formRol.addEventListener('submit',async event=>{
    event.preventDefault();
    try{await Salus.json('backend/rolesDAO.php?q=guardar',{method:'POST',body:new FormData(formRol)});Salus.toast('Rol guardado.');limpiarRol();cargarRoles();}
    catch(error){mensaje.textContent=error.message;}
});

function verPermisosRol(id,tipo){
    rolModal=roles.find(r=>Number(r.id_rol)===Number(id));tipoModal=tipo;buscarPermisosModal.value='';
    modalPermisosTipo.textContent=tipo==='menu'?'Navegación':'Seguridad funcional';
    modalPermisosTitulo.textContent=`${tipo==='menu'?'Permisos de menú':'Permisos'} · ${rolModal.nombre}`;
    renderPermisosModal();modalPermisosRol.showModal();
}
function renderPermisosModal(){
    if(!rolModal)return;const termino=buscarPermisosModal.value.trim().toLowerCase();
    const ids=rolModal.permiso_ids.map(Number);const lista=permisos.filter(p=>ids.includes(Number(p.id_permiso))&&(tipoModal==='menu'?p.codigo.startsWith('menu.'):!p.codigo.startsWith('menu.'))).filter(p=>!termino||`${p.codigo} ${p.nombre}`.toLowerCase().includes(termino));
    contenidoPermisosModal.innerHTML=lista.length?lista.map(p=>`<article class="permission-item"><div><strong>${Salus.escape(p.nombre)}</strong><br><span class="code">${Salus.escape(p.codigo)}</span></div>${Number(p.activo)?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo globalmente</span>'}</article>`).join(''):'<div class="empty-state">Este rol no tiene permisos de esta categoría.</div>';
}
function cerrarModalPermisos(){modalPermisosRol.close();}
buscarRoles.addEventListener('input',renderTablaRoles);
buscarPermisosRol.addEventListener('input',filtrarSelectorPermisos);
buscarPermisosModal.addEventListener('input',renderPermisosModal);
cargarRoles();
</script>
<?php include 'footer.php'; ?>
