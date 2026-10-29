<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'menus.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'menus.editar');
$tituloPagina = 'Menús y accesos';
include 'menu.php';
?>

<div class="page-header">
    <div>
        <span class="eyebrow">Seguridad · Navegación</span>
        <h1>Menús y submenús</h1>
        <p>Cada nodo genera su propio permiso <span class="code">menu.padre.hijo</span>.</p>
    </div>
    <div class="header-actions">
        <div class="search-box"><input type="search" id="buscarMenus" placeholder="Buscar menú, ruta o URL…" aria-label="Buscar menús"></div>
        <?php if ($puedeEditar): ?><button type="button" onclick="nuevoMenu()">+ Nuevo menú</button><?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <section class="card" id="panelFormulario">
        <div class="card-header"><div><h2>Configuración del nodo</h2><p class="help-text">Puede tener URL, hijos o ambas cosas.</p></div></div>
        <form id="formMenu">
            <input type="hidden" name="id_menu" id="id_menu">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo">
                <label for="id_menu_padre">Menú padre</label>
                <select name="id_menu_padre" id="id_menu_padre"><option value="">Sin padre (nivel principal)</option></select>
            </div>
            <div class="field-grid">
                <div class="campo full">
                    <label for="segmento">Segmento de ruta</label>
                    <input name="segmento" id="segmento" maxlength="40" pattern="[a-z0-9_-]{2,40}" placeholder="usuarios" required>
                    <small>El permiso se arma automáticamente.</small>
                </div>
                <div class="campo full">
                    <label for="etiqueta">Texto visible</label>
                    <input name="etiqueta" id="etiqueta" maxlength="100" placeholder="USUARIOS" required>
                </div>
                <div class="campo">
                    <label for="url">URL interna (opcional)</label>
                    <input name="url" id="url" maxlength="255" placeholder="usuarios.php">
                </div>
                <div class="campo">
                    <label for="icono">Icono o emoji (opcional)</label>
                    <input name="icono" id="icono" maxlength="100" placeholder="👥">
                </div>
                <div class="campo">
                    <label for="orden">Orden</label>
                    <input type="number" name="orden" id="orden" min="0" max="65535" value="0">
                </div>
                <div class="campo">
                    <label>Permiso resultante</label>
                    <div class="code" id="permisoPrevio">menu.segmento</div>
                </div>
            </div>
            <div class="campo">
                <label>Roles que pueden verlo</label>
                <div id="listaRoles" class="checks"></div>
                <small>ADMIN se conserva siempre. Si es visible para todos, los roles dejan de ser necesarios para mostrarlo.</small>
            </div>
            <div class="notice"><strong>Regla de visibilidad:</strong> si no marca “visible para todos”, solamente verán este nodo los usuarios con un rol que tenga su permiso <span class="code">menu.*</span>.</div>
            <div class="checks">
                <label><input type="checkbox" name="visible_todos" id="visible_todos" value="1"> Visible para todos los usuarios autenticados</label>
                <label><input type="checkbox" name="activo" id="activo" value="1" checked> Menú activo</label>
            </div>
            <?php if ($puedeEditar): ?>
                <div class="form-actions"><button type="submit">Guardar menú</button><button class="btn-secondary" type="button" onclick="limpiarMenu()">Cancelar</button></div>
            <?php endif; ?>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><div><h2>Árbol configurado</h2><p class="help-text" id="resumenMenus">Cargando menús…</p></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Ruta / etiqueta</th><th>Destino</th><th>Acceso</th><th>Estado</th><th></th></tr></thead>
                <tbody id="tablaMenus"><tr><td colspan="5" class="empty-state">Cargando menús…</td></tr></tbody>
            </table>
        </div>
    </section>
</div>

<script>
const puedeEditarMenus = <?= $puedeEditar ? 'true' : 'false' ?>;
let menus = [], roles = [];

async function cargarMenus() {
    try {
        const datos = await Salus.json('backend/menusAdminDAO.php?q=listar');
        menus = datos.menus; roles = datos.roles;
        renderPadres(); renderRoles(); renderTabla(); actualizarPermisoPrevio();
    } catch (error) { Salus.toast(error.message, 'error'); }
}

function renderPadres() {
    const actual = id_menu_padre.value;
    id_menu_padre.innerHTML = '<option value="">Sin padre (nivel principal)</option>' + menus
        .filter(m => Number(m.id_menu) !== Number(id_menu.value || 0))
        .map(m => `<option value="${m.id_menu}">${Salus.escape(m.codigo)} — ${Salus.escape(m.etiqueta)}</option>`).join('');
    id_menu_padre.value = actual;
}

