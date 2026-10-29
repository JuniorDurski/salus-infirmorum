<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'universidades.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'universidades.editar');
$tituloPagina = 'Universidades';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Referenciales</span><h1>Universidades</h1><p>Catálogo de instituciones nacionales e internacionales.</p></div>
    <div class="search-box"><input type="search" id="buscarUniversidades" placeholder="Buscar universidad, sigla o país…"></div>
</div>

<main class="<?= $puedeEditar ? 'seguridad-grid' : 'grid' ?>">
    <?php if ($puedeEditar): ?>
    <section class="panel-seguridad" id="panelUniversidad">
        <h2>Datos de la universidad</h2>
        <form id="formUniversidad">
            <input type="hidden" name="id_universidad" id="id_universidad">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="id_pais">País</label><select name="id_pais" id="id_pais" required><option value="">Cargando…</option></select></div>
            <div class="campo"><label for="nombre">Nombre oficial</label><input name="nombre" id="nombre" data-uppercase maxlength="200" required></div>
            <div class="campo"><label for="abreviatura">Abreviatura</label><input name="abreviatura" id="abreviatura" data-uppercase maxlength="30" placeholder="UNA"></div>
            <div class="form-actions"><button type="submit">Guardar universidad</button><button type="button" class="btn-secondary" onclick="limpiarUniversidad()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="panel-seguridad">
        <div class="card-header"><div><h2>Universidades registradas</h2><p class="help-text" id="resumenUniversidades">Cargando…</p></div><select id="estadoUniversidad" style="width:auto"><option value="activas">Activas</option><option value="todas">Todas</option><option value="inactivas">Inactivas</option></select></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Universidad</th><th>Abreviatura</th><th>País</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaUniversidades"><tr><td colspan="5" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</main>

<script>
const puedeEditarUniversidades=<?= $puedeEditar?'true':'false' ?>;
let universidades=[],paises=[];
async function iniciarUniversidades(){
    try{
        [universidades,paises]=await Promise.all([Salus.json('backend/referencialesAdminDAO.php?entidad=universidades&q=listar'),Salus.json('backend/referencialesDAO.php?q=paises')]);
        if(puedeEditarUniversidades){id_pais.innerHTML='<option value="">Seleccione un país</option>'+paises.map(p=>`<option value="${p.id_pais}">${Salus.escape(p.nombre)}</option>`).join('');seleccionarParaguay();}
        renderUniversidades();
    }catch(error){Salus.toast(error.message,'error');}
}
function renderUniversidades(){
    const termino=buscarUniversidades.value.trim().toLowerCase(),estado=estadoUniversidad.value;
    const datos=universidades.filter(u=>(estado==='todas'||(estado==='activas'&&Number(u.activo))||(estado==='inactivas'&&!Number(u.activo)))&&(!termino||`${u.nombre} ${u.abreviatura||''} ${u.pais} ${u.codigo_iso2}`.toLowerCase().includes(termino)));
    resumenUniversidades.textContent=`${datos.length} de ${universidades.length} universidad(es)`;
    tablaUniversidades.innerHTML=datos.length?datos.map(u=>`<tr><td><strong>${Salus.escape(u.nombre)}</strong></td><td><span class="code">${Salus.escape(u.abreviatura||'—')}</span></td><td>${Salus.escape(u.pais)} <small>(${Salus.escape(u.codigo_iso2)})</small></td><td>${Number(u.activo)?'<span class="badge badge-success">Activa</span>':'<span class="badge badge-danger">Inactiva</span>'}</td><td><div class="table-actions">${puedeEditarUniversidades?`<button class="btn-small btn-secondary" onclick="editarUniversidad(${u.id_universidad})">Editar</button><button class="btn-small ${Number(u.activo)?'btn-danger':'btn-secondary'}" onclick="estadoUniversidadRegistro(${u.id_universidad},${Number(u.activo)?0:1})">${Number(u.activo)?'Desactivar':'Activar'}</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="5" class="empty-state">No se encontraron universidades.</td></tr>';
}
function seleccionarParaguay(){const paraguay=paises.find(p=>p.codigo_iso2==='PY');id_pais.value=paraguay?.id_pais||'';}
function editarUniversidad(id){const u=universidades.find(x=>Number(x.id_universidad)===Number(id));if(!u)return;id_universidad.value=u.id_universidad;id_pais.value=u.id_pais;nombre.value=u.nombre;abreviatura.value=u.abreviatura||'';panelUniversidad.scrollIntoView({behavior:'smooth'});}
function limpiarUniversidad(){formUniversidad.reset();id_universidad.value='';seleccionarParaguay();mensaje.textContent='';}
if(puedeEditarUniversidades)formUniversidad.addEventListener('submit',async event=>{event.preventDefault();try{await Salus.json('backend/referencialesAdminDAO.php?entidad=universidades&q=guardar',{method:'POST',body:new FormData(formUniversidad)});Salus.toast('Universidad guardada.');limpiarUniversidad();await recargarUniversidades();}catch(error){mensaje.textContent=error.message;}});
async function estadoUniversidadRegistro(id,activo){try{await Salus.json('backend/referencialesAdminDAO.php?entidad=universidades&q=cambiar_estado',{method:'POST',body:Salus.form({id_universidad:id,activo})});Salus.toast(activo?'Universidad activada.':'Universidad desactivada.');await recargarUniversidades();}catch(error){Salus.toast(error.message,'error');}}
async function recargarUniversidades(){universidades=await Salus.json('backend/referencialesAdminDAO.php?entidad=universidades&q=listar');renderUniversidades();}
buscarUniversidades.addEventListener('input',renderUniversidades);estadoUniversidad.addEventListener('change',renderUniversidades);
iniciarUniversidades();
</script>
<?php include 'footer.php'; ?>
