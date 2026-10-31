<?php
include 'conexion.php';
$moduloPersonas = 'postulantes';
$esModuloPostulantes = $moduloPersonas === 'postulantes';
$personaSingular = $esModuloPostulantes ? 'postulante' : 'paciente';
$personaPlural = $esModuloPostulantes ? 'postulantes' : 'pacientes';
$personaSingularTitulo = $esModuloPostulantes ? 'Postulante' : 'Paciente';
$personaPluralTitulo = $esModuloPostulantes ? 'Postulantes' : 'Pacientes';
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'pacientes.editar');
$calendarioActual = $conexion->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-%d') AS hoy, YEAR(CURDATE()) AS anio")->fetch_assoc();
$hoyAplicacion = $calendarioActual['hoy'];
$anioAplicacion = (int) $calendarioActual['anio'];
$tituloPagina = $personaPluralTitulo;
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow"><?= $esModuloPostulantes ? 'Postulantes · Gestión' : 'Gestión clínica' ?></span><h1><?= $personaPluralTitulo ?></h1><p>Datos personales, procedencia e identificación de <?= $esModuloPostulantes ? 'postulantes' : 'pacientes' ?> en un solo lugar.</p></div>
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center">
        <input id="filtroPacientes" type="search" placeholder="Buscar nombre, documento o código…" style="min-width:260px; width:auto">
        <select id="tipoExportacion" aria-label="Tipo de informe" style="width:auto">
            <option value="lista">Lista de postulantes</option>
            <option value="comision">Lista + comisión de gestión</option>
            <option value="clinica">Lista + ganancia de la clínica</option>
        </select>
        <button type="button" class="btn-secondary" onclick="exportarLista('xls')" title="Descargar en Excel los <?= $personaPlural ?> de la lista">Excel</button>
        <button type="button" class="btn-secondary" onclick="exportarLista('pdf')" title="Generar un PDF con los <?= $personaPlural ?> de la lista">PDF</button>
    </div>
</div>

<?php if ($puedeEditar): ?>
<section class="card payment-context-card">
    <div class="card-header"><div><span class="eyebrow">Contexto persistente</span><h2>Escuela y convenio para cobrar</h2><p class="help-text">La selección se conserva en este navegador hasta que presione “Borrar selección”.</p></div></div>
    <div class="payment-context-grid">
        <div class="campo"><label for="selectorEscuelaCobro">Escuela *</label><select id="selectorEscuelaCobro"><option value="">Seleccione una escuela</option></select></div>
        <div class="campo"><label for="selectorConvenioCobro">Convenio *</label><select id="selectorConvenioCobro" disabled><option value="">Seleccione primero una escuela</option></select></div>
        <button type="button" class="btn-secondary" id="btnBorrarContexto">Borrar selección</button>
    </div>
</section>
<?php endif; ?>

<div class="<?= $puedeEditar ? 'grid-2' : 'grid' ?>">
<?php if ($puedeEditar): ?>
    <section class="card">
        <div class="card-header"><div><h2>Datos del <?= $personaSingular ?></h2><p class="help-text">Los campos marcados son obligatorios.</p></div></div>
        <form id="frmPersona">
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
                <div class="campo"><label for="fecha_nacimiento">Fecha de nacimiento *</label><input type="date" name="fecha_nacimiento" id="fecha_nacimiento" max="<?= htmlspecialchars($hoyAplicacion, ENT_QUOTES, 'UTF-8') ?>" required></div>
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
            <div class="form-actions"><button type="submit">Guardar <?= $personaSingular ?></button><button type="button" class="btn-secondary" onclick="limpiar()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
<?php endif; ?>

    <section class="card">
        <div class="card-header" style="flex-wrap:wrap">
            <div><h2><?= $personaPluralTitulo ?> activos</h2><p class="help-text" id="resumen">Cargando registros…</p></div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center">
                <input id="filtroFecha" type="date" title="Fecha de alta" aria-label="Filtrar por fecha de alta" style="width:auto">
                <button type="button" class="btn-secondary btn-small" onclick="filtroFecha.value=''; renderPacientes()">Todas las fechas</button>
            </div>
        </div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>ID / orden</th><th><?= $personaSingularTitulo ?></th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Rh</th><th>Acciones</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="7" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<?php if ($puedeEditar): ?>
