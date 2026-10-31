<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'pacientes.editar');
$anioActual = (int) $conexion->query('SELECT YEAR(CURDATE()) AS anio')->fetch_assoc()['anio'];
$tituloPagina = 'Arreadores y convenios';
include 'menu.php';
?>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Configuración</span>
        <h1>Arreadores y convenios</h1>
        <p>Administre los arreadores y las tarifas por escuela para <?= $anioActual ?>.</p>
    </div>
    <?php if ($puedeEditar): ?>
    <div class="header-actions">
        <button type="button" class="btn-secondary" onclick="nuevoArreador()">+ Nuevo arreador</button>
        <button type="button" onclick="nuevoConvenio()">+ Nuevo convenio</button>
    </div>
    <?php endif; ?>
</div>

<div class="finance-catalog-grid">
    <section class="card">
        <div class="card-header"><div><h2>Arreadores</h2><p class="help-text" id="resumenArreadores">Cargando…</p></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Nombre</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaArreadores"><tr><td colspan="4" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-header"><div><h2>Convenios <?= $anioActual ?></h2><p class="help-text" id="resumenConvenios">Cargando…</p></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Escuela / arreador</th><th>Hombres</th><th>Mujeres</th><th>Comisión</th><th>Clínica</th><th>Acciones</th></tr></thead>
            <tbody id="tablaConvenios"><tr><td colspan="6" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<?php if ($puedeEditar): ?>
<dialog class="modal" id="modalArreador">
    <div class="modal-header"><div><span class="eyebrow">Catálogo</span><h2 id="tituloArreador">Nuevo arreador</h2></div><button class="icon-button" type="button" onclick="modalArreador.close()" aria-label="Cerrar">×</button></div>
    <form id="formArreador" class="modal-form">
        <input type="hidden" name="id_arreador" id="idArreador">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-body">
            <div class="campo"><label for="nombreArreador">Nombre y apellido *</label><input id="nombreArreador" name="nombre" data-uppercase maxlength="200" required></div>
            <div class="campo"><label for="institucionArreador">Institución</label><input id="institucionArreador" name="institucion" data-uppercase maxlength="200"></div>
            <div class="field-grid">
                <div class="campo"><label for="paisArreador">País</label><select id="paisArreador" name="id_pais"></select></div>
                <div class="campo"><label for="telefonoArreador">Teléfono</label><input id="telefonoArreador" name="telefono" type="tel" placeholder="+595981123456" pattern="\+[1-9][0-9]{7,14}"></div>
            </div>
            <div class="campo"><label for="correoArreador">Correo</label><input id="correoArreador" name="correo" type="email" maxlength="150"></div>
            <div id="mensajeArreador" class="mensaje"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn-secondary" onclick="modalArreador.close()">Cancelar</button><button type="submit">Guardar arreador</button></div>
    </form>
</dialog>

<dialog class="modal" id="modalConvenio">
    <div class="modal-header"><div><span class="eyebrow">Tarifas por escuela</span><h2 id="tituloConvenio">Nuevo convenio</h2></div><button class="icon-button" type="button" onclick="modalConvenio.close()" aria-label="Cerrar">×</button></div>
    <form id="formConvenio" class="modal-form">
        <input type="hidden" name="id_arreador_escuela" id="idConvenio">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-body">
            <p class="notice">El convenio corresponde a <?= $anioActual ?>. Todos los importes se cargan manualmente y corresponden a cada postulante.</p>
            <div class="campo"><label for="convenioArreador">Arreador *</label><select id="convenioArreador" name="id_arreador" required></select></div>
            <div class="campo"><label for="convenioEscuela">Escuela *</label><select id="convenioEscuela" name="id_escuela" required></select></div>
            <div class="field-grid">
                <div class="campo"><label for="montoHombre">Cobro a hombres *</label><input id="montoHombre" name="monto_total_hombre" type="number" min="0.01" step="0.01" required></div>
                <div class="campo"><label for="montoMujer">Cobro a mujeres *</label><input id="montoMujer" name="monto_total_mujer" type="number" min="0.01" step="0.01" required></div>
                <div class="campo"><label for="comisionArreador">Comisión del arreador *</label><input id="comisionArreador" name="comision_arreador" type="number" min="0" step="0.01" required></div>
                <div class="campo"><label for="montoClinica">Queda para la clínica *</label><input id="montoClinica" name="monto_clinica" type="number" min="0" step="0.01" required></div>
            </div>
            <small>“Queda para la clínica” no se calcula automáticamente.</small>
            <div id="mensajeConvenio" class="mensaje"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn-secondary" onclick="modalConvenio.close()">Cancelar</button><button type="submit">Guardar convenio</button></div>
    </form>
