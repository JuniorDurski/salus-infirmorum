<?php
include 'conexion.php';
$usuarioAutenticado = requerirAlgunPermiso($conexion, ['postulantes_estudios.ver', 'postulantes_estudios.editar']);
$puedeEditar = tienePermiso($usuarioAutenticado, 'postulantes_estudios.editar');
$tituloPagina = 'Estudios referenciales de postulantes';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Atenciones · Catálogo</span><h1>Estudios referenciales</h1><p>Define los estudios que pueden asignarse a los postulantes.</p></div>
    <div class="header-actions"><input class="search-box" type="search" id="buscar" placeholder="Buscar estudio…"></div>
</div>

<div class="<?= $puedeEditar ? 'grid-2' : 'grid' ?>">
<?php if ($puedeEditar): ?>
    <section class="card">
        <div class="card-header"><div><h2>Datos del estudio</h2><p class="help-text">Es un catálogo referencial, no un resultado clínico.</p></div></div>
        <form id="formEstudio">
            <input type="hidden" name="id_estudio" id="id_estudio">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="estudio_referencial">Nombre *</label><input name="estudio_referencial" id="estudio_referencial" maxlength="180" required placeholder="Ej.: Hemograma"></div>
            <div class="form-actions"><button type="submit">Guardar estudio</button><button type="button" class="btn-secondary" onclick="limpiarEstudio()">Cancelar</button></div>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
<?php endif; ?>
    <section class="card">
        <div class="card-header"><div><h2>Catálogo</h2><p class="help-text" id="resumen">Cargando…</p></div></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Estudio</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="tabla"></tbody></table></div>
    </section>
</div>
<script>
const puedeEditarEstudios = <?= $puedeEditar ? 'true' : 'false' ?>;
let estudiosReferenciales = [];
async function cargarEstudios(){
    try { const r=await Salus.json('backend/estudiosReferencialesDAO.php?q=listar'+(puedeEditarEstudios?'&todos=1':'')); estudiosReferenciales=r.estudios; renderEstudios(); }
    catch(e){ Salus.toast(e.message,'error'); }
}
function renderEstudios(){
    const t=buscar.value.trim().toLowerCase(); const datos=estudiosReferenciales.filter(e=>!t||e.estudio_referencial.toLowerCase().includes(t));
    resumen.textContent=`${datos.length} de ${estudiosReferenciales.length} estudio(s)`;
    tabla.innerHTML=datos.length?datos.map(e=>`<tr><td><strong>${Salus.escape(e.estudio_referencial)}</strong></td><td><span class="badge ${Number(e.activo)?'badge-success':'badge-danger'}">${Number(e.activo)?'Activo':'Inactivo'}</span></td><td><div class="table-actions">${puedeEditarEstudios?`<button class="btn-small btn-secondary" onclick="editarEstudio(${e.id_estudio})">Editar</button><button class="btn-small ${Number(e.activo)?'btn-danger':'btn-secondary'}" onclick="estadoEstudio(${e.id_estudio},${Number(e.activo)?0:1})">${Number(e.activo)?'Desactivar':'Activar'}</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="3" class="empty-state">No hay estudios.</td></tr>';
}
function editarEstudio(id){const e=estudiosReferenciales.find(x=>Number(x.id_estudio)===Number(id));if(!e)return;id_estudio.value=e.id_estudio;estudio_referencial.value=e.estudio_referencial;estudio_referencial.focus();}
function limpiarEstudio(){if(!puedeEditarEstudios)return;formEstudio.reset();id_estudio.value='';mensaje.textContent='';}
async function estadoEstudio(id,activo){try{await Salus.json('backend/estudiosReferencialesDAO.php?q=estado',{method:'POST',body:Salus.form({id_estudio:id,activo})});await cargarEstudios();}catch(e){Salus.toast(e.message,'error');}}
if(puedeEditarEstudios)formEstudio.addEventListener('submit',async ev=>{ev.preventDefault();try{const r=await Salus.json('backend/estudiosReferencialesDAO.php?q=guardar',{method:'POST',body:new FormData(formEstudio)});Salus.toast(r.mensaje);limpiarEstudio();await cargarEstudios();}catch(e){mensaje.textContent=e.message;}});
buscar.addEventListener('input',renderEstudios);cargarEstudios();
</script>
<?php include 'footer.php'; ?>