<section class="card postulantes-finanzas payment-workflow" id="panelCobro">
    <div class="card-header"><div><span class="eyebrow">Cobro del postulante</span><h2>Medios de pago</h2><p class="help-text" id="resumenCobro">Guarde o seleccione un postulante para habilitar el cobro.</p></div></div>
    <form id="formCobro">
        <input type="hidden" id="cobroIdPaciente" name="id_paciente">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <fieldset id="camposCobro" disabled>
            <div class="payment-summary-grid">
                <div><span>Postulante</span><strong id="cobroPostulante">—</strong></div>
                <div><span>Tarifa</span><strong id="cobroTarifa">Gs. 0</strong></div>
                <div><span>Ya pagado</span><strong id="cobroPagado">Gs. 0</strong></div>
                <div><span>Saldo inicial</span><strong id="cobroSaldoInicial">Gs. 0</strong></div>
            </div>
            <div class="campo payment-date"><label for="cobroFecha">Fecha *</label><input id="cobroFecha" name="fecha" type="date" max="<?= htmlspecialchars($hoyAplicacion, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($hoyAplicacion, ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div id="filasPago" class="payment-method-list"></div>
            <button type="button" class="btn-secondary btn-small" id="btnAgregarMetodo">+ Agregar otro método</button>
            <div class="payment-final-balance"><span>Saldo después de estos pagos</span><strong id="cobroSaldoFinal">Gs. 0</strong></div>
            <div class="campo"><label for="cobroObservacion">Observación</label><textarea id="cobroObservacion" name="observacion" maxlength="500" placeholder="Observación opcional del cobro…"></textarea></div>
            <div class="form-actions"><button type="submit">Confirmar asignación y pagos</button><button type="button" class="btn-secondary" onclick="cancelarCobro()">Cancelar cobro</button></div>
            <div id="mensajeCobro" class="mensaje"></div>
        </fieldset>
    </form>
</section>
<?php endif; ?>

<section class="card postulantes-finanzas">
    <div class="card-header"><div><h2>Asignaciones y saldos</h2><p class="help-text" id="resumenFinanzas">Cargando…</p></div></div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Postulante</th><th>Escuela</th><th>Arreador</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Acciones</th></tr></thead>
        <tbody id="tablaFinanzas"><tr><td colspan="7" class="empty-state">Cargando…</td></tr></tbody>
    </table></div>
</section>

<dialog class="modal" id="modalHistorialPagos">
    <div class="modal-header"><div><span class="eyebrow">Trazabilidad</span><h2 id="tituloHistorialPagos">Historial de pagos</h2></div><button class="icon-button" type="button" onclick="modalHistorialPagos.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body"><div class="table-wrap"><table class="data-table"><thead><tr><th>Fecha</th><th>Método</th><th>Importe</th><th>Saldo</th><th>Registró</th></tr></thead><tbody id="tablaHistorialPagos"></tbody></table></div></div>
    <div class="modal-footer"><button type="button" class="btn-secondary" onclick="modalHistorialPagos.close()">Cerrar</button></div>
</dialog>