</dialog>
<?php endif; ?>

<script>
const puedeEditarArreadores = <?= $puedeEditar ? 'true' : 'false' ?>;
let escuelas=[], paises=[], finanzas={anio:<?= $anioActual ?>,arreadores:[],convenios:[]};
const dinero=valor=>new Intl.NumberFormat('es-PY',{style:'currency',currency:'PYG',minimumFractionDigits:0,maximumFractionDigits:2}).format(Number(valor)||0);

async function iniciarArreadores(){
    try{
        [escuelas,paises,finanzas]=await Promise.all([
            Salus.json('backend/designacionesDAO.php?q=listar'),
            Salus.json('backend/referencialesDAO.php?q=paises'),
            Salus.json('backend/postulantesFinanzasDAO.php?q=resumen')
        ]);
        if(puedeEditarArreadores){
            paisArreador.innerHTML='<option value="">Sin especificar</option>'+paises.map(p=>`<option value="${Number(p.id_pais)}">${Salus.escape(p.nombre)}</option>`).join('');
        }
        renderArreadores();
    }catch(error){
        tablaArreadores.innerHTML=`<tr><td colspan="4" class="empty-state">${Salus.escape(error.message)}</td></tr>`;
        tablaConvenios.innerHTML='<tr><td colspan="6" class="empty-state">No se pudieron cargar los convenios.</td></tr>';
        Salus.toast(error.message,'error');
    }
}
async function recargarFinanzas(){
    finanzas=await Salus.json('backend/postulantesFinanzasDAO.php?q=resumen');
    renderArreadores();
}
function renderArreadores(){
    const arreadores=finanzas.arreadores||[], convenios=finanzas.convenios||[];
    resumenArreadores.textContent=`${arreadores.length} arreador(es), ${arreadores.filter(a=>Number(a.activo)).length} activo(s)`;
    tablaArreadores.innerHTML=arreadores.length?arreadores.map(a=>`<tr>
        <td><strong>${Salus.escape(a.nombre)}</strong><br><small>${Salus.escape(a.institucion||a.pais||'—')}</small></td>
        <td>${Salus.escape(a.telefono||'—')}<br><small>${Salus.escape(a.correo||'')}</small></td>
        <td><span class="badge ${Number(a.activo)?'badge-success':'badge-danger'}">${Number(a.activo)?'Activo':'Inactivo'}</span></td>
        <td><div class="table-actions">${puedeEditarArreadores?`<button class="btn-small btn-secondary" onclick="editarArreador(${Number(a.id_arreador)})">Editar</button><button class="btn-small ${Number(a.activo)?'btn-danger':'btn-secondary'}" onclick="estadoArreador(${Number(a.id_arreador)},${Number(a.activo)?0:1})">${Number(a.activo)?'Desactivar':'Activar'}</button>`:''}</div></td>
    </tr>`).join(''):'<tr><td colspan="4" class="empty-state">Todavía no hay arreadores.</td></tr>';

    resumenConvenios.textContent=`${convenios.length} convenio(s) en ${finanzas.anio}`;
    tablaConvenios.innerHTML=convenios.length?convenios.map(c=>`<tr>
        <td><strong>${Salus.escape(c.escuela)}</strong><br><small>${Salus.escape(c.arreador)} · ${Number(c.anio)}</small>${Number(c.activo)?'':'<br><span class="badge badge-danger">Inactivo</span>'}</td>
        <td class="finance-money">${dinero(c.monto_total_hombre)}</td>
        <td class="finance-money">${dinero(c.monto_total_mujer)}</td>
        <td class="finance-money">${dinero(c.comision_arreador)}</td>
        <td class="finance-money"><strong>${dinero(c.monto_clinica)}</strong></td>
        <td><div class="table-actions">${puedeEditarArreadores?`<button class="btn-small btn-secondary" onclick="editarConvenio(${Number(c.id_arreador_escuela)})">Editar</button><button class="btn-small ${Number(c.activo)?'btn-danger':'btn-secondary'}" onclick="estadoConvenio(${Number(c.id_arreador_escuela)},${Number(c.activo)?0:1})">${Number(c.activo)?'Desactivar':'Activar'}</button>`:''}</div></td>
    </tr>`).join(''):'<tr><td colspan="6" class="empty-state">Cree el primer convenio del año.</td></tr>';
}
function opcionesArreadores(seleccion=''){
    return '<option value="">Seleccione</option>'+(finanzas.arreadores||[]).map(a=>`<option value="${Number(a.id_arreador)}" ${String(a.id_arreador)===String(seleccion)?'selected':''}>${Salus.escape(a.nombre)}${Number(a.activo)?'':' (INACTIVO)'}</option>`).join('');
}
function opcionesEscuelas(seleccion=''){
    return '<option value="">Seleccione</option>'+escuelas.map(e=>`<option value="${Number(e.id_escuela)}" ${String(e.id_escuela)===String(seleccion)?'selected':''}>${Salus.escape(e.nombre)} · ${Salus.escape(e.periodo)}</option>`).join('');
}
function nuevoArreador(){
    formArreador.reset();idArreador.value='';tituloArreador.textContent='Nuevo arreador';mensajeArreador.textContent='';
    const paraguay=paises.find(p=>p.codigo_iso2==='PY');if(paraguay)paisArreador.value=paraguay.id_pais;
    modalArreador.showModal();nombreArreador.focus();
}
function editarArreador(id){
    const a=finanzas.arreadores.find(item=>Number(item.id_arreador)===Number(id));if(!a)return;
    formArreador.reset();idArreador.value=a.id_arreador;nombreArreador.value=a.nombre;institucionArreador.value=a.institucion||'';
    paisArreador.value=a.id_pais||'';telefonoArreador.value=a.telefono||'';correoArreador.value=a.correo||'';
    tituloArreador.textContent='Editar arreador';mensajeArreador.textContent='';modalArreador.showModal();
}
async function estadoArreador(id,activo){
    if(!confirm(`¿${activo?'Activar':'Desactivar'} este arreador?`))return;
    try{await Salus.json('backend/postulantesFinanzasDAO.php?q=estado_arreador',{method:'POST',body:Salus.form({id_arreador:id,activo})});await recargarFinanzas();}
    catch(error){Salus.toast(error.message,'error');}
}
function nuevoConvenio(){
    formConvenio.reset();idConvenio.value='';tituloConvenio.textContent=`Nuevo convenio ${finanzas.anio}`;
    convenioArreador.innerHTML=opcionesArreadores();convenioEscuela.innerHTML=opcionesEscuelas();mensajeConvenio.textContent='';modalConvenio.showModal();
}
function editarConvenio(id){
    const c=finanzas.convenios.find(item=>Number(item.id_arreador_escuela)===Number(id));if(!c)return;
    formConvenio.reset();idConvenio.value=c.id_arreador_escuela;tituloConvenio.textContent=`Editar convenio ${c.anio}`;
    convenioArreador.innerHTML=opcionesArreadores(c.id_arreador);convenioEscuela.innerHTML=opcionesEscuelas(c.id_escuela);
    montoHombre.value=c.monto_total_hombre;montoMujer.value=c.monto_total_mujer;comisionArreador.value=c.comision_arreador;montoClinica.value=c.monto_clinica;
    mensajeConvenio.textContent='';modalConvenio.showModal();
}
async function estadoConvenio(id,activo){
    if(!confirm(`¿${activo?'Activar':'Desactivar'} este convenio?`))return;
    try{await Salus.json('backend/postulantesFinanzasDAO.php?q=estado_convenio',{method:'POST',body:Salus.form({id_arreador_escuela:id,activo})});await recargarFinanzas();}
    catch(error){Salus.toast(error.message,'error');}
}
if(puedeEditarArreadores){
    formArreador.addEventListener('submit',async event=>{
        event.preventDefault();mensajeArreador.textContent='';
        try{
            await Salus.json('backend/postulantesFinanzasDAO.php?q=guardar_arreador',{method:'POST',body:new FormData(formArreador)});
            modalArreador.close();Salus.toast('Arreador guardado.');await recargarFinanzas();
        }catch(error){mensajeArreador.textContent=error.message;}
    });
    formConvenio.addEventListener('submit',async event=>{
        event.preventDefault();mensajeConvenio.textContent='';
        const comision=Number(comisionArreador.value),hombres=Number(montoHombre.value),mujeres=Number(montoMujer.value);
        if(comision>hombres||comision>mujeres){mensajeConvenio.textContent='La comisión no puede superar el cobro de hombres ni el de mujeres.';return;}
        try{
            await Salus.json('backend/postulantesFinanzasDAO.php?q=guardar_convenio',{method:'POST',body:new FormData(formConvenio)});
            modalConvenio.close();Salus.toast('Convenio anual guardado.');await recargarFinanzas();
        }catch(error){mensajeConvenio.textContent=error.message;}
    });
}
iniciarArreadores();
</script>
<?php include 'footer.php'; ?>
