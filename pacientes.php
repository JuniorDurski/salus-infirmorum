<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'pacientes.editar');
$tituloPagina = 'Pacientes';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Gestión clínica</span><h1>Pacientes</h1><p>Datos personales, procedencia e identificación clínica en un solo lugar.</p></div>
    <div><input id="filtroPacientes" type="search" placeholder="Buscar nombre o documento…" style="min-width:280px"></div>
</div>

<div class="<?= $puedeEditar ? 'grid-2' : 'grid' ?>">
<?php if ($puedeEditar): ?>
    <section class="card">
        <div class="card-header"><div><h2>Datos del paciente</h2><p class="help-text">Los campos marcados son obligatorios.</p></div></div>
        <form id="frmPostulante">
            <input type="hidden" name="id_paciente" id="id_paciente">
            <input type="hidden" name="url_foto" id="url_foto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="nombre">Nombre y apellido *</label><input name="nombre" id="nombre" data-uppercase maxlength="250" required></div>
            <div class="field-grid">
                <div class="campo"><label for="ci">Documento *</label><input name="ci" id="ci" maxlength="30" required></div>
                <div class="campo"><label for="codigo">Código</label><input name="codigo" id="codigo" maxlength="20"></div>
                <div class="campo"><label for="fecha_nacimiento">Fecha de nacimiento *</label><input type="date" name="fecha_nacimiento" id="fecha_nacimiento" max="<?= date('Y-m-d') ?>" required></div>
                <div class="campo"><label for="es_femenino">Sexo *</label><select name="es_femenino" id="es_femenino" required><option value="">Seleccione</option><option value="0">Masculino</option><option value="1">Femenino</option></select></div>
                <div class="campo"><label for="id_escuela">Escuela</label><select name="id_escuela" id="id_escuela"><option value="">Cargando…</option></select></div>
                <div class="campo"><label for="grupo_sanguineo">Grupo sanguíneo</label><select name="grupo_sanguineo" id="grupo_sanguineo"><option value="">Sin especificar</option><option>A Rh Positivo</option><option>A Rh Negativo</option><option>B Rh Positivo</option><option>B Rh Negativo</option><option>AB Rh Positivo</option><option>AB Rh Negativo</option><option>O Rh Positivo</option><option>O Rh Negativo</option></select></div>
                <div class="campo"><label for="id_pais">País</label><select name="id_pais" id="id_pais"><option value="">Cargando…</option></select></div>
                <div class="campo"><label for="telefono">Teléfono internacional</label><input type="tel" name="telefono" id="telefono" placeholder="+595981123456" pattern="\+[1-9][0-9]{7,14}"></div>
            </div>
            <div class="campo"><label for="foto_paciente">Foto</label><img id="vista_foto" class="preview-image" alt="Vista previa" hidden><input type="file" id="foto_paciente" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG o WEBP. Se sube al guardar.</small></div>
            <div class="form-actions"><button type="submit">Guardar paciente</button><button type="button" class="btn-secondary" onclick="limpiar()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
<?php endif; ?>

    <section class="card">
        <div class="card-header"><div><h2>Pacientes activos</h2><p class="help-text" id="resumen">Cargando registros…</p></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Paciente</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Rh</th><th>Acciones</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="6" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<script>