<script>
const puedeEditarPacientes = <?= $puedeEditar ? 'true' : 'false' ?>;
const moduloPersonas = <?= json_encode($moduloPersonas, JSON_UNESCAPED_UNICODE) ?>;
const personaSingular = <?= json_encode($personaSingular, JSON_UNESCAPED_UNICODE) ?>;
const personaPlural = <?= json_encode($personaPlural, JSON_UNESCAPED_UNICODE) ?>;
const personaSingularTitulo = <?= json_encode($personaSingularTitulo, JSON_UNESCAPED_UNICODE) ?>;
const hoyAplicacion = <?= json_encode($hoyAplicacion) ?>;
let pacientes = [], escuelas = [], paises = [];
let finanzas = {anio:<?= $anioAplicacion ?>, arreadores:[], convenios:[], asignaciones:[], metodos_pago:[]};
let pacientesVisibles = []; // lo que se ve en la tabla con los filtros aplicados
let pacienteCobro = null, secuenciaPago = 0;
const storageEscuela = 'salus.postulantes.escuela_cobro';
const storageConvenio = 'salus.postulantes.convenio_cobro';
async function cargarCatalogos() {
    [escuelas, paises] = await Promise.all([
        Salus.json('backend/designacionesDAO.php?q=listar'), Salus.json('backend/referencialesDAO.php?q=paises')
    ]);
    if (puedeEditarPacientes) {
        id_escuela.innerHTML='<option value="">Sin especificar</option>'+escuelas.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)} · ${Salus.escape(d.periodo)}</option>`).join('');
        id_pais.innerHTML=paises.map(p=>`<option value="${p.id_pais}">${Salus.escape(p.nombre)} (${Salus.escape(p.prefijo_telefonico)})</option>`).join('');
        selectorEscuelaCobro.innerHTML='<option value="">Seleccione una escuela</option>'+escuelas.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)} · ${Salus.escape(d.periodo)}</option>`).join('');
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
    resumen.textContent=`${datos.length} de ${pacientes.length} ${personaPlural}`;
    tabla.innerHTML=datos.length?datos.map(p=>`<tr>
        <td><strong>${Number(p.id_paciente)}</strong><br><small title="Orden del día de alta">Orden ${Salus.escape(p.orden_dia)}</small></td>
        <td><strong>${Salus.escape(p.nombre)}</strong><br><small>${Salus.escape(p.codigo_generado||'')}</small></td>
        <td>${Salus.escape(p.ci)}</td><td>${Salus.escape(p.edad)} años<br><small>${Salus.escape(p.sexo)}</small></td>
        <td>${Salus.escape(p.escuela||'—')}</td><td><span class="badge badge-muted">${Salus.escape(formatoGrupoSanguineo(p.grupo_sanguineo)||'—')}</span></td>
        <td><div class="table-actions">${puedeEditarPacientes?`<button class="btn-small btn-secondary" onclick="editar(${p.id_paciente})">Editar</button><button class="btn-small" onclick="prepararCobro(${p.id_paciente})">Seleccionar / cobrar</button><button class="btn-small btn-danger" onclick="eliminar(${p.id_paciente})">Desactivar</button>`:''}</div></td>
    </tr>`).join(''):`<tr><td colspan="7" class="empty-state">No se encontraron ${personaPlural}.</td></tr>`;
}