function renderRoles(seleccionados = []) {
    const ids = seleccionados.map(Number);
    listaRoles.innerHTML = roles.filter(r => Number(r.activo) === 1).map(r =>
        `<label><input type="checkbox" name="roles[]" value="${r.id_rol}" ${ids.includes(Number(r.id_rol)) ? 'checked' : ''} ${r.codigo === 'ADMIN' ? 'checked disabled' : ''}> <span><strong>${Salus.escape(r.nombre)}</strong><br><small>${Salus.escape(r.codigo)}</small></span></label>`
    ).join('');
}

function renderTabla() {
    const termino = buscarMenus.value.trim().toLowerCase();
    const datos = menus.filter(m => !termino || `${m.codigo} ${m.etiqueta} ${m.url || ''} ${m.permiso_codigo || ''}`.toLowerCase().includes(termino));
    resumenMenus.textContent = `${datos.length} de ${menus.length} menú(s)`;
    if (!datos.length) {
        tablaMenus.innerHTML = '<tr><td colspan="5" class="empty-state">No se encontraron menús.</td></tr>'; return;
    }
    tablaMenus.innerHTML = datos.map(m => {
        const nivel = (m.codigo.match(/\./g) || []).length;
        const acceso = Number(m.visible_todos) ? '<span class="badge badge-success">Todos</span>' : `${m.rol_ids.length} rol(es)`;
        let estado = '<span class="badge badge-success">Disponible</span>';
        if (!Number(m.activo)) estado = '<span class="badge badge-danger">Menú inactivo</span>';
        else if (!Number(m.permiso_activo)) estado = '<span class="badge badge-danger">Permiso bloqueado globalmente</span><br><small>Actívelo desde Permisos</small>';
        return `<tr>
            <td style="padding-left:${13 + nivel * 20}px"><strong>${nivel ? '↳ ' : ''}${Salus.escape(m.etiqueta)}</strong><br><span class="code">${Salus.escape(m.permiso_codigo || '')}</span></td>
            <td>${m.url ? `<a href="${Salus.escape(m.url)}">${Salus.escape(m.url)}</a>` : '<span class="help-text">Solo contenedor</span>'}</td>
            <td>${acceso}</td><td>${estado}</td>
            <td>${puedeEditarMenus ? `<button class="btn-small btn-secondary" type="button" onclick="editarMenu(${m.id_menu})">Editar</button>` : ''}</td>
        </tr>`;
    }).join('');
}

function editarMenu(id) {
    const m = menus.find(item => Number(item.id_menu) === Number(id));
    if (!m) return;
    id_menu.value = m.id_menu; renderPadres(); id_menu_padre.value = m.id_menu_padre || '';
    segmento.value = m.segmento; etiqueta.value = m.etiqueta; url.value = m.url || '';
    icono.value = m.icono || ''; orden.value = m.orden;
    visible_todos.checked = Number(m.visible_todos) === 1; activo.checked = Number(m.activo) === 1;
    renderRoles(m.rol_ids);
    actualizarPermisoPrevio(); document.getElementById('panelFormulario').scrollIntoView({behavior:'smooth'});
}

function limpiarMenu() {
    formMenu.reset(); id_menu.value = ''; activo.checked = true;
    renderPadres(); renderRoles(); actualizarPermisoPrevio(); mensaje.textContent = '';
}
function nuevoMenu() { limpiarMenu(); segmento.focus(); }
function actualizarPermisoPrevio() {
    const padre = menus.find(m => Number(m.id_menu) === Number(id_menu_padre.value));
    const ruta = [padre?.codigo, segmento.value.trim().toLowerCase()].filter(Boolean).join('.');
    permisoPrevio.textContent = 'menu.' + (ruta || 'segmento');
}
id_menu_padre.addEventListener('change', actualizarPermisoPrevio);
segmento.addEventListener('input', actualizarPermisoPrevio);
buscarMenus.addEventListener('input', renderTabla);
formMenu.addEventListener('submit', async event => {
    event.preventDefault(); mensaje.textContent = '';
    const data = new FormData(formMenu);
    document.querySelectorAll('#listaRoles input:checked:disabled').forEach(input => data.append('roles[]', input.value));
    try {
        const resultado = await Salus.json('backend/menusAdminDAO.php?q=guardar', {method:'POST', body:data});
        Salus.toast(resultado.mensaje); limpiarMenu(); await cargarMenus();
    } catch (error) { mensaje.textContent = error.message; }
});
cargarMenus();
</script>

<?php include 'footer.php'; ?>
