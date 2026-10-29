<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'pacientes.editar');
$tituloPagina = 'Pacientes';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Gestión clínica</span><h1>Pacientes</h1><p>Datos personales, procedencia e identificación clínica en un solo lugar.</p></div>
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center">
        <input id="filtroPacientes" type="search" placeholder="Buscar nombre, documento o código…" style="min-width:260px; width:auto">
        <button type="button" class="btn-secondary" onclick="exportarLista('xls')" title="Descargar en Excel los pacientes de la lista">Excel</button>
        <button type="button" class="btn-secondary" onclick="exportarLista('pdf')" title="Generar un PDF con los pacientes de la lista">PDF</button>
    </div>
</div>

<div class="<?= $puedeEditar ? 'grid-2' : 'grid' ?>">
<?php if ($puedeEditar): ?>
    <section class="card">
        <div class="card-header"><div><h2>Datos del paciente</h2><p class="help-text">Los campos marcados son obligatorios.</p></div></div>
        <form id="frmPostulante">
            <input type="hidden" name="id_paciente" id="id_paciente">
            <input type="hidden" name="url_foto" id="url_foto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="ci">Documento *</label>
                <div style="display:flex; gap:6px">
                    <input name="ci" id="ci" maxlength="30" required>
                    <button type="button" class="btn-secondary" id="btnBuscarCedula" style="flex-shrink:0" onclick="buscarCedula()" title="Buscar en el sistema y en el padrón de cédulas">Buscar</button>
                </div>
            </div>
            <div class="campo"><label for="nombre">Nombre y apellido *</label><input name="nombre" id="nombre" data-uppercase maxlength="250" required></div>
            <div class="campo"><label for="codigo">Código</label><input name="codigo" id="codigo" maxlength="20" placeholder="Automático: orden-id"></div>
            <div class="field-grid">
                <div class="campo"><label for="fecha_nacimiento">Fecha de nacimiento *</label><input type="date" name="fecha_nacimiento" id="fecha_nacimiento" max="<?= date('Y-m-d') ?>" required></div>
                <div class="campo"><label for="es_femenino">Sexo *</label><select name="es_femenino" id="es_femenino" required><option value="">Seleccione</option><option value="0">Masculino</option><option value="1">Femenino</option></select></div>
                <div class="campo"><label for="id_escuela">Escuela</label><select name="id_escuela" id="id_escuela"><option value="">Cargando…</option></select></div>
                <div class="campo"><label for="grupo_sanguineo">Grupo sanguíneo</label><select name="grupo_sanguineo" id="grupo_sanguineo"><option value="">Sin especificar</option><option>A Rh Positivo</option><option>A Rh Negativo</option><option>B Rh Positivo</option><option>B Rh Negativo</option><option>AB Rh Positivo</option><option>AB Rh Negativo</option><option>O Rh Positivo</option><option>O Rh Negativo</option></select></div>
                <div class="campo"><label for="id_pais">País</label><select name="id_pais" id="id_pais"><option value="">Cargando…</option></select></div>
                <div class="campo"><label for="telefono">Teléfono internacional</label><input type="tel" name="telefono" id="telefono" placeholder="+595981123456" pattern="\+[1-9][0-9]{7,14}"></div>
            </div>
            <!--
                Foto del paciente oculta por ahora (solo en pantalla). El backend sigue soportándola
                (backend/uploadDAO.php, tipo "paciente_foto") y el campo oculto url_foto conserva la
                foto que ya tenga el paciente. Para volver a mostrarla, restaurar este bloque:
                <div class="campo"><label for="foto_paciente">Foto</label><img id="vista_foto" class="preview-image" alt="Vista previa" hidden><input type="file" id="foto_paciente" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG o WEBP. Se sube al guardar.</small></div>
                y las líneas comentadas con "foto" en el script de abajo.
            -->
            <div class="form-actions"><button type="submit">Guardar paciente</button><button type="button" class="btn-secondary" onclick="limpiar()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
<?php endif; ?>

    <section class="card">
        <div class="card-header" style="flex-wrap:wrap">
            <div><h2>Pacientes activos</h2><p class="help-text" id="resumen">Cargando registros…</p></div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center">
                <input id="filtroFecha" type="date" title="Fecha de alta" aria-label="Filtrar por fecha de alta" style="width:auto">
                <button type="button" class="btn-secondary btn-small" onclick="filtroFecha.value=''; renderPacientes()">Todas las fechas</button>
            </div>
        </div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>ID / orden</th><th>Paciente</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Rh</th><th>Acciones</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="7" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<script>
