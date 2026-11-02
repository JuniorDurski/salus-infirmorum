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
        <button type="button" class="btn-secondary" onclick="verArreadores()">Ver arreadores</button>
        <button type="button" class="btn-secondary" onclick="nuevoArreador()">+ Nuevo arreador</button>
        <button type="button" onclick="nuevoConvenio()">+ Nuevo convenio</button>
    </div>
    <?php else: ?>
    <div class="header-actions"><button type="button" class="btn-secondary" onclick="verArreadores()">Ver arreadores</button></div>
    <?php endif; ?>
</div>

<section class="card finance-filters">
    <div class="field-grid finance-filter-grid">
        <div class="campo"><label for="filtroAnioConvenio">Año</label><select id="filtroAnioConvenio"></select></div>
        <div class="campo"><label for="filtroEscuelaConvenio">Escuela</label><select id="filtroEscuelaConvenio"><option value="">Todas</option></select></div>
        <div class="campo"><label for="filtroArreadorConvenio">Arreador</label><select id="filtroArreadorConvenio"><option value="">Todos</option></select></div>
    </div>
</section>

<section class="card postulantes-finanzas">
    <div class="card-header"><div><h2>Convenios</h2><p class="help-text" id="resumenConvenios">Cargando…</p></div></div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Escuela / arreador</th><th>Orden desde</th><th>Hombres</th><th>Mujeres</th><th>Comisión</th><th>Clínica</th><th>Acciones</th></tr></thead>
        <tbody id="tablaConvenios"><tr><td colspan="7" class="empty-state">Cargando…</td></tr></tbody>
    </table></div>
</section>

<dialog class="modal modal-wide" id="modalListaArreadores">
    <div class="modal-header"><div><span class="eyebrow">Catálogo</span><h2>Arreadores</h2><p class="help-text" id="resumenArreadores">Cargando…</p></div><button class="icon-button" type="button" onclick="modalListaArreadores.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body"><div class="table-wrap"><table class="data-table">
        <thead><tr><th>Nombre</th><th>Contacto</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody id="tablaArreadores"><tr><td colspan="4" class="empty-state">Cargando…</td></tr></tbody>
    </table></div></div>
    <div class="modal-footer"><button type="button" class="btn-secondary" onclick="modalListaArreadores.close()">Cerrar</button></div>
</dialog>

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
            <div class="campo"><label for="ordenInicio">Orden de inicio *</label><input id="ordenInicio" name="orden_inicio" type="number" min="1" max="60000" step="1" value="1" inputmode="numeric" required><small>Los postulantes de este convenio se numeran cada día desde este número.</small></div>
            <div class="field-grid">
                <div class="campo"><label for="montoHombre">Cobro a hombres *</label><input id="montoHombre" name="monto_total_hombre" class="money-input" inputmode="numeric" autocomplete="off" required></div>
                <div class="campo"><label for="montoMujer">Cobro a mujeres *</label><input id="montoMujer" name="monto_total_mujer" class="money-input" inputmode="numeric" autocomplete="off" required></div>
                <div class="campo"><label for="comisionArreador">Comisión del arreador *</label><input id="comisionArreador" name="comision_arreador" class="money-input" inputmode="numeric" autocomplete="off" required></div>
                <div class="campo"><label for="montoClinica">Queda para la clínica *</label><input id="montoClinica" name="monto_clinica" class="money-input" inputmode="numeric" autocomplete="off" required></div>
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
const dinero=valor=>new Intl.NumberFormat('es-PY',{style:'currency',currency:'PYG',minimumFractionDigits:0,maximumFractionDigits:0}).format(Number(valor)||0);
const numeroMonto=valor=>Number(String(valor??'').replace(/\.00$/,'').replace(/\D/g,''))||0;
const formatearMiles=valor=>numeroMonto(valor).toLocaleString('es-PY',{maximumFractionDigits:0});