const puedeEditarPacientes = <?= $puedeEditar ? 'true' : 'false' ?>;
let pacientes = [], escuelas = [], paises = [];
async function cargarCatalogos() {
    [escuelas, paises] = await Promise.all([
        Salus.json('backend/designacionesDAO.php?q=listar'), Salus.json('backend/referencialesDAO.php?q=paises')
    ]);
    if (puedeEditarPacientes) {
        id_escuela.innerHTML='<option value="">Sin especificar</option>'+escuelas.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)} · ${Salus.escape(d.periodo)}</option>`).join('');
        id_pais.innerHTML='<option value="">Sin especificar</option>'+paises.map(p=>`<option value="${p.id_pais}">${Salus.escape(p.nombre)} (${Salus.escape(p.prefijo_telefonico)})</option>`).join('');
    }
}
async function cargar() {
    try { pacientes=await Salus.json('backend/postulantesDAO.php?q=listar'); renderPacientes(); }
    catch(error){ Salus.toast(error.message,'error'); }
}
function renderPacientes() {
    const termino=(filtroPacientes.value||'').trim().toLowerCase();
    const datos=pacientes.filter(p=>!termino||`${p.nombre} ${p.ci} ${p.codigo||''}`.toLowerCase().includes(termino));
    resumen.textContent=`${datos.length} de ${pacientes.length} paciente(s)`;
    tabla.innerHTML=datos.length?datos.map(p=>`<tr>
        <td><strong>${Salus.escape(p.nombre)}</strong><br><small>${Salus.escape(p.codigo||'Sin código')}</small></td>
        <td>${Salus.escape(p.ci)}</td><td>${Salus.escape(p.edad)} años<br><small>${Salus.escape(p.sexo)}</small></td>
        <td>${Salus.escape(p.escuela||'—')}</td><td><span class="badge badge-muted">${Salus.escape(formatoGrupoSanguineo(p.grupo_sanguineo)||'—')}</span></td>
        <td><div class="table-actions">${puedeEditarPacientes?`<button class="btn-small btn-secondary" onclick="editar(${p.id_paciente})">Editar</button><button class="btn-small btn-danger" onclick="eliminar(${p.id_paciente})">Desactivar</button>`:''}</div></td>
    </tr>`).join(''):'<tr><td colspan="6" class="empty-state">No se encontraron pacientes.</td></tr>';
}
function editar(id) {
    const p=pacientes.find(item=>Number(item.id_paciente)===Number(id)); if(!p)return;
    id_paciente.value=p.id_paciente; nombre.value=p.nombre; codigo.value=p.codigo||''; ci.value=p.ci;
    fecha_nacimiento.value=p.fecha_nacimiento; es_femenino.value=p.es_femenino; grupo_sanguineo.value=p.grupo_sanguineo||'';
    id_escuela.value=p.id_escuela||''; id_pais.value=p.id_pais||''; telefono.value=p.telefono||''; url_foto.value=p.url_foto||'';
    vista_foto.src=p.url_foto||''; vista_foto.hidden=!p.url_foto; nombre.focus();
}
function limpiar(){frmPostulante.reset();id_paciente.value='';url_foto.value='';vista_foto.src='';vista_foto.hidden=true;mensaje.textContent='';}
async function eliminar(id){
    if(!confirm('¿Desactivar este paciente?'))return;
    try{await Salus.json('backend/postulantesDAO.php?q=eliminar',{method:'POST',body:Salus.form({id})});Salus.toast('Paciente desactivado.');cargar();}
    catch(error){Salus.toast(error.message,'error');}
}
if (puedeEditarPacientes) frmPostulante.addEventListener('submit',async event=>{
    event.preventDefault(); mensaje.textContent='';
    try{
        const resultado=await Salus.json('backend/postulantesDAO.php?q=guardar',{method:'POST',body:new FormData(frmPostulante)});
        if(foto_paciente.files[0]){
            const foto=Salus.form({tipo:'paciente_foto',id_paciente:resultado.id_paciente});foto.append('archivo',foto_paciente.files[0]);
            await Salus.json('backend/uploadDAO.php',{method:'POST',body:foto});
        }
        Salus.toast('Paciente guardado.');limpiar();cargar();
    }catch(error){mensaje.textContent=error.message;}
});
filtroPacientes.addEventListener('input',renderPacientes);
(async()=>{try{await cargarCatalogos();await cargar();}catch(error){Salus.toast(error.message,'error');}})();
</script>
<?php include 'footer.php'; ?>