function dinero(valor){
    return new Intl.NumberFormat('es-PY',{style:'currency',currency:'PYG',minimumFractionDigits:0,maximumFractionDigits:0}).format(Number(valor)||0);
}
function numeroMonto(valor){return Number(String(valor??'').replace(/\.00$/,'').replace(/\D/g,''))||0;}
function formatearMiles(valor){const numero=numeroMonto(valor);return numero?numero.toLocaleString('es-PY'):'';}
function fechaHora(valor){
    if(!valor)return '—';
    const partes=String(valor).split(' ')[0].split('-');
    return partes.length===3?`${partes[2]}/${partes[1]}/${partes[0]}`:Salus.escape(valor);
}
async function cargarFinanzas(){
    try{
        finanzas=await Salus.json('backend/postulantesFinanzasDAO.php?q=resumen');
        renderFinanzas();
        renderPacientes();
    }catch(error){
        tablaFinanzas.innerHTML=`<tr><td colspan="7" class="empty-state">${Salus.escape(error.message)}</td></tr>`;
        Salus.toast(error.message,'error');
    }
}
function renderFinanzas(){
    const asignaciones=finanzas.asignaciones||[];
    resumenFinanzas.textContent=`${asignaciones.length} postulante(s) asignado(s) en ${finanzas.anio}`;
    tablaFinanzas.innerHTML=asignaciones.length?asignaciones.map(a=>`<tr>
        <td><strong>${Salus.escape(a.postulante)}</strong><br><small>${Salus.escape(a.documento)}</small></td>
        <td>${Salus.escape(a.escuela)}</td><td>${Salus.escape(a.arreador)}</td>
        <td class="finance-money">${dinero(a.monto_total)}</td><td class="finance-money">${dinero(a.pagado)}</td>
        <td class="finance-money"><strong class="${Number(a.saldo)<=0?'estado-activo':''}">${dinero(a.saldo)}</strong></td>
        <td><div class="table-actions">${puedeEditarPacientes&&Number(a.saldo)>0?`<button class="btn-small" onclick="prepararCobro(${Number(a.id_paciente)})">Cobrar</button>`:''}<button class="btn-small btn-secondary" onclick="verHistorialPagos(${Number(a.id_arreador_postulante)})">Historial</button></div></td>
    </tr>`).join(''):'<tr><td colspan="7" class="empty-state">Aún no hay postulantes vinculados a un arreador.</td></tr>';
}
function conveniosDeEscuela(idEscuela){
    return (finanzas.convenios||[]).filter(c=>Number(c.activo)&&Number(c.id_escuela)===Number(idEscuela));
}
function cargarConveniosContexto(idSeleccionado='',seleccionarUnico=false){
    const disponibles=conveniosDeEscuela(selectorEscuelaCobro.value);
    selectorConvenioCobro.disabled=!selectorEscuelaCobro.value;
    selectorConvenioCobro.innerHTML='<option value="">Seleccione un convenio</option>'+disponibles.map(c=>`<option value="${Number(c.id_arreador_escuela)}">${Salus.escape(c.arreador)} · H ${dinero(c.monto_total_hombre)} · M ${dinero(c.monto_total_mujer)}</option>`).join('');
    if(disponibles.some(c=>String(c.id_arreador_escuela)===String(idSeleccionado)))selectorConvenioCobro.value=String(idSeleccionado);
    else if(seleccionarUnico&&disponibles.length===1)selectorConvenioCobro.value=String(disponibles[0].id_arreador_escuela);
    localStorage.setItem(storageEscuela,selectorEscuelaCobro.value||'');
    if(selectorConvenioCobro.value)localStorage.setItem(storageConvenio,selectorConvenioCobro.value);
    else localStorage.removeItem(storageConvenio);
}
function restaurarContextoCobro(){
    const escuelaGuardada=localStorage.getItem(storageEscuela)||'';
    const convenioGuardado=localStorage.getItem(storageConvenio)||'';
    if([...selectorEscuelaCobro.options].some(o=>o.value===escuelaGuardada))selectorEscuelaCobro.value=escuelaGuardada;
    cargarConveniosContexto(convenioGuardado);
    if(!id_paciente.value&&selectorEscuelaCobro.value)id_escuela.value=selectorEscuelaCobro.value;
}
function borrarContextoCobro(){
    localStorage.removeItem(storageEscuela);localStorage.removeItem(storageConvenio);
    selectorEscuelaCobro.value='';selectorConvenioCobro.innerHTML='<option value="">Seleccione primero una escuela</option>';selectorConvenioCobro.disabled=true;
    if(!id_paciente.value)id_escuela.value='';
    cancelarCobro();
}
function convenioContexto(){return (finanzas.convenios||[]).find(c=>Number(c.id_arreador_escuela)===Number(selectorConvenioCobro.value));}
function prepararCobro(idPaciente){
    const p=pacientes.find(item=>Number(item.id_paciente)===Number(idPaciente));if(!p)return;
    if(!p.id_escuela){Salus.toast('El postulante debe tener una escuela para poder cobrar.','warn');return;}
    const asignacion=finanzas.asignaciones.find(a=>Number(a.id_paciente)===Number(idPaciente));
    selectorEscuelaCobro.value=String(p.id_escuela);
    cargarConveniosContexto(asignacion?.id_arreador_escuela||selectorConvenioCobro.value,true);
    if(!selectorConvenioCobro.value){
        Salus.toast('Seleccione un convenio activo para la escuela del postulante.','warn');
        selectorConvenioCobro.focus();document.querySelector('.payment-context-card')?.scrollIntoView({behavior:'smooth'});return;
    }
    localStorage.setItem(storageConvenio,selectorConvenioCobro.value);
    pacienteCobro=p;cobroIdPaciente.value=p.id_paciente;camposCobro.disabled=false;
    cobroPostulante.textContent=`${p.nombre} · ${p.ci}`;
    cobroFecha.value=hoyAplicacion;cobroObservacion.value=asignacion?.observacion||'';mensajeCobro.textContent='';
    reiniciarFilasPago();actualizarResumenCobro();
    panelCobro.scrollIntoView({behavior:'smooth',block:'start'});
}
function actualizarResumenCobro(){
    if(!pacienteCobro)return;
    const convenio=convenioContexto();
    if(!convenio||Number(convenio.id_escuela)!==Number(pacienteCobro.id_escuela)){
        cancelarCobro();return;
    }
    const tarifa=Number(pacienteCobro.es_femenino)?Number(convenio.monto_total_mujer):Number(convenio.monto_total_hombre);
    const asignacion=finanzas.asignaciones.find(a=>Number(a.id_paciente)===Number(pacienteCobro.id_paciente)&&Number(a.id_arreador_escuela)===Number(convenio.id_arreador_escuela));
    const pagado=Number(asignacion?.pagado||0),saldo=Math.max(0,tarifa-pagado);
    panelCobro.dataset.tarifa=String(tarifa);panelCobro.dataset.pagado=String(pagado);panelCobro.dataset.saldo=String(saldo);
    cobroTarifa.textContent=dinero(tarifa);cobroPagado.textContent=dinero(pagado);cobroSaldoInicial.textContent=dinero(saldo);
    resumenCobro.textContent=`${pacienteCobro.nombre} · ${pacienteCobro.escuela} · ${Number(pacienteCobro.es_femenino)?'Tarifa mujer':'Tarifa hombre'}`;
    calcularSaldosPago();
}
function reiniciarFilasPago(){filasPago.innerHTML='';secuenciaPago=0;agregarFilaPago();}
function agregarFilaPago(){
    secuenciaPago++;
    const fila=document.createElement('div');fila.className='payment-method-row';fila.dataset.pago=String(secuenciaPago);
    fila.innerHTML=`<div class="campo"><label>Método *</label><select class="pago-metodo" required><option value="">Seleccione</option>${(finanzas.metodos_pago||[]).map(m=>`<option value="${Number(m.id_metodo_pago)}">${Salus.escape(m.nombre)}</option>`).join('')}</select></div><div class="campo"><label>Importe *</label><input class="pago-monto" inputmode="numeric" autocomplete="off" required placeholder="0"></div><div class="payment-row-balance"><span>Saldo después</span><strong class="pago-saldo">${dinero(panelCobro.dataset.saldo||0)}</strong></div><button type="button" class="btn-danger btn-small pago-quitar" aria-label="Quitar método">Quitar</button>`;
    fila.querySelector('.pago-monto').addEventListener('input',event=>{const digitos=event.target.value.replace(/\D/g,'');event.target.value=digitos?Number(digitos).toLocaleString('es-PY'):'';calcularSaldosPago();});
    fila.querySelector('.pago-metodo').addEventListener('change',validarMetodosRepetidos);
    fila.querySelector('.pago-quitar').addEventListener('click',()=>{fila.remove();if(!filasPago.children.length)agregarFilaPago();calcularSaldosPago();});
    filasPago.appendChild(fila);calcularSaldosPago();
}
function validarMetodosRepetidos(){
    const vistos=new Set();let repetido=false;
    document.querySelectorAll('.pago-metodo').forEach(campo=>{campo.setCustomValidity('');if(campo.value&&vistos.has(campo.value)){campo.setCustomValidity('Este método ya fue seleccionado.');repetido=true;}vistos.add(campo.value);});
    return !repetido;
}
function calcularSaldosPago(){
    let saldo=Number(panelCobro.dataset.saldo||0);
    document.querySelectorAll('.payment-method-row').forEach(fila=>{saldo-=numeroMonto(fila.querySelector('.pago-monto').value);const salida=fila.querySelector('.pago-saldo');salida.textContent=dinero(Math.max(0,saldo));salida.classList.toggle('estado-inactivo',saldo<0);});
    cobroSaldoFinal.textContent=dinero(Math.max(0,saldo));cobroSaldoFinal.classList.toggle('estado-inactivo',saldo<0);panelCobro.dataset.saldoFinal=String(saldo);
}
function cancelarCobro(){
    pacienteCobro=null;cobroIdPaciente.value='';camposCobro.disabled=true;filasPago.innerHTML='';
    resumenCobro.textContent='Guarde o seleccione un postulante para habilitar el cobro.';cobroPostulante.textContent='—';mensajeCobro.textContent='';
}
async function verHistorialPagos(idAsignacion){
    const a=finanzas.asignaciones.find(item=>Number(item.id_arreador_postulante)===Number(idAsignacion));
    tituloHistorialPagos.textContent=a?`Pagos de ${a.postulante}`:'Historial de pagos';
    tablaHistorialPagos.innerHTML='<tr><td colspan="5" class="empty-state">Cargando…</td></tr>';modalHistorialPagos.showModal();
    try{
        const r=await Salus.json(`backend/postulantesFinanzasDAO.php?q=pagos&id=${Number(idAsignacion)}`);
        tablaHistorialPagos.innerHTML=r.pagos.length?r.pagos.map(p=>`<tr><td>${fechaHora(p.fecha)}<br><small>${Salus.escape(String(p.fecha).split(' ')[1]||'')}</small></td><td>${Salus.escape(p.metodo_pago)}</td><td class="finance-money">${dinero(p.monto)}</td><td class="finance-money">${dinero(p.saldo)}</td><td>${Salus.escape(p.registrado_por)}${p.observacion?`<br><small>${Salus.escape(p.observacion)}</small>`:''}</td></tr>`).join(''):'<tr><td colspan="5" class="empty-state">Todavía no se registraron pagos.</td></tr>';
    }catch(error){tablaHistorialPagos.innerHTML=`<tr><td colspan="5" class="empty-state">${Salus.escape(error.message)}</td></tr>`;}
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
    if(!pacientesVisibles.length){Salus.toast(`No hay ${personaPlural} en la lista para exportar.`,'warn');return;}
    const filtros=[];
    if(filtroPacientes.value.trim()) filtros.push('búsqueda "'+filtroPacientes.value.trim()+'"');
    if(filtroFecha.value) filtros.push('fecha de alta '+filtroFecha.value.split('-').reverse().join('/'));
    const form=document.createElement('form');
    form.method='POST'; form.action='backend/pacientesExportar.php';
    if(formato==='pdf') form.target='_blank';
    const campos={formato, entidad:moduloPersonas, reporte:tipoExportacion.value, ids:pacientesVisibles.map(p=>p.id_paciente).join(','), filtro:filtros.join(', '), csrf_token:Salus.csrf};
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
            if(Number(id_paciente.value)===r.id_paciente){prepararCobro(r.id_paciente);return;}
            if(r.activo&&pacientes.some(p=>Number(p.id_paciente)===r.id_paciente)){
                editar(r.id_paciente);
                prepararCobro(r.id_paciente);
                Salus.toast(`Este ${personaSingular} ya está registrado (ID ${r.id_paciente}). Se cargaron sus datos para editar.`,'warn');
            }else{
                id_paciente.value='';
                nombre.value=r.nombre||''; fecha_nacimiento.value=r.fecha_nacimiento||''; es_femenino.value=r.sexo==='F'?'1':'0';
                Salus.toast(`Esta cédula pertenece a un ${personaSingular} desactivado (ID ${r.id_paciente}). Reactívelo antes de volver a cargarlo.`,'warn');
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
    frmPersona.reset();id_paciente.value='';url_foto.value='';mensaje.textContent='';
    seleccionarParaguay();
    if(puedeEditarPacientes&&selectorEscuelaCobro.value)id_escuela.value=selectorEscuelaCobro.value;
    // Foto oculta por ahora: vista_foto.src='';vista_foto.hidden=true;
}
async function eliminar(id){
    if(!confirm(`¿Desactivar este ${personaSingular}?`))return;
    try{await Salus.json('backend/postulantesDAO.php?q=eliminar',{method:'POST',body:Salus.form({id})});Salus.toast(`${personaSingularTitulo} desactivado.`);cargar();}
    catch(error){Salus.toast(error.message,'error');}
}
if (puedeEditarPacientes) frmPersona.addEventListener('submit',async event=>{
    event.preventDefault(); mensaje.textContent='';
    if(!selectorEscuelaCobro.value||!selectorConvenioCobro.value){mensaje.textContent='Seleccione primero la escuela y el convenio para el cobro.';selectorEscuelaCobro.focus();return;}
    if(!id_paciente.value)id_escuela.value=selectorEscuelaCobro.value;
    try{
        const resultado=await Salus.json('backend/postulantesDAO.php?q=guardar',{method:'POST',body:new FormData(frmPersona)});
        // Foto oculta por ahora. Para reactivarla, guardar el resultado de arriba en "resultado" y descomentar:
        // if(foto_paciente.files[0]){
        //     const foto=Salus.form({tipo:'paciente_foto',id_paciente:resultado.id_paciente});foto.append('archivo',foto_paciente.files[0]);
        //     await Salus.json('backend/uploadDAO.php',{method:'POST',body:foto});
        // }
        Salus.toast(`${personaSingularTitulo} guardado. Complete ahora los medios de pago.`);await cargar();await cargarFinanzas();limpiar();prepararCobro(resultado.id_paciente);
    }catch(error){mensaje.textContent=error.message;}
});
if (puedeEditarPacientes) {
    selectorEscuelaCobro.addEventListener('change',()=>{
        cargarConveniosContexto('',true);if(!id_paciente.value)id_escuela.value=selectorEscuelaCobro.value;
        if(pacienteCobro&&Number(pacienteCobro.id_escuela)!==Number(selectorEscuelaCobro.value))cancelarCobro();
    });
    selectorConvenioCobro.addEventListener('change',()=>{
        if(selectorConvenioCobro.value)localStorage.setItem(storageConvenio,selectorConvenioCobro.value);else localStorage.removeItem(storageConvenio);
        actualizarResumenCobro();
    });
    btnBorrarContexto.addEventListener('click',borrarContextoCobro);
    btnAgregarMetodo.addEventListener('click',agregarFilaPago);
    formCobro.addEventListener('submit',async event=>{
        event.preventDefault();mensajeCobro.textContent='';
        if(!pacienteCobro||!selectorConvenioCobro.value){mensajeCobro.textContent='Seleccione un postulante y un convenio.';return;}
        if(!validarMetodosRepetidos()||!formCobro.reportValidity())return;
        const pagos=[...document.querySelectorAll('.payment-method-row')].map(fila=>({id_metodo_pago:fila.querySelector('.pago-metodo').value,monto:String(numeroMonto(fila.querySelector('.pago-monto').value))}));
        if(pagos.some(p=>!p.id_metodo_pago||Number(p.monto)<=0)){mensajeCobro.textContent='Complete cada método con un importe mayor que cero.';return;}
        calcularSaldosPago();if(Number(panelCobro.dataset.saldoFinal)<0){mensajeCobro.textContent='La suma de los pagos supera el saldo pendiente.';return;}
        const cuerpo=Salus.form({id_paciente:pacienteCobro.id_paciente,id_arreador_escuela:selectorConvenioCobro.value,fecha:cobroFecha.value,observacion:cobroObservacion.value,pagos:JSON.stringify(pagos)});
        try{
            const resultado=await Salus.json('backend/postulantesFinanzasDAO.php?q=registrar_cobro',{method:'POST',body:cuerpo});
            Salus.toast(`Asignación y pagos registrados. Saldo: ${dinero(resultado.saldo)}.`);cancelarCobro();await cargar();await cargarFinanzas();
        }catch(error){mensajeCobro.textContent=error.message;}
    });
}
// Enter en Documento busca en el padrón en vez de guardar el formulario.
if (puedeEditarPacientes) ci.addEventListener('keydown',event=>{
    if(event.key==='Enter'){event.preventDefault();buscarCedula();}
});
filtroPacientes.addEventListener('input',renderPacientes);
filtroFecha.addEventListener('change',renderPacientes);
(async()=>{try{filtroFecha.value=hoyAplicacion;await cargarCatalogos();await cargarFinanzas();if(puedeEditarPacientes)restaurarContextoCobro();await cargar();}catch(error){Salus.toast(error.message,'error');}})();
</script>
<?php include 'footer.php'; ?>