async function iniciarArreadores(){
    try{
        [escuelas,paises,finanzas]=await Promise.all([
            Salus.json('backend/designacionesDAO.php?q=listar'),
            Salus.json('backend/referencialesDAO.php?q=paises'),
            Salus.json('backend/postulantesFinanzasDAO.php?q=configuracion')
        ]);
        if(puedeEditarArreadores){
            paisArreador.innerHTML='<option value="">Sin especificar</option>'+paises.map(p=>`<option value="${Number(p.id_pais)}">${Salus.escape(p.nombre)}</option>`).join('');
        }
        actualizarFiltros();
        renderArreadores();
    }catch(error){
        tablaArreadores.innerHTML=`<tr><td colspan="4" class="empty-state">${Salus.escape(error.message)}</td></tr>`;
        tablaConvenios.innerHTML='<tr><td colspan="7" class="empty-state">No se pudieron cargar los convenios.</td></tr>';
        Salus.toast(error.message,'error');
    }
}
async function recargarFinanzas(){
    finanzas=await Salus.json('backend/postulantesFinanzasDAO.php?q=configuracion');
    actualizarFiltros();
    renderArreadores();
}
function actualizarFiltros(){
    const anioSeleccionado=filtroAnioConvenio.value||String(finanzas.anio);
    const escuelaSeleccionada=filtroEscuelaConvenio.value;
    const arreadorSeleccionado=filtroArreadorConvenio.value;
    const anios=[...new Set([Number(finanzas.anio),...(finanzas.convenios||[]).map(c=>Number(c.anio))])].sort((a,b)=>b-a);
    filtroAnioConvenio.innerHTML=anios.map(a=>`<option value="${a}">${a}</option>`).join('');
    filtroAnioConvenio.value=anios.includes(Number(anioSeleccionado))?anioSeleccionado:String(finanzas.anio);
    filtroEscuelaConvenio.innerHTML='<option value="">Todas</option>'+escuelas.map(e=>`<option value="${Number(e.id_escuela)}">${Salus.escape(e.nombre)} · ${Salus.escape(e.periodo)}</option>`).join('');
    filtroEscuelaConvenio.value=escuelaSeleccionada;
    filtroArreadorConvenio.innerHTML='<option value="">Todos</option>'+(finanzas.arreadores||[]).map(a=>`<option value="${Number(a.id_arreador)}">${Salus.escape(a.nombre)}</option>`).join('');
    filtroArreadorConvenio.value=arreadorSeleccionado;
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

    const filtrados=convenios.filter(c=>Number(c.anio)===Number(filtroAnioConvenio.value)
        &&(!filtroEscuelaConvenio.value||Number(c.id_escuela)===Number(filtroEscuelaConvenio.value))
        &&(!filtroArreadorConvenio.value||Number(c.id_arreador)===Number(filtroArreadorConvenio.value)));
    resumenConvenios.textContent=`${filtrados.length} de ${convenios.length} convenio(s)`;
    tablaConvenios.innerHTML=filtrados.length?filtrados.map(c=>`<tr>
        <td><strong>${Salus.escape(c.escuela)}</strong><br><small>${Salus.escape(c.arreador)} · ${Number(c.anio)}</small>${Number(c.activo)?'':'<br><span class="badge badge-danger">Inactivo</span>'}</td>
        <td class="finance-money">${String(Number(c.orden_inicio)||1).padStart(3,'0')}</td>
        <td class="finance-money">${dinero(c.monto_total_hombre)}</td>
        <td class="finance-money">${dinero(c.monto_total_mujer)}</td>
        <td class="finance-money">${dinero(c.comision_arreador)}</td>
        <td class="finance-money"><strong>${dinero(c.monto_clinica)}</strong></td>
        <td><div class="table-actions">${puedeEditarArreadores&&Number(c.anio)===Number(finanzas.anio)?`<button class="btn-small btn-secondary" onclick="editarConvenio(${Number(c.id_arreador_escuela)})">Editar</button><button class="btn-small ${Number(c.activo)?'btn-danger':'btn-secondary'}" onclick="estadoConvenio(${Number(c.id_arreador_escuela)},${Number(c.activo)?0:1})">${Number(c.activo)?'Desactivar':'Activar'}</button>`:'<span class="badge badge-muted">Histórico</span>'}</div></td>
    </tr>`).join(''):'<tr><td colspan="7" class="empty-state">No hay convenios para estos filtros.</td></tr>';
}
function verArreadores(){modalListaArreadores.showModal();}
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
    montoHombre.value=formatearMiles(c.monto_total_hombre);montoMujer.value=formatearMiles(c.monto_total_mujer);comisionArreador.value=formatearMiles(c.comision_arreador);montoClinica.value=formatearMiles(c.monto_clinica);ordenInicio.value=Number(c.orden_inicio)||1;
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
        const comision=numeroMonto(comisionArreador.value),hombres=numeroMonto(montoHombre.value),mujeres=numeroMonto(montoMujer.value);
        if(comision>hombres||comision>mujeres){mensajeConvenio.textContent='La comisión no puede superar el cobro de hombres ni el de mujeres.';return;}
        try{
            await Salus.json('backend/postulantesFinanzasDAO.php?q=guardar_convenio',{method:'POST',body:new FormData(formConvenio)});
            modalConvenio.close();Salus.toast('Convenio anual guardado.');await recargarFinanzas();
        }catch(error){mensajeConvenio.textContent=error.message;}
    });
}
document.querySelectorAll('.money-input').forEach(campo=>campo.addEventListener('input',()=>{
    const digitos=campo.value.replace(/\D/g,'');
    campo.value=digitos?Number(digitos).toLocaleString('es-PY'):'';
}));
[filtroAnioConvenio,filtroEscuelaConvenio,filtroArreadorConvenio].forEach(campo=>campo.addEventListener('change',renderArreadores));
iniciarArreadores();
</script>
<?php include 'footer.php'; ?>
