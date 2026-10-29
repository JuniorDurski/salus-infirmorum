<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'titulos.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'titulos.editar');
$tituloPagina = 'Títulos profesionales';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Referenciales</span><h1>Títulos profesionales</h1><p>Configure la denominación, abreviatura y variante de género.</p></div>
    <div class="search-box"><input type="search" id="buscarTitulos" placeholder="Buscar título o abreviatura…"></div>
</div>

<main class="<?= $puedeEditar ? 'seguridad-grid' : 'grid' ?>">
    <?php if ($puedeEditar): ?>
    <section class="panel-seguridad" id="panelTitulo">
        <h2>Datos del título</h2>
        <form id="formTitulo">
            <input type="hidden" name="id_titulo" id="id_titulo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="titulo">Título</label><input name="titulo" id="titulo" maxlength="100" placeholder="Doctora" required></div>
            <div class="campo"><label for="abreviatura">Abreviatura</label><input name="abreviatura" id="abreviatura" maxlength="20" placeholder="Dra." required></div>
            <div class="campo"><label for="es_femenino">Aplicación</label><select name="es_femenino" id="es_femenino"><option value="0">Masculino</option><option value="1">Femenino</option></select><small>Falso/0 corresponde a hombre; verdadero/1 corresponde a mujer.</small></div>
            <div class="form-actions"><button type="submit">Guardar título</button><button type="button" class="btn-secondary" onclick="limpiarTitulo()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="panel-seguridad">
        <div class="card-header"><div><h2>Títulos registrados</h2><p class="help-text" id="resumenTitulos">Cargando…</p></div><select id="estadoTitulo" style="width:auto"><option value="activos">Activos</option><option value="todos">Todos</option><option value="inactivos">Inactivos</option></select></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Título</th><th>Abreviatura</th><th>Aplicación</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaTitulos"><tr><td colspan="5" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</main>

<script>
const puedeEditarTitulos=<?= $puedeEditar?'true':'false' ?>;let titulos=[];
async function cargarTitulos(){try{titulos=await Salus.json('backend/referencialesAdminDAO.php?entidad=titulos&q=listar');renderTitulos();}catch(error){Salus.toast(error.message,'error');}}
function renderTitulos(){const termino=buscarTitulos.value.trim().toLowerCase(),estado=estadoTitulo.value;const datos=titulos.filter(t=>(estado==='todos'||(estado==='activos'&&Number(t.activo))||(estado==='inactivos'&&!Number(t.activo)))&&(!termino||`${t.titulo} ${t.abreviatura}`.toLowerCase().includes(termino)));resumenTitulos.textContent=`${datos.length} de ${titulos.length} título(s)`;tablaTitulos.innerHTML=datos.length?datos.map(t=>`<tr><td><strong>${Salus.escape(t.titulo)}</strong></td><td><span class="code">${Salus.escape(t.abreviatura)}</span></td><td>${Number(t.es_femenino)?'<span class="badge badge-muted">Mujer</span>':'<span class="badge badge-muted">Hombre</span>'}</td><td>${Number(t.activo)?'<span class="badge badge-success">Activo</span>':'<span class="badge badge-danger">Inactivo</span>'}</td><td><div class="table-actions">${puedeEditarTitulos?`<button class="btn-small btn-secondary" onclick="editarTitulo(${t.id_titulo})">Editar</button><button class="btn-small ${Number(t.activo)?'btn-danger':'btn-secondary'}" onclick="estadoTituloRegistro(${t.id_titulo},${Number(t.activo)?0:1})">${Number(t.activo)?'Desactivar':'Activar'}</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="5" class="empty-state">No se encontraron títulos.</td></tr>';}
function editarTitulo(id){const t=titulos.find(x=>Number(x.id_titulo)===Number(id));if(!t)return;id_titulo.value=t.id_titulo;titulo.value=t.titulo;abreviatura.value=t.abreviatura;es_femenino.value=t.es_femenino;panelTitulo.scrollIntoView({behavior:'smooth'});}
function limpiarTitulo(){formTitulo.reset();id_titulo.value='';mensaje.textContent='';}
if(puedeEditarTitulos)formTitulo.addEventListener('submit',async event=>{event.preventDefault();try{await Salus.json('backend/referencialesAdminDAO.php?entidad=titulos&q=guardar',{method:'POST',body:new FormData(formTitulo)});Salus.toast('Título guardado.');limpiarTitulo();cargarTitulos();}catch(error){mensaje.textContent=error.message;}});
async function estadoTituloRegistro(id,activo){try{await Salus.json('backend/referencialesAdminDAO.php?entidad=titulos&q=cambiar_estado',{method:'POST',body:Salus.form({id_titulo:id,activo})});Salus.toast(activo?'Título activado.':'Título desactivado.');cargarTitulos();}catch(error){Salus.toast(error.message,'error');}}
buscarTitulos.addEventListener('input',renderTitulos);estadoTitulo.addEventListener('change',renderTitulos);cargarTitulos();
</script>
<?php include 'footer.php'; ?>
