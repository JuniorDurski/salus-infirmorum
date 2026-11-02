<?php
include 'conexion.php';
$moduloPersonas = 'postulantes';
$personaSingular = 'postulante';
$personaPlural = 'postulantes';
$personaSingularTitulo = 'Postulante';
$personaPluralTitulo = 'Postulantes';
$usuarioAutenticado = requerirAutenticacion($conexion, 'pacientes.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'pacientes.editar');
$calendarioActual = $conexion->query("SELECT DATE_FORMAT(CURDATE(), '%Y-%m-%d') AS hoy, YEAR(CURDATE()) AS anio")->fetch_assoc();
$hoyAplicacion = $calendarioActual['hoy'];
$anioAplicacion = (int) $calendarioActual['anio'];
$tituloPagina = $personaPluralTitulo;
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Postulantes · Gestión</span><h1>Postulantes</h1><p>Registro de postulantes, pagos y saldos por escuela.</p></div>
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center">
        <input id="filtroPacientes" type="search" placeholder="Buscar nombre, documento o código…" style="min-width:260px; width:auto">
        <select id="tipoExportacion" aria-label="Tipo de informe" style="width:auto">
            <option value="lista">Lista de postulantes</option>
            <option value="comision">Lista + comisión de gestión</option>
            <option value="clinica">Lista + ganancia de la clínica</option>
        </select>
        <button type="button" class="btn-secondary" onclick="exportarLista('xls')" title="Descargar en Excel los postulantes de la lista">Excel</button>
        <button type="button" class="btn-secondary" onclick="exportarLista('pdf')" title="Generar un PDF con los postulantes de la lista">PDF</button>
    </div>
</div>

<div class="card context-bar" title="La selección se conserva en este navegador hasta presionar “Borrar selección”.">
    <label for="selectorEscuelaCobro">Escuela</label>
    <select id="selectorEscuelaCobro"><option value="">Todas las escuelas</option></select>
    <?php if ($puedeEditar): ?>
    <label for="selectorConvenioCobro">Convenio</label>
    <select id="selectorConvenioCobro" disabled><option value="">Seleccione primero una escuela</option></select>
    <?php endif; ?>
    <button type="button" class="btn-secondary btn-small" id="btnBorrarContexto">Borrar selección</button>
</div>

<div class="<?= $puedeEditar ? 'grid-2' : 'grid' ?>">
<?php if ($puedeEditar): ?>
    <section class="card">
        <div class="card-header"><div><h2>Datos del postulante</h2><p class="help-text">Los campos marcados son obligatorios.</p></div></div>
        <form id="frmPersona">
            <input type="hidden" name="id_paciente" id="id_paciente">
            <input type="hidden" name="url_foto" id="url_foto">
            <!-- País y teléfono no se piden: vacío = Paraguay y sin teléfono. Al editar se conservan los que ya tenga. -->
            <input type="hidden" name="id_pais" id="id_pais">
            <input type="hidden" name="telefono" id="telefono">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="ci">Documento *</label>
                <div style="display:flex; gap:6px">
                    <input name="ci" id="ci" maxlength="30" required>
                    <button type="button" class="btn-secondary" id="btnBuscarCedula" style="flex-shrink:0" onclick="buscarCedula()" title="Buscar en el sistema y en el padrón de cédulas">Buscar</button>
                </div>
            </div>
            <div class="campo"><label for="nombre">Nombre y apellido *</label><input name="nombre" id="nombre" data-uppercase maxlength="250" required></div>
            <div class="campo"><label for="codigo">Código</label><input name="codigo" id="codigo" maxlength="20" placeholder="Opcional"></div>
            <div class="field-grid">
                <div class="campo"><label for="fecha_nacimiento">Fecha de nacimiento *</label><input type="date" name="fecha_nacimiento" id="fecha_nacimiento" max="<?= htmlspecialchars($hoyAplicacion, ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="campo"><label for="es_femenino">Sexo *</label><select name="es_femenino" id="es_femenino" required><option value="">Seleccione</option><option value="0">Masculino</option><option value="1">Femenino</option></select></div>
                <div class="campo"><label for="id_escuela">Escuela</label><select name="id_escuela" id="id_escuela"><option value="">Cargando…</option></select></div>
                <div class="campo"><label for="grupo_sanguineo">Grupo sanguíneo</label><select name="grupo_sanguineo" id="grupo_sanguineo"><option value="">Sin especificar</option><option>A Rh Positivo</option><option>A Rh Negativo</option><option>B Rh Positivo</option><option>B Rh Negativo</option><option>AB Rh Positivo</option><option>AB Rh Negativo</option><option>O Rh Positivo</option><option>O Rh Negativo</option></select></div>
            </div>
            <!--
                Foto del postulante oculta por ahora (solo en pantalla). El backend sigue soportándola
                (backend/uploadDAO.php, tipo "paciente_foto") y el campo oculto url_foto conserva la
                foto que ya tenga. Para volver a mostrarla, restaurar:
                <div class="campo"><label for="foto_paciente">Foto</label><img id="vista_foto" class="preview-image" alt="Vista previa" hidden><input type="file" id="foto_paciente" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG o WEBP. Se sube al guardar.</small></div>
            -->
            <div class="form-actions"><button type="submit">Guardar postulante</button><button type="button" class="btn-secondary" onclick="limpiar()">Cancelar</button></div>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>
<?php endif; ?>

    <section class="card">
        <div class="card-header" style="flex-wrap:wrap">
            <div><h2 id="tituloEscuela">Todas las escuelas</h2><p class="help-text" id="resumen">Cargando registros…</p></div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center">
                <input id="filtroFecha" type="date" title="Fecha de registro en postulantes" aria-label="Filtrar por fecha de registro" style="width:auto">
                <button type="button" class="btn-secondary btn-small" onclick="filtroFecha.value=''; renderTabla()">Todas las fechas</button>
            </div>
        </div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Postulante</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Acciones</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="5" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<dialog class="modal modal-wide payment-modal" id="modalPagos">
    <div class="modal-header">
        <div><span class="eyebrow">Pagos del postulante</span><h2 id="tituloPagos">Pagos</h2><p class="help-text" id="resumenCobro"></p></div>
        <button class="icon-button" type="button" onclick="cerrarPagos()" aria-label="Cerrar">×</button>
    </div>
    <div class="modal-body">
        <div class="payment-summary-grid">
            <div><span>Postulante</span><strong id="cobroPostulante">—</strong></div>
            <div><span>Tarifa</span><strong id="cobroTarifa" class="finance-money">Gs. 0</strong></div>
            <div><span>Pagado</span><strong id="cobroPagado" class="finance-money">Gs. 0</strong></div>
            <div><span>Saldo pendiente</span><strong id="cobroSaldoInicial" class="finance-money">Gs. 0</strong></div>
        </div>
        <div class="payment-layout<?= $puedeEditar ? '' : ' solo-historial' ?>">
            <section class="payment-section">
                <header><h3>Pagos registrados</h3><small id="resumenHistorialPagos"></small></header>
                <div class="table-wrap"><table class="data-table payment-history">
                    <thead><tr><th>Fecha</th><th>Método</th><th>Importe</th><th>Saldo</th><th>Detalle</th><?php if ($puedeEditar): ?><th></th><?php endif; ?></tr></thead>
                    <tbody id="tablaHistorialPagos"></tbody>
                </table></div>
            </section>
            <?php if ($puedeEditar): ?>
            <section class="payment-section">
                <header><h3>Registrar pago</h3><small>Con fecha de hoy</small></header>
                <form id="formCobro" class="payment-workflow" autocomplete="off">
                    <div id="filasPago" class="payment-method-list"></div>
                    <button type="button" class="btn-secondary btn-small" id="btnAgregarMetodo">+ Agregar otro método</button>
                    <div class="payment-final-balance"><span>Saldo después de estos pagos</span><strong id="cobroSaldoFinal">Gs. 0</strong></div>
                    <small class="help-text">Deje el importe vacío para no cargar esa fila; un 0 escrito se registra como pago de Gs. 0. Si cierra, lo cargado queda como borrador.</small>
                    <div id="mensajeCobro" class="mensaje"></div>
                </form>
            </section>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-footer" style="gap:10px">
        <?php if ($puedeEditar): ?><button type="button" class="btn-secondary" onclick="descartarBorrador()">Descartar borrador</button><?php endif; ?>
        <button type="button" class="btn-secondary" onclick="cerrarPagos()">Cerrar</button>
        <?php if ($puedeEditar): ?><button type="submit" form="formCobro" id="btnConfirmarCobro">Confirmar</button><?php endif; ?>
    </div>
</dialog>

<script>
const puedeEditarPacientes = <?= $puedeEditar ? 'true' : 'false' ?>;
const moduloPersonas = <?= json_encode($moduloPersonas) ?>;
const hoyAplicacion = <?= json_encode($hoyAplicacion) ?>;
let pacientes = [], escuelas = [];
let finanzas = {anio:<?= $anioAplicacion ?>, arreadores:[], convenios:[], asignaciones:[], metodos_pago:[]};
let filasVisibles = []; // lo que se ve en la tabla con los filtros aplicados
let cobro = null; // {paciente, convenio, tarifa, pagado, saldo}
const storageEscuela = 'salus.postulantes.escuela_cobro';
const storageConvenio = 'salus.postulantes.convenio_cobro';
const storagePagoAbierto = 'salus.postulantes.pago_abierto';
const claveBorrador = id => `salus.postulantes.borrador_pago.${Number(id)}`;
const almacen = {
    get(k){try{return localStorage.getItem(k);}catch(e){return null;}},
    set(k,v){try{localStorage.setItem(k,v);}catch(e){}},
    del(k){try{localStorage.removeItem(k);}catch(e){}}
};

/* ---------- Formatos ---------- */
function dinero(valor){
    return new Intl.NumberFormat('es-PY',{style:'currency',currency:'PYG',minimumFractionDigits:0,maximumFractionDigits:0}).format(Number(valor)||0);
}
function numeroMonto(valor){return Number(String(valor??'').replace(/\.00$/,'').replace(/\D/g,''))||0;}
// Vacío queda vacío; un 0 escrito se conserva como "0".
function formatearMiles(valor){if(String(valor??'').replace(/\.00$/,'').replace(/\D/g,'')==='')return '';return numeroMonto(valor).toLocaleString('es-PY');}
function fechaHora(valor){
    if(!valor)return '—';
    const partes=String(valor).split(' ')[0].split('-');
    return partes.length===3?`${partes[2]}/${partes[1]}/${partes[0]}`:Salus.escape(valor);
}

/* ---------- Carga de datos ---------- */
async function cargarCatalogos() {
    escuelas = await Salus.json('backend/designacionesDAO.php?q=listar');
    const opcionesEscuela=escuelas.map(d=>`<option value="${Number(d.id_escuela)}">${Salus.escape(d.nombre)}</option>`).join('');
    selectorEscuelaCobro.innerHTML='<option value="">Todas las escuelas</option>'+opcionesEscuela;
    if (puedeEditarPacientes) id_escuela.innerHTML='<option value="">Sin especificar</option>'+opcionesEscuela;
}
async function cargar(){
    try{pacientes=await Salus.json('backend/postulantesDAO.php?q=listar');renderTabla();}
    catch(error){Salus.toast(error.message,'error');}
}
async function cargarFinanzas(){
    try{finanzas=await Salus.json('backend/postulantesFinanzasDAO.php?q=resumen');renderTabla();}
    catch(error){Salus.toast(error.message,'error');}
}

/* ---------- Contexto persistente: escuela y convenio ---------- */
function escuelaContexto(){return escuelas.find(e=>Number(e.id_escuela)===Number(selectorEscuelaCobro.value));}
function conveniosDeEscuela(idEscuela){
    return (finanzas.convenios||[]).filter(c=>Number(c.activo)&&Number(c.id_escuela)===Number(idEscuela));
}
function convenioContexto(){
    if(!puedeEditarPacientes)return null;
    return (finanzas.convenios||[]).find(c=>Number(c.id_arreador_escuela)===Number(selectorConvenioCobro.value))||null;
}
function cargarConveniosContexto(idSeleccionado=''){
    if(!puedeEditarPacientes)return;
    const disponibles=conveniosDeEscuela(selectorEscuelaCobro.value);
    selectorConvenioCobro.disabled=!selectorEscuelaCobro.value;
    selectorConvenioCobro.innerHTML=(selectorEscuelaCobro.value?'<option value="">Seleccione un convenio</option>':'<option value="">Seleccione primero una escuela</option>')
        +disponibles.map(c=>`<option value="${Number(c.id_arreador_escuela)}">${Salus.escape(c.arreador)} · orden desde ${String(Number(c.orden_inicio)||1).padStart(3,"0")} · H ${dinero(c.monto_total_hombre)} · M ${dinero(c.monto_total_mujer)}</option>`).join('');
    if(disponibles.some(c=>String(c.id_arreador_escuela)===String(idSeleccionado)))selectorConvenioCobro.value=String(idSeleccionado);
    else if(disponibles.length===1)selectorConvenioCobro.value=String(disponibles[0].id_arreador_escuela);
    guardarContexto();
}
function guardarContexto(){
    almacen.set(storageEscuela,selectorEscuelaCobro.value||'');
    if(puedeEditarPacientes&&selectorConvenioCobro.value)almacen.set(storageConvenio,selectorConvenioCobro.value);
    else almacen.del(storageConvenio);
}
function restaurarContexto(){
    const escuelaGuardada=almacen.get(storageEscuela)||'';
    if([...selectorEscuelaCobro.options].some(o=>o.value===escuelaGuardada))selectorEscuelaCobro.value=escuelaGuardada;
    cargarConveniosContexto(almacen.get(storageConvenio)||'');
    if(puedeEditarPacientes&&!id_paciente.value)id_escuela.value=selectorEscuelaCobro.value;
}
function borrarContexto(){
    almacen.del(storageEscuela);almacen.del(storageConvenio);
    selectorEscuelaCobro.value='';cargarConveniosContexto();
    if(puedeEditarPacientes&&!id_paciente.value)id_escuela.value='';
    renderTabla();
}

/* ---------- Tabla: postulantes registrados (postulantes_arreador_postulante) ---------- */
function asignacionDe(idPaciente){return (finanzas.asignaciones||[]).find(a=>Number(a.id_paciente)===Number(idPaciente));}
function tarifaPara(p,convenio){return Number(p.es_femenino)?Number(convenio.monto_total_mujer):Number(convenio.monto_total_hombre);}
function renderTabla(){
    const termino=(filtroPacientes.value||'').trim().toLowerCase();
    const fecha=filtroFecha.value;
    const idEscuela=selectorEscuelaCobro.value;
    const convenio=convenioContexto(); // null si no hay convenio elegido (o sin permiso de edición)
    const escuela=escuelaContexto();
    tituloEscuela.textContent=(escuela?`${escuela.nombre} · ${finanzas.anio}`:`Todas las escuelas · ${finanzas.anio}`)+(convenio?` · ${convenio.arreador}`:'');
    const porId=new Map(pacientes.map(p=>[Number(p.id_paciente),p]));
    // La fecha de la tabla es la del registro en postulantes, no la del alta del paciente.
    const filas=(finanzas.asignaciones||[]).map(a=>({a,p:porId.get(Number(a.id_paciente))})).filter(({a,p})=>p
        &&(!idEscuela||Number(a.id_escuela_convenio)===Number(idEscuela))
        &&(!convenio||Number(a.id_arreador_escuela)===Number(convenio.id_arreador_escuela))
        &&(!fecha||a.fecha_registro===fecha)
        &&(!termino||`${p.nombre} ${p.ci} ${p.codigo_generado||''}`.toLowerCase().includes(termino)));
    filas.sort((x,y)=>String(y.a.derivado_en).localeCompare(String(x.a.derivado_en))||Number(y.p.id_paciente)-Number(x.p.id_paciente));
    filasVisibles=filas.map(f=>f.p);
    let sumaTotal=0,sumaPagado=0,sumaSaldo=0;
    tabla.innerHTML=filas.length?filas.map(({a,p})=>{
        const total=Number(a.monto_total),pagado=Number(a.pagado),saldo=Number(a.saldo);
        sumaTotal+=total;sumaPagado+=pagado;sumaSaldo+=saldo;
        const estado=saldo<=0?'<span class="badge badge-success">Pagado</span>'
            :pagado>0?'<span class="badge badge-warn">Parcial</span>':'<span class="badge badge-muted">Pendiente</span>';
        const borrador=puedeEditarPacientes&&almacen.get(claveBorrador(p.id_paciente))?' <span class="badge badge-warn">Borrador</span>':'';
        const codigo=p.codigo?` · Cód. ${Salus.escape(p.codigo)}`:'';
        const acciones=`<button type="button" class="btn-small btn-secondary" aria-haspopup="menu" onclick="abrirMenuAcciones(event,${Number(p.id_paciente)},${Number(a.id_arreador_postulante)},${saldo>0?1:0})">Acciones ▾</button>`;
        return `<tr>
            <td><strong>${Salus.escape(p.orden_dia)} · ${Salus.escape(p.nombre)}</strong><br><small>ID ${Number(p.id_paciente)}${codigo} · ${Salus.escape(p.ci)} · ${Salus.escape(p.sexo)} · ${fechaHora(a.fecha_registro)}${idEscuela?'':' · '+Salus.escape(a.escuela)}</small><br>${estado}${borrador}</td>
            <td class="finance-money">${dinero(total)}</td>
            <td class="finance-money">${dinero(pagado)}</td>
            <td class="finance-money"><strong class="${saldo<=0?'estado-activo':''}">${dinero(saldo)}</strong></td>
            <td><div class="table-actions">${acciones}</div></td>
        </tr>`;
    }).join(''):'<tr><td colspan="5" class="empty-state">No se encontraron postulantes.</td></tr>';
    resumen.textContent=`${filas.length} postulante(s) · Total ${dinero(sumaTotal)} · Pagado ${dinero(sumaPagado)} · Saldo ${dinero(sumaSaldo)}`;
}

/* ---------- Menú de acciones de cada fila ---------- */
const menuAcciones=document.createElement('div');
menuAcciones.className='actions-menu';menuAcciones.setAttribute('role','menu');menuAcciones.hidden=true;
document.body.appendChild(menuAcciones);
function cerrarMenuAcciones(){menuAcciones.hidden=true;}
function abrirMenuAcciones(event,idPaciente,idAsignacion,conSaldo){
    event.stopPropagation();
    const boton=event.currentTarget;
    if(!menuAcciones.hidden&&menuAcciones.dataset.origen===String(idPaciente)){cerrarMenuAcciones();return;}
    const opciones=[
        puedeEditarPacientes||idAsignacion?['Gestion de Pagos',()=>abrirPagos(idPaciente),'']:null,
        puedeEditarPacientes?['Editar Postulante',()=>editar(idPaciente),'']:null,
        puedeEditarPacientes?['Desactivar',()=>eliminar(idPaciente),'peligro']:null
    ].filter(Boolean);
    menuAcciones.innerHTML='';
    opciones.forEach(([texto,accion,clase])=>{
        const item=document.createElement('button');item.type='button';item.setAttribute('role','menuitem');item.textContent=texto;if(clase)item.className=clase;
        item.addEventListener('click',()=>{cerrarMenuAcciones();accion();});
        menuAcciones.appendChild(item);
    });
    menuAcciones.dataset.origen=String(idPaciente);menuAcciones.hidden=false;
    // Se ubica debajo del botón; si no entra, arriba.
    const caja=boton.getBoundingClientRect(),alto=menuAcciones.offsetHeight,ancho=menuAcciones.offsetWidth;
    const arriba=caja.bottom+alto+8>window.innerHeight?caja.top-alto-4:caja.bottom+4;
    menuAcciones.style.top=`${Math.max(8,arriba)}px`;
    menuAcciones.style.left=`${Math.max(8,Math.min(caja.right-ancho,window.innerWidth-ancho-8))}px`;
    menuAcciones.querySelector('button')?.focus();
}
document.addEventListener('click',event=>{if(!menuAcciones.contains(event.target))cerrarMenuAcciones();});
document.addEventListener('keydown',event=>{if(event.key==='Escape')cerrarMenuAcciones();});
window.addEventListener('scroll',cerrarMenuAcciones,true);
window.addEventListener('resize',cerrarMenuAcciones);

/* ---------- Modal único de pagos: historial, edición y nuevos pagos ---------- */
// Sin registro previo usa el convenio de la barra superior; con registro, el suyo.
let historialPagos=null; // {idAsignacion, pagos}
function abrirPagos(idPaciente){
    const p=pacientes.find(item=>Number(item.id_paciente)===Number(idPaciente));if(!p)return;
    const a=asignacionDe(p.id_paciente);
    const convenio=a?(finanzas.convenios||[]).find(c=>Number(c.id_arreador_escuela)===Number(a.id_arreador_escuela)):convenioContexto();
    if(!convenio){Salus.toast('Seleccione la escuela y el convenio en la barra superior.','warn');selectorEscuelaCobro.focus();return;}
    const tarifa=tarifaPara(p,convenio),pagado=Number(a?.pagado||0),saldo=Math.max(0,tarifa-pagado);
    cobro={paciente:p,convenio,tarifa,pagado,saldo,registrado:Boolean(a),idAsignacion:a?Number(a.id_arreador_postulante):null};
    tituloPagos.textContent=Salus.ordenNombre(p);
    resumenCobro.textContent=`${a?'':'Registro nuevo · '}${convenio.escuela} · ${convenio.arreador} · ${Number(p.es_femenino)?'Tarifa mujer':'Tarifa hombre'}`;
    cobroPostulante.textContent=`CI ${p.ci}`;
    cobroTarifa.textContent=dinero(tarifa);cobroPagado.textContent=dinero(pagado);cobroSaldoInicial.textContent=dinero(saldo);
    cobroSaldoInicial.classList.toggle('estado-activo',saldo<=0);
    cargarHistorialPagos();
    if(puedeEditarPacientes){
        mensajeCobro.textContent='';filasPago.innerHTML='';
        let borrador=null;try{borrador=JSON.parse(almacen.get(claveBorrador(p.id_paciente))||'null');}catch(e){}
        const filas=Array.isArray(borrador?.filas)&&borrador.filas.length?borrador.filas:[{}];
        filas.forEach(f=>agregarFilaPago(f));
        almacen.set(storagePagoAbierto,String(p.id_paciente));
    }
    if(!modalPagos.open)modalPagos.showModal();
}
// Efectivo es el predeterminado; las filas siguientes toman el próximo método libre.
function metodoPredeterminado(){
    const usados=new Set([...filasPago.querySelectorAll('.pago-metodo')].map(c=>c.value));
    return (finanzas.metodos_pago||[]).find(m=>!usados.has(String(m.id_metodo_pago)))?.id_metodo_pago||'';
}
function agregarFilaPago(datos={}){
    const fila=document.createElement('div');fila.className='payment-method-row';
    fila.innerHTML=`<div class="campo pago-campo-metodo"><label>Método</label><select class="pago-metodo"><option value="">Seleccione</option>${(finanzas.metodos_pago||[]).map(m=>`<option value="${Number(m.id_metodo_pago)}">${Salus.escape(m.nombre)}</option>`).join('')}</select></div>
        <div class="campo pago-campo-monto"><label>Importe</label><input class="pago-monto money-input" inputmode="numeric" autocomplete="off" placeholder="Gs."></div>
        <button type="button" class="btn-danger btn-small pago-quitar" aria-label="Quitar método">Quitar</button>
        <div class="campo pago-campo-observacion"><label>Observación</label><input class="pago-observacion" maxlength="500" placeholder="Opcional"></div>
        <div class="payment-row-balance"><span>Saldo después</span><strong class="pago-saldo">Gs. 0</strong></div>`;
    fila.querySelector('.pago-metodo').value=String(datos.metodo||metodoPredeterminado()||'');
    fila.querySelector('.pago-monto').value=formatearMiles(datos.monto);
    fila.querySelector('.pago-observacion').value=datos.observacion||'';
    fila.querySelector('.pago-monto').addEventListener('input',event=>{event.target.value=formatearMiles(event.target.value);});
    fila.querySelector('.pago-quitar').addEventListener('click',()=>{fila.remove();if(!filasPago.children.length)agregarFilaPago();cambioEnCobro();});
    filasPago.appendChild(fila);cambioEnCobro();
}
function leerFilasPago(){
    return [...filasPago.querySelectorAll('.payment-method-row')].map(fila=>{
        const montoTexto=fila.querySelector('.pago-monto').value.trim();
        return {metodo:fila.querySelector('.pago-metodo').value,montoTexto,monto:numeroMonto(montoTexto),observacion:fila.querySelector('.pago-observacion').value.trim()};
    });
}
// Una fila cuenta como pago cuando tiene importe escrito (0 incluido) u observación.
function filasConPago(){return leerFilasPago().filter(f=>f.montoTexto!==''||f.observacion);}
// Recalcula saldos y guarda el borrador en el navegador.
function cambioEnCobro(){
    if(!cobro||!puedeEditarPacientes)return;
    validarMetodosRepetidos();
    let saldo=cobro.saldo;
    filasPago.querySelectorAll('.payment-method-row').forEach(fila=>{
        saldo-=numeroMonto(fila.querySelector('.pago-monto').value);
        const salida=fila.querySelector('.pago-saldo');salida.textContent=dinero(Math.max(0,saldo));salida.classList.toggle('estado-inactivo',saldo<0);
    });
    cobroSaldoFinal.textContent=saldo<0?`Excede en ${dinero(-saldo)}`:dinero(saldo);cobroSaldoFinal.classList.toggle('estado-inactivo',saldo<0);
    cobro.saldoFinal=saldo;
    const conPago=filasConPago().length>0;
    btnConfirmarCobro.textContent=conPago||cobro.registrado?'Confirmar pagos':'Registrar sin pago';
    const filas=leerFilasPago().map(({metodo,montoTexto,observacion})=>({metodo,monto:montoTexto,observacion}));
    if(conPago)almacen.set(claveBorrador(cobro.paciente.id_paciente),JSON.stringify({filas}));
    else almacen.del(claveBorrador(cobro.paciente.id_paciente));
}
function validarMetodosRepetidos(){
    const vistos=new Set();let repetido=false;
    filasPago.querySelectorAll('.pago-metodo').forEach(campo=>{campo.setCustomValidity('');if(campo.value&&vistos.has(campo.value)){campo.setCustomValidity('Este método ya fue seleccionado.');repetido=true;}vistos.add(campo.value);});
    return !repetido;
}
function cerrarPagos(){almacen.del(storagePagoAbierto);if(modalPagos.open)modalPagos.close();cobro=null;historialPagos=null;renderTabla();}
function descartarBorrador(){
    if(!cobro||!confirm('¿Descartar lo cargado en este cobro?'))return;
    almacen.del(claveBorrador(cobro.paciente.id_paciente));cerrarPagos();
}
const columnasHistorial=puedeEditarPacientes?6:5;
async function cargarHistorialPagos(){
    historialPagos=null;
    if(!cobro?.idAsignacion){
        resumenHistorialPagos.textContent='';
        tablaHistorialPagos.innerHTML=`<tr><td colspan="${columnasHistorial}" class="empty-state">Todavía no está registrado. Al confirmar queda registrado en postulantes.</td></tr>`;
        return;
    }
    tablaHistorialPagos.innerHTML=`<tr><td colspan="${columnasHistorial}" class="empty-state">Cargando…</td></tr>`;
    try{
        const r=await Salus.json(`backend/postulantesFinanzasDAO.php?q=pagos&id=${cobro.idAsignacion}`);
        historialPagos={idAsignacion:cobro.idAsignacion,pagos:r.pagos};
        resumenHistorialPagos.textContent=`${r.pagos.length} pago(s)`;
        pintarHistorialPagos();
    }catch(error){tablaHistorialPagos.innerHTML=`<tr><td colspan="${columnasHistorial}" class="empty-state">${Salus.escape(error.message)}</td></tr>`;}
}
function pintarHistorialPagos(){
    const pagos=historialPagos?.pagos||[];
    tablaHistorialPagos.innerHTML=pagos.length?pagos.map(p=>`<tr data-pago="${Number(p.id_pago)}">
        <td>${fechaHora(p.fecha)}<br><small>${Salus.escape(String(p.fecha).split(' ')[1]||'')}</small></td>
        <td>${Salus.escape(p.metodo_pago)}</td>
        <td class="finance-money">${dinero(p.monto)}</td>
        <td class="finance-money">${dinero(p.saldo)}</td>
        <td><small>${Salus.escape(p.registrado_por)}</small>${p.observacion?`<br>${Salus.escape(p.observacion)}`:''}${p.modificado_en?`<br><small>Modificado por ${Salus.escape(p.modificado_por||'—')} · ${fechaHora(p.modificado_en)}</small>`:''}</td>
        ${puedeEditarPacientes?`<td><button type="button" class="btn-small btn-secondary" onclick="editarPagoEnLinea(${Number(p.id_pago)})">Editar</button></td>`:''}
    </tr>`).join(''):`<tr><td colspan="${columnasHistorial}" class="empty-state">Todavía no se registraron pagos.</td></tr>`;
}
// La edición se hace dentro de la misma fila del historial.
function editarPagoEnLinea(idPago){
    const pago=historialPagos?.pagos.find(p=>Number(p.id_pago)===Number(idPago));if(!pago)return;
    pintarHistorialPagos();
    const metodos=[...(finanzas.metodos_pago||[])];
    if(!metodos.some(m=>Number(m.id_metodo_pago)===Number(pago.id_metodo_pago)))metodos.push({id_metodo_pago:pago.id_metodo_pago,nombre:pago.metodo_pago});
    const fila=tablaHistorialPagos.querySelector(`tr[data-pago="${Number(idPago)}"]`);
    fila.classList.add('payment-editing');
    fila.innerHTML=`<td colspan="${columnasHistorial}"><form class="payment-edit-form">
        <div class="campo"><label>Método</label><select class="editar-metodo">${metodos.map(m=>`<option value="${Number(m.id_metodo_pago)}">${Salus.escape(m.nombre)}</option>`).join('')}</select></div>
        <div class="campo"><label>Importe</label><input class="editar-monto money-input" inputmode="numeric" placeholder="0"></div>
        <div class="campo"><label>Observación</label><input class="editar-observacion" maxlength="500" placeholder="Opcional"></div>
        <div class="payment-edit-actions"><button type="button" class="btn-small btn-secondary editar-cancelar">Cancelar</button><button type="submit" class="btn-small">Guardar</button></div>
        <div class="mensaje editar-mensaje"></div>
    </form></td>`;
    const form=fila.querySelector('form'),monto=form.querySelector('.editar-monto');
    form.querySelector('.editar-metodo').value=String(Number(pago.id_metodo_pago));
    monto.value=formatearMiles(Math.round(Number(pago.monto)));
    form.querySelector('.editar-observacion').value=pago.observacion||'';
    monto.addEventListener('input',e=>{e.target.value=formatearMiles(e.target.value);});
    form.querySelector('.editar-cancelar').addEventListener('click',pintarHistorialPagos);
    form.addEventListener('submit',async e=>{
        e.preventDefault();const mensaje=form.querySelector('.editar-mensaje');mensaje.textContent='';
        if(monto.value.trim()===''){mensaje.textContent='Ingrese el importe (puede ser 0).';return;}
        const boton=form.querySelector('button[type="submit"]');boton.disabled=true;
        try{
            const r=await Salus.json('backend/postulantesFinanzasDAO.php?q=editar_pago',{method:'POST',body:Salus.form({
                id_pago:idPago,id_metodo_pago:form.querySelector('.editar-metodo').value,
                monto:String(numeroMonto(monto.value)),observacion:form.querySelector('.editar-observacion').value.trim()})});
            Salus.toast(`Pago actualizado. Saldo: ${dinero(r.saldo)}.`);
            await cargarFinanzas();if(cobro)abrirPagos(cobro.paciente.id_paciente);
        }catch(error){mensaje.textContent=error.message;boton.disabled=false;}
    });
    monto.focus();
}

/* ---------- Formulario del postulante ---------- */
function editar(id) {
    const p=pacientes.find(item=>Number(item.id_paciente)===Number(id)); if(!p)return;
    id_paciente.value=p.id_paciente; nombre.value=p.nombre; codigo.value=p.codigo||''; ci.value=p.ci;
    fecha_nacimiento.value=p.fecha_nacimiento; es_femenino.value=p.es_femenino; grupo_sanguineo.value=p.grupo_sanguineo||'';
    id_escuela.value=p.id_escuela||''; telefono.value=p.telefono||''; url_foto.value=p.url_foto||''; id_pais.value=p.id_pais||'';
    nombre.focus();
}
function limpiar(){
    frmPersona.reset();id_paciente.value='';url_foto.value='';id_pais.value='';telefono.value='';mensaje.textContent='';
    if(selectorEscuelaCobro.value)id_escuela.value=selectorEscuelaCobro.value;
}
// Exporta a Excel o PDF exactamente los postulantes que se ven en la tabla.
function exportarLista(formato){
    if(!filasVisibles.length){Salus.toast('No hay postulantes en la lista para exportar.','warn');return;}
    const filtros=[];
    const escuela=escuelaContexto();
    if(escuela) filtros.push('escuela '+escuela.nombre);
    const convenio=convenioContexto();
    if(convenio) filtros.push('convenio '+convenio.arreador);
    if(filtroPacientes.value.trim()) filtros.push('búsqueda "'+filtroPacientes.value.trim()+'"');
    if(filtroFecha.value) filtros.push('fecha de registro '+filtroFecha.value.split('-').reverse().join('/'));
    const form=document.createElement('form');
    form.method='POST'; form.action='backend/pacientesExportar.php';
    if(formato==='pdf') form.target='_blank';
    const campos={formato, entidad:moduloPersonas, reporte:tipoExportacion.value, ids:filasVisibles.map(p=>p.id_paciente).join(','), filtro:filtros.join(', '), id_convenio:convenio?convenio.id_arreador_escuela:'', fecha_registro:filtroFecha.value?filtroFecha.value.split('-').reverse().join('/'):'', csrf_token:Salus.csrf};
    Object.entries(campos).forEach(([name,value])=>{
        const input=document.createElement('input'); input.type='hidden'; input.name=name; input.value=value; form.appendChild(input);
    });
    document.body.appendChild(form); form.submit(); form.remove();
}
// Busca la cédula primero en nuestros postulantes y, si no está, en el padrón.
async function buscarCedula(){
    const cedula=ci.value.trim();
    if(!cedula){Salus.toast('Ingrese un número de cédula.','warn');ci.focus();return;}
    btnBuscarCedula.disabled=true;
    try{
        const r=await Salus.json('backend/cedulasDAO.php?q=buscar&cedula='+encodeURIComponent(cedula));
        if(!r.encontrado){Salus.toast('No se encontró la cédula. Cargue los datos a mano.','warn');return;}

        if(r.fuente==='sistema'){
            if(r.activo&&pacientes.some(p=>Number(p.id_paciente)===r.id_paciente)){
                editar(r.id_paciente);
                const a=asignacionDe(r.id_paciente);
                if(a){Salus.toast(`Ya está registrado en postulantes desde el ${fechaHora(a.fecha_registro)} (${a.escuela} · ${a.arreador}). Use “Pagar” en la tabla para cobrar el saldo.`,'warn');return;}
                abrirPagos(r.id_paciente);
            }else{
                id_paciente.value='';
                nombre.value=r.nombre||''; fecha_nacimiento.value=r.fecha_nacimiento||''; es_femenino.value=r.sexo==='F'?'1':'0';
                Salus.toast(`Esta cédula pertenece a un postulante desactivado (ID ${r.id_paciente}). Reactívelo antes de volver a cargarlo.`,'warn');
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
async function eliminar(id){
    if(!confirm('¿Desactivar este postulante?'))return;
    try{await Salus.json('backend/postulantesDAO.php?q=eliminar',{method:'POST',body:Salus.form({id})});Salus.toast('Postulante desactivado.');cargar();}
    catch(error){Salus.toast(error.message,'error');}
}

/* ---------- Eventos ---------- */
selectorEscuelaCobro.addEventListener('change',()=>{
    cargarConveniosContexto();
    if(puedeEditarPacientes&&!id_paciente.value)id_escuela.value=selectorEscuelaCobro.value;
    renderTabla();
});
btnBorrarContexto.addEventListener('click',borrarContexto);
filtroPacientes.addEventListener('input',renderTabla);
filtroFecha.addEventListener('change',renderTabla);
if (puedeEditarPacientes) {
    selectorConvenioCobro.addEventListener('change',()=>{guardarContexto();renderTabla();});
    frmPersona.addEventListener('submit',async event=>{
        event.preventDefault(); mensaje.textContent='';
        const esNuevo=!id_paciente.value;
        if(esNuevo&&(!selectorEscuelaCobro.value||!selectorConvenioCobro.value)){mensaje.textContent='Seleccione primero la escuela y el convenio en la barra superior.';selectorEscuelaCobro.focus();return;}
        if(esNuevo)id_escuela.value=selectorEscuelaCobro.value;
        try{
            const resultado=await Salus.json('backend/postulantesDAO.php?q=guardar',{method:'POST',body:new FormData(frmPersona)});
            Salus.toast('Postulante guardado.');
            await Promise.all([cargar(),cargarFinanzas()]);limpiar();
            const p=pacientes.find(item=>Number(item.id_paciente)===Number(resultado.id_paciente));
            if(p&&!asignacionDe(p.id_paciente))abrirPagos(p.id_paciente);
        }catch(error){mensaje.textContent=error.message;}
    });
    // Enter en Documento busca en el padrón en vez de guardar el formulario.
    ci.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();buscarCedula();}});
    btnAgregarMetodo.addEventListener('click',()=>agregarFilaPago());
    formCobro.addEventListener('input',cambioEnCobro);
    formCobro.addEventListener('change',cambioEnCobro);
    // Esc cierra el modal: el borrador queda guardado.
    modalPagos.addEventListener('cancel',event=>{event.preventDefault();cerrarPagos();});
    formCobro.addEventListener('submit',async event=>{
        event.preventDefault();mensajeCobro.textContent='';
        if(!cobro)return;
        if(!validarMetodosRepetidos()){formCobro.reportValidity();return;}
        const filas=filasConPago();
        if(filas.some(f=>!f.metodo)){mensajeCobro.textContent='Seleccione el método de cada pago, o quite la fila.';return;}
        if(filas.some(f=>f.montoTexto==='')){mensajeCobro.textContent='Ingrese el importe de cada pago (puede ser 0), o quite la fila.';return;}
        if(!filas.length&&cobro.registrado){mensajeCobro.textContent='Cargue al menos un pago.';return;}
        cambioEnCobro();if(cobro.saldoFinal<0){mensajeCobro.textContent='La suma de los pagos supera el saldo pendiente.';return;}
        const pagos=filas.map(f=>({id_metodo_pago:f.metodo,monto:String(f.monto),observacion:f.observacion}));
        const cuerpo=Salus.form({id_paciente:cobro.paciente.id_paciente,id_arreador_escuela:cobro.convenio.id_arreador_escuela,pagos:JSON.stringify(pagos)});
        const boton=document.querySelector('button[form="formCobro"]');boton.disabled=true;
        try{
            const resultado=await Salus.json('backend/postulantesFinanzasDAO.php?q=registrar_cobro',{method:'POST',body:cuerpo});
            almacen.del(claveBorrador(cobro.paciente.id_paciente));
            Salus.toast(pagos.length?`Pagos registrados. Saldo: ${dinero(resultado.saldo)}.`:`Postulante registrado. Saldo: ${dinero(resultado.saldo)}.`);
            cerrarPagos();await Promise.all([cargar(),cargarFinanzas()]);
        }catch(error){mensajeCobro.textContent=error.message;}
        finally{boton.disabled=false;}
    });
}
(async()=>{
    try{
        filtroFecha.value=hoyAplicacion;
        await cargarCatalogos();await cargarFinanzas();restaurarContexto();await cargar();
        // Si la página se recargó con el modal abierto, se vuelve a abrir con su borrador.
        const abierto=puedeEditarPacientes?almacen.get(storagePagoAbierto):null;
        if(abierto&&pacientes.some(p=>Number(p.id_paciente)===Number(abierto)))abrirPagos(abierto);
    }catch(error){Salus.toast(error.message,'error');}
})();
</script>
<?php include 'footer.php'; ?>