const puedeEditarPacientes = <?= $puedeEditar ? 'true' : 'false' ?>;
let pacientes = [], escuelas = [], paises = [];
let pacientesVisibles = []; // lo que se ve en la tabla con los filtros aplicados
async function cargarCatalogos() {
    [escuelas, paises] = await Promise.all([
        Salus.json('backend/designacionesDAO.php?q=listar'), Salus.json('backend/referencialesDAO.php?q=paises')
    ]);
    if (puedeEditarPacientes) {
        id_escuela.innerHTML='<option value="">Sin especificar</option>'+escuelas.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)} · ${Salus.escape(d.periodo)}</option>`).join('');
        id_pais.innerHTML=paises.map(p=>`<option value="${p.id_pais}">${Salus.escape(p.nombre)} (${Salus.escape(p.prefijo_telefonico)})</option>`).join('');
        seleccionarParaguay();
    }
}
// El país siempre arranca en Paraguay (también al limpiar el formulario).
function seleccionarParaguay(){
    const paraguay=paises.find(p=>p.codigo_iso2==='PY');
    if(paraguay) id_pais.value=paraguay.id_pais;
}
async function cargar() {
    try { pacientes=await Salus.json('backend/postulantesDAO.php?q=listar'); renderPacientes(); }
    catch(error){ Salus.toast(error.message,'error'); }
}
function renderPacientes() {
    const termino=(filtroPacientes.value||'').trim().toLowerCase();
    const fecha=filtroFecha.value;
    const datos=pacientes.filter(p=>(!fecha||p.fecha_alta===fecha)
        &&(!termino||`${p.nombre} ${p.ci} ${p.codigo_generado||''}`.toLowerCase().includes(termino)));
    pacientesVisibles=datos;
    resumen.textContent=`${datos.length} de ${pacientes.length} paciente(s)`;
    tabla.innerHTML=datos.length?datos.map(p=>`<tr>
        <td><strong>${Number(p.id_paciente)}</strong><br><small title="Orden del día de alta">Orden ${Salus.escape(p.orden_dia)}</small></td>
        <td><strong>${Salus.escape(p.nombre)}</strong><br><small>${Salus.escape(p.codigo_generado||'')}</small></td>
        <td>${Salus.escape(p.ci)}</td><td>${Salus.escape(p.edad)} años<br><small>${Salus.escape(p.sexo)}</small></td>
        <td>${Salus.escape(p.escuela||'—')}</td><td><span class="badge badge-muted">${Salus.escape(formatoGrupoSanguineo(p.grupo_sanguineo)||'—')}</span></td>
        <td><div class="table-actions">${puedeEditarPacientes?`<button class="btn-small btn-secondary" onclick="editar(${p.id_paciente})">Editar</button><button class="btn-small btn-danger" onclick="eliminar(${p.id_paciente})">Desactivar</button>`:''}</div></td>
    </tr>`).join(''):'<tr><td colspan="7" class="empty-state">No se encontraron pacientes.</td></tr>';
}
function editar(id) {
    const p=pacientes.find(item=>Number(item.id_paciente)===Number(id)); if(!p)return;
    id_paciente.value=p.id_paciente; nombre.value=p.nombre; codigo.value=p.codigo||''; ci.value=p.ci;
    fecha_nacimiento.value=p.fecha_nacimiento; es_femenino.value=p.es_femenino; grupo_sanguineo.value=p.grupo_sanguineo||'';
    id_escuela.value=p.id_escuela||''; telefono.value=p.telefono||''; url_foto.value=p.url_foto||'';
    if(p.id_pais) id_pais.value=p.id_pais; else seleccionarParaguay();
    // Foto oculta por ahora: vista_foto.src=p.url_foto||''; vista_foto.hidden=!p.url_foto;
    nombre.focus();
}
// Exporta a Excel o PDF exactamente los pacientes que se ven en la lista.
function exportarLista(formato){
    if(!pacientesVisibles.length){Salus.toast('No hay pacientes en la lista para exportar.','warn');return;}
    const filtros=[];
    if(filtroPacientes.value.trim()) filtros.push('búsqueda "'+filtroPacientes.value.trim()+'"');
    if(filtroFecha.value) filtros.push('fecha de alta '+filtroFecha.value.split('-').reverse().join('/'));
    const form=document.createElement('form');
    form.method='POST'; form.action='backend/pacientesExportar.php';
    if(formato==='pdf') form.target='_blank';
    const campos={formato, ids:pacientesVisibles.map(p=>p.id_paciente).join(','), filtro:filtros.join(', '), csrf_token:Salus.csrf};
    Object.entries(campos).forEach(([name,value])=>{
        const input=document.createElement('input'); input.type='hidden'; input.name=name; input.value=value; form.appendChild(input);
    });
    document.body.appendChild(form); form.submit(); form.remove();
}
// Busca la cédula primero en nuestros pacientes y, si no está, en el padrón.
// Solo completa los datos que vengan bien; todo se puede corregir a mano después.
// Los avisos salen como pop-up; si se completó todo desde el padrón no se avisa nada.
async function buscarCedula(){
    const cedula=ci.value.trim();
    if(!cedula){Salus.toast('Ingrese un número de cédula.','warn');ci.focus();return;}
    btnBuscarCedula.disabled=true;
    try{
        const r=await Salus.json('backend/cedulasDAO.php?q=buscar&cedula='+encodeURIComponent(cedula));
        if(!r.encontrado){Salus.toast('No se encontró la cédula. Cargue los datos a mano.','warn');return;}

        if(r.fuente==='sistema'){
            if(Number(id_paciente.value)===r.id_paciente) return; // ya se está editando este paciente
            if(r.activo&&pacientes.some(p=>Number(p.id_paciente)===r.id_paciente)){
                editar(r.id_paciente);
                Salus.toast('Este paciente ya está registrado (ID '+r.id_paciente+'). Se cargaron sus datos para editar.','warn');
            }else{
                id_paciente.value='';
                nombre.value=r.nombre||''; fecha_nacimiento.value=r.fecha_nacimiento||''; es_femenino.value=r.sexo==='F'?'1':'0';
                Salus.toast('Esta cédula pertenece a un paciente desactivado (ID '+r.id_paciente+'). Reactívelo antes de volver a cargarlo.','warn');
            }
            return;
        }

        const faltantes=[];
        if(r.nombre) nombre.value=r.nombre; else faltantes.push('nombre');
        if(r.fecha_nacimiento) fecha_nacimiento.value=r.fecha_nacimiento; else faltantes.push('fecha de nacimiento');
        if(r.sexo) es_femenino.value=r.sexo==='F'?'1':'0'; else faltantes.push('sexo');
        let texto='';
        if(faltantes.length===3) texto='El padrón no tiene datos útiles para esta cédula.';
        else if(faltantes.length) texto='Falta: '+faltantes.join(', ')+'.';
        if(r.origen&&r.origen!=='principal') texto+=' Registrado como '+({padre:'padre',madre:'madre',conyuge:'cónyuge'}[r.origen]||r.origen)+' de otra persona: revise los datos.';
        if(texto.trim()) Salus.toast(texto.trim(),'warn');
    }catch(error){Salus.toast(error.message,'error');}
    finally{btnBuscarCedula.disabled=false;}
}
function limpiar(){
    frmPostulante.reset();id_paciente.value='';url_foto.value='';mensaje.textContent='';
    seleccionarParaguay();
    // Foto oculta por ahora: vista_foto.src='';vista_foto.hidden=true;
}
async function eliminar(id){
    if(!confirm('¿Desactivar este paciente?'))return;
    try{await Salus.json('backend/postulantesDAO.php?q=eliminar',{method:'POST',body:Salus.form({id})});Salus.toast('Paciente desactivado.');cargar();}
    catch(error){Salus.toast(error.message,'error');}
}
if (puedeEditarPacientes) frmPostulante.addEventListener('submit',async event=>{
    event.preventDefault(); mensaje.textContent='';
    try{
        await Salus.json('backend/postulantesDAO.php?q=guardar',{method:'POST',body:new FormData(frmPostulante)});
        // Foto oculta por ahora. Para reactivarla, guardar el resultado de arriba en "resultado" y descomentar:
        // if(foto_paciente.files[0]){
        //     const foto=Salus.form({tipo:'paciente_foto',id_paciente:resultado.id_paciente});foto.append('archivo',foto_paciente.files[0]);
        //     await Salus.json('backend/uploadDAO.php',{method:'POST',body:foto});
        // }
        Salus.toast('Paciente guardado.');limpiar();cargar();
    }catch(error){mensaje.textContent=error.message;}
});
// Enter en Documento busca en el padrón en vez de guardar el formulario.
if (puedeEditarPacientes) ci.addEventListener('keydown',event=>{
    if(event.key==='Enter'){event.preventDefault();buscarCedula();}
});
filtroPacientes.addEventListener('input',renderPacientes);
filtroFecha.addEventListener('change',renderPacientes);
(async()=>{try{await cargarCatalogos();await cargar();}catch(error){Salus.toast(error.message,'error');}})();
</script>
<?php include 'footer.php'; ?>
