<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'permisos.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'permisos.editar');
$tituloPagina = 'Permisos';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Seguridad</span><h1>Permisos</h1><p>Controle capacidades funcionales y bloqueos globales de navegación.</p></div>
    <div class="search-box"><input type="search" id="buscarPermisos" placeholder="Buscar código, nombre o descripción…" aria-label="Buscar permisos"></div>
</div>

<main class="seguridad-grid">
    <section class="panel-seguridad" id="panelPermiso">
        <h2>Datos del permiso</h2>
        <form id="formPermiso">
            <input type="hidden" name="id_permiso" id="id_permiso">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="codigo">Código</label><input name="codigo" id="codigo" required><small>No puede cambiarse después de crear el permiso.</small></div>
            <div class="campo"><label for="nombre">Nombre</label><input name="nombre" id="nombre" required></div>
            <div class="campo"><label for="descripcion">Descripción</label><textarea name="descripcion" id="descripcion"></textarea></div>
            <div class="form-actions">
                <?php if ($puedeEditar): ?><button type="submit">Guardar permiso</button><?php endif; ?>
                <button class="btn-secondary" type="button" onclick="limpiarPermiso()">Cancelar</button>
            </div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>

    <section class="panel-seguridad">
        <div class="card-header">
            <div><h2>Permisos configurados</h2><p class="help-text" id="resumenPermisos">Cargando…</p></div>
            <select id="tipoPermiso" aria-label="Filtrar tipo" style="width:auto"><option value="todos">Todos</option><option value="funcional">Funcionales</option><option value="menu">Menús</option></select>
        </div>
        <div class="notice"><strong>Estado global:</strong> si desactiva un permiso <span class="code">menu.*</span>, ese menú se oculta para todos, aunque un rol lo tenga asignado.</div>
        <div class="table-wrap"><table class="tabla-seguridad">
            <thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaPermisos"></tbody>
        </table></div>
    </section>
</main>

<script>
const puedeEditarPermisos=<?= $puedeEditar?'true':'false' ?>;
let permisos=[];
async function cargarPermisos(){
    try{permisos=await Salus.json('backend/permisosDAO.php?q=listar');renderPermisos();}
    catch(error){Salus.toast(error.message,'error');}
}
function renderPermisos(){
    const termino=buscarPermisos.value.trim().toLowerCase(),tipo=tipoPermiso.value;
    const datos=permisos.filter(p=>(tipo==='todos'||(tipo==='menu'&&p.codigo.startsWith('menu.'))||(tipo==='funcional'&&!p.codigo.startsWith('menu.')))&&(!termino||`${p.codigo} ${p.nombre} ${p.descripcion||''}`.toLowerCase().includes(termino)));
    resumenPermisos.textContent=`${datos.length} de ${permisos.length} permiso(s)`;
    tablaPermisos.innerHTML=datos.length?datos.map(p=>`<tr>
        <td><span class="code">${Salus.escape(p.codigo)}</span>${p.codigo.startsWith('menu.')?'<br><span class="badge badge-muted">Menú</span>':''}</td><td><strong>${Salus.escape(p.nombre)}</strong></td><td>${Salus.escape(p.descripcion||'—')}</td>
        <td>${Number(p.activo)?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo globalmente</span>'}</td>
        <td><div class="table-actions">${puedeEditarPermisos?`<button class="btn-small btn-secondary" onclick="editarPermiso(${p.id_permiso})">Editar</button><button class="btn-small ${Number(p.activo)?'btn-danger':'btn-secondary'}" onclick="estadoPermiso(${p.id_permiso},${Number(p.activo)?0:1})">${Number(p.activo)?'Desactivar':'Activar'}</button>`:''}</div></td>
    </tr>`).join(''):'<tr><td colspan="5" class="empty-state">No se encontraron permisos.</td></tr>';
}
function editarPermiso(id){const p=permisos.find(x=>Number(x.id_permiso)===Number(id));if(!p)return;id_permiso.value=p.id_permiso;codigo.value=p.codigo;codigo.readOnly=true;nombre.value=p.nombre;descripcion.value=p.descripcion||'';panelPermiso.scrollIntoView({behavior:'smooth'});}
function limpiarPermiso(){formPermiso.reset();id_permiso.value='';codigo.readOnly=false;mensaje.textContent='';}
formPermiso.addEventListener('submit',async event=>{event.preventDefault();try{await Salus.json('backend/permisosDAO.php?q=guardar',{method:'POST',body:new FormData(formPermiso)});Salus.toast('Permiso guardado.');limpiarPermiso();cargarPermisos();}catch(error){mensaje.textContent=error.message;}});
async function estadoPermiso(id,activo){
    const permiso=permisos.find(p=>Number(p.id_permiso)===Number(id));
    if(!activo&&permiso?.codigo.startsWith('menu.')&&!confirm('Este menú se ocultará para todos los roles. ¿Continuar?'))return;
    try{await Salus.json('backend/permisosDAO.php?q=cambiar_estado',{method:'POST',body:Salus.form({id_permiso:id,activo})});Salus.toast(activo?'Permiso activado.':'Permiso desactivado globalmente.');cargarPermisos();}catch(error){Salus.toast(error.message,'error');}
}
buscarPermisos.addEventListener('input',renderPermisos);tipoPermiso.addEventListener('change',renderPermisos);
cargarPermisos();
</script>
<?php include 'footer.php'; ?>
