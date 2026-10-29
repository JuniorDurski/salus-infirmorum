<?php
include 'conexion.php';
$usuarioAutenticado = requerirAlgunPermiso($conexion, [
    'postulantes_atencion.ver', 'postulantes_atencion.asignar',
    'postulantes_atencion.iniciar', 'postulantes_atencion.sincronizar',
    'postulantes_atencion.atender', 'postulantes_atencion.comentar',
    'postulantes_atencion.pagar', 'postulantes_atencion.estado',
    'postulantes_atencion.exportar',
]);
$puedeCrearAsignacion = tienePermiso($usuarioAutenticado, 'postulantes_atencion.asignar');
$puedeSincronizar = tienePermiso($usuarioAutenticado, 'postulantes_atencion.sincronizar');
$puedeCambiarEstado = tienePermiso($usuarioAutenticado, 'postulantes_atencion.estado');
$tituloPagina = 'Asignaciones de atención';
include 'menu.php';
?>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Atención</span>
        <h1>Asignaciones de atención</h1>
        <p>Organiza profesionales, escuelas, estudios, atención y pagos por jornada.</p>
    </div>
    <div class="header-actions">
        <?php if ($puedeSincronizar): ?>
            <button class="btn-secondary" type="button" onclick="sincronizarTodoHoy()">↻ Sincronizar todos hoy</button>
        <?php endif; ?>
        <?php if ($puedeCrearAsignacion): ?>
            <button type="button" onclick="nuevaAsignacion()">+ Nueva asignación</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($puedeCrearAsignacion): ?>
<dialog class="modal modal-wide" id="modalAsignacion">
    <div class="modal-header">
        <div><span class="eyebrow">Configuración de jornada</span><h2 id="tituloForm">Nueva asignación</h2></div>
        <button class="icon-button" type="button" onclick="cancelarAsignacion()" aria-label="Cerrar">×</button>
    </div>
    <form id="formAsignacion" class="modal-form">
        <input type="hidden" name="id_asignar_atencion" id="id_asignar_atencion">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-body">
            <p class="notice">Seleccione la fecha y todos los profesionales, escuelas y estudios que formarán parte de la jornada.</p>
            <div class="campo"><label for="fechaAsignacion">Fecha de la jornada *</label><input type="date" name="fecha" id="fechaAsignacion" required></div>
            <div class="attention-selector-grid">
                <div class="campo"><label>Profesionales que atenderán *</label><input type="search" data-filtra="listaUsuarios" placeholder="Buscar profesional…"><div class="checks selection-list" id="listaUsuarios"></div></div>
                <div class="campo"><label>Escuelas *</label><input type="search" data-filtra="listaEscuelas" placeholder="Buscar escuela…"><div class="checks selection-list" id="listaEscuelas"></div></div>
                <div class="campo"><label>Estudios referenciales *</label><input type="search" data-filtra="listaEstudios" placeholder="Buscar estudio…"><div class="checks selection-list" id="listaEstudios"></div></div>
            </div>
            <div id="mensajeAsignacion" class="mensaje"></div>
        </div>
        <div class="modal-footer"><button class="btn-secondary" type="button" onclick="cancelarAsignacion()">Cancelar</button><button type="submit">Guardar y sincronizar</button></div>
    </form>
</dialog>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <div><h2>Jornadas activas</h2><p class="help-text" id="resumenAsignaciones">Cargando…</p></div>
        <div class="header-actions"><input type="date" id="filtroFecha" aria-label="Filtrar por fecha"><button class="btn-secondary" type="button" onclick="filtroFecha.value='';cargarAsignaciones()">Ver todas</button></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Fecha</th><th>Escuelas</th><th>Profesionales</th><th>Postulantes atendidos</th><th>Liquidación</th><th></th></tr></thead>
        <tbody id="tablaAsignaciones"></tbody>
    </table></div>
</section>

<section class="card attention-workspace" id="panelJornada" hidden>
    <div class="card-header">
        <div><span class="eyebrow">Jornada seleccionada</span><h2 id="detalleTitulo">Atención</h2><p class="help-text" id="detalleResumen"></p></div>
        <div class="attention-workspace-actions">
            <label class="status-filter" for="filtroEstadoPostulantes"><span>Mostrar</span><select id="filtroEstadoPostulantes"><option value="todos">Todos</option><option value="pendientes">Pendientes</option><option value="atendidos">Atendidos</option></select></label>
            <button class="icon-button" type="button" onclick="cerrarDetalle()" aria-label="Cerrar">×</button>
        </div>
    </div>
    <div class="attention-toolbar" id="accionesJornada"></div>
    <div class="table-wrap"><table class="data-table attention-table">
        <thead><tr><th class="attention-order">N.º</th><th>Postulante</th><th>Escuela</th><th>Estudios</th><th>Observaciones</th><th>Liquidación</th><th>Estado</th></tr></thead>
        <tbody id="tablaPostulantes"></tbody>
        <tfoot id="pieLiquidacion"></tfoot>
    </table></div>
</section>

<?php if ($puedeCambiarEstado): ?>
<section class="card attention-disabled-section">
    <div class="card-header">
        <div><span class="eyebrow">Historial recuperable</span><h2>Asignaciones desactivadas</h2><p class="help-text" id="resumenAsignacionesDesactivadas">Cargando…</p></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Fecha</th><th>Escuelas</th><th>Profesionales</th><th>Postulantes atendidos</th><th>Liquidación</th><th></th></tr></thead>
        <tbody id="tablaAsignacionesDesactivadas"></tbody>
    </table></div>
</section>
<?php endif; ?>

<dialog class="modal" id="modalEstudios">
    <div class="modal-header"><div><span class="eyebrow">Atención</span><h2 id="estudiosTitulo">Estudios del postulante</h2></div><button class="icon-button" type="button" onclick="modalEstudios.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body"><div class="checks" id="estudiosContenido"></div></div>
    <div class="modal-footer"><button class="btn-secondary" type="button" onclick="modalEstudios.close()">Cancelar</button><button id="btnGuardarEstudios" type="button" onclick="guardarEstudiosMultiples()">Guardar atención</button></div>
</dialog>

<dialog class="modal observation-modal" id="modalObservaciones">
    <div class="modal-header"><div><span class="eyebrow">Historial del postulante</span><h2 id="observacionesTitulo">Observaciones</h2></div><button class="icon-button" type="button" onclick="modalObservaciones.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body observation-body">
        <div class="observation-list" id="observacionesContenido"></div>
        <div id="formObservacionModal" class="observation-compose" hidden>
            <label for="textoObservacion">Nueva observación</label>
            <textarea id="textoObservacion" maxlength="1500" placeholder="Escriba una observación sobre el postulante…"></textarea>
            <small>El sistema registrará automáticamente su nombre y la fecha.</small>
        </div>
    </div>
    <div class="modal-footer"><button class="btn-secondary" type="button" onclick="modalObservaciones.close()">Cerrar</button><button id="btnAgregarObservacion" type="button" onclick="agregarObservacionModal()" hidden>Guardar observación</button></div>
</dialog>

<script>
const puedeCrearAsignacion = <?= $puedeCrearAsignacion ? 'true' : 'false' ?>;
const puedeSincronizarAsignaciones = <?= $puedeSincronizar ? 'true' : 'false' ?>;
const puedeCambiarEstadoAsignaciones = <?= $puedeCambiarEstado ? 'true' : 'false' ?>;
let catalogos={escuelas:[],usuarios:[],estudios:[],permisos:{},hoy:''};
let asignaciones=[], asignacionesDesactivadas=[], detalleActual=null, postulanteEstudiosActual=null, postulanteObservacionActual=null;
const esc=v=>Salus.escape(v??'');

async function iniciarAtenciones(){
    try{
        catalogos=await Salus.json('backend/atencionesDAO.php?q=catalogos');
        filtroFecha.value=catalogos.hoy;
        if(puedeCrearAsignacion){fechaAsignacion.value=catalogos.hoy;renderSelectores();}
        await cargarAsignaciones();
        if(puedeCambiarEstadoAsignaciones)await cargarAsignacionesDesactivadas();
    }catch(e){Salus.toast(e.message,'error');}
}

function renderSelectores(seleccion={usuarios:[],escuelas:[],estudios:[]}){
    if(!puedeCrearAsignacion)return;
    const ids=a=>a.map(Number);
    listaUsuarios.innerHTML=catalogos.usuarios.map(u=>`<label><input type="checkbox" name="usuarios[]" value="${u.id_usuario}" ${ids(seleccion.usuarios).includes(Number(u.id_usuario))?'checked':''}><span><strong>${esc(u.nombre)}</strong><br><small>@${esc(u.usuario)}</small></span></label>`).join('')||'<small>No hay usuarios con permiso para atender.</small>';
    listaEscuelas.innerHTML=catalogos.escuelas.map(e=>`<label><input type="checkbox" name="escuelas[]" value="${e.id_escuela}" ${ids(seleccion.escuelas).includes(Number(e.id_escuela))?'checked':''}><span><strong>${esc(e.nombre)}</strong><br><small>${esc(e.periodo)}</small></span></label>`).join('');
    listaEstudios.innerHTML=catalogos.estudios.map(e=>`<label><input type="checkbox" name="estudios[]" value="${e.id_estudio}" ${ids(seleccion.estudios).includes(Number(e.id_estudio))?'checked':''}><span>${esc(e.estudio_referencial)}</span></label>`).join('')||'<small>Cree primero un estudio referencial.</small>';
}

document.querySelectorAll('[data-filtra]').forEach(input=>input.addEventListener('input',()=>{
    const termino=input.value.toLowerCase();
    document.querySelectorAll(`#${input.dataset.filtra} label`).forEach(label=>label.hidden=!label.textContent.toLowerCase().includes(termino));
}));

async function cargarAsignaciones(){
    try{
        const filtro=filtroFecha.value?`&fecha=${encodeURIComponent(filtroFecha.value)}`:'';
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=listar'+filtro);
        asignaciones=respuesta.asignaciones;renderAsignaciones();
    }catch(e){Salus.toast(e.message,'error');}
}

function renderAsignaciones(){
    resumenAsignaciones.textContent=`${asignaciones.length} jornada(s)`;
    tablaAsignaciones.innerHTML=asignaciones.length?asignaciones.map(a=>`<tr class="${detalleActual&&Number(detalleActual.id_asignar_atencion)===Number(a.id_asignar_atencion)?'selected-row':''}"><td><strong>${esc(a.fecha)}</strong><br><small>#${a.id_asignar_atencion}</small></td><td>${esc(a.escuelas||'—')}</td><td>${esc(a.profesionales||'—')}</td><td><strong>${a.atendidos}/${a.total}</strong><br><small>atendidos / total</small></td><td>${a.pago_realizado?'<span class="badge badge-success">Pago total realizado</span>':`<span class="badge badge-muted">${a.pagados}/${a.total} pagados</span>`}</td><td><div class="table-actions"><button class="btn-small btn-secondary" onclick="abrirDetalle(${a.id_asignar_atencion})">Abrir jornada</button>${puedeCambiarEstadoAsignaciones?`<button class="btn-small btn-danger" onclick="cambiarEstadoAsignacion(${a.id_asignar_atencion},0)">Desactivar</button>`:''}</div></td></tr>`).join(''):'<tr><td colspan="6" class="empty-state">No hay asignaciones activas para este filtro.</td></tr>';
}

async function cargarAsignacionesDesactivadas(){
    if(!puedeCambiarEstadoAsignaciones)return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=listar&estado=desactivadas');
        asignacionesDesactivadas=respuesta.asignaciones;renderAsignacionesDesactivadas();
    }catch(e){Salus.toast(e.message,'error');}
}

function renderAsignacionesDesactivadas(){
    resumenAsignacionesDesactivadas.textContent=`${asignacionesDesactivadas.length} jornada(s)`;
    tablaAsignacionesDesactivadas.innerHTML=asignacionesDesactivadas.length
        ?asignacionesDesactivadas.map(a=>`<tr><td><strong>${esc(a.fecha)}</strong><br><small>#${a.id_asignar_atencion}</small></td><td>${esc(a.escuelas||'—')}</td><td>${esc(a.profesionales||'—')}</td><td><strong>${a.atendidos}/${a.total}</strong><br><small>atendidos / total</small></td><td>${a.pago_realizado?'<span class="badge badge-success">Pago total realizado</span>':`<span class="badge badge-muted">${a.pagados}/${a.total} pagados</span>`}</td><td><button class="btn-small" onclick="cambiarEstadoAsignacion(${a.id_asignar_atencion},1)">Reactivar</button></td></tr>`).join('')
        :'<tr><td colspan="6" class="empty-state">No hay asignaciones desactivadas.</td></tr>';
}

function nuevaAsignacion(){
    if(!puedeCrearAsignacion)return;
    formAsignacion.reset();id_asignar_atencion.value='';fechaAsignacion.value=catalogos.hoy;tituloForm.textContent='Nueva asignación';mensajeAsignacion.textContent='';renderSelectores();modalAsignacion.showModal();
}
function cancelarAsignacion(){if(!puedeCrearAsignacion)return;modalAsignacion.close();mensajeAsignacion.textContent='';}

if(puedeCrearAsignacion)formAsignacion.addEventListener('submit',async evento=>{
    evento.preventDefault();mensajeAsignacion.textContent='';
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=guardar',{method:'POST',body:new FormData(formAsignacion)});
        Salus.toast(respuesta.mensaje);const fechaGuardada=fechaAsignacion.value;modalAsignacion.close();filtroFecha.value=fechaGuardada||catalogos.hoy;await cargarAsignaciones();await abrirDetalle(respuesta.id_asignar_atencion);
    }catch(e){mensajeAsignacion.textContent=e.message;}
});

filtroFecha.addEventListener('change',cargarAsignaciones);
filtroEstadoPostulantes.addEventListener('change',renderTablaPostulantes);

async function sincronizarTodoHoy(){
    if(!puedeSincronizarAsignaciones)return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=sincronizar_dia',{method:'POST',body:Salus.form({fecha:catalogos.hoy})});
        Salus.toast(respuesta.mensaje);await cargarAsignaciones();if(detalleActual)await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

async function abrirDetalle(id,desplazar=true){
    try{
        const cambiaJornada=Number(detalleActual?.id_asignar_atencion||0)!==Number(id);
        const respuesta=await Salus.json(`backend/atencionesDAO.php?q=detalle&id=${id}`);
        detalleActual=respuesta.asignacion;if(cambiaJornada)filtroEstadoPostulantes.value='todos';renderAsignaciones();renderDetalle();panelJornada.hidden=false;
        if(desplazar)panelJornada.scrollIntoView({behavior:'smooth',block:'start'});
    }catch(e){Salus.toast(e.message,'error');}
}
function cerrarDetalle(){panelJornada.hidden=true;detalleActual=null;renderAsignaciones();}

function renderDetalle(){
    const a=detalleActual;
    detalleTitulo.textContent=`${a.fecha} · ${a.escuelas.map(e=>e.nombre).join(' · ')}`;
    detalleResumen.textContent=`${a.usuarios.map(u=>u.nombre).join(' · ')} · ${a.postulantes.length} postulante(s)`;
    let acciones='';
    if(catalogos.permisos.asignar&&!a.pago_realizado)acciones+='<button class="btn-secondary" onclick="editarCabecera()">Editar asignación</button>';
    if(catalogos.permisos.iniciar&&!a.pago_realizado)acciones+=`<button onclick="accionCabecera('inicio',{empieza_atender:${a.empieza_atender?0:1}})">${a.empieza_atender?'Pausar atención':'Empezar a atender'}</button>`;
    if(catalogos.permisos.estado)acciones+=`<button class="btn-danger" onclick="cambiarEstadoAsignacion(${a.id_asignar_atencion},0)">Desactivar asignación</button>`;
    ['xls','csv','txt','json'].forEach(formato=>acciones+=`<a class="btn btn-secondary btn-small" href="backend/atencionesExportar.php?id=${a.id_asignar_atencion}&formato=${formato}">${formato==='xls'?'Excel':formato.toUpperCase()}</a>`);
    acciones+=a.pago_realizado?'<span class="badge badge-success">Jornada pagada · solo lectura</span>':a.empieza_atender?'<span class="badge badge-success">Atención habilitada</span>':'<span class="badge badge-muted">En espera</span>';
    accionesJornada.innerHTML=acciones;
    renderTablaPostulantes();
    renderPieLiquidacion();
}

function renderTablaPostulantes(){
    if(!detalleActual)return;
    const estado=filtroEstadoPostulantes.value;
    const postulantes=[...detalleActual.postulantes]
        .sort((a,b)=>Number(a.id_paciente)-Number(b.id_paciente))
        .filter(postulante=>estado==='todos'||(estado==='atendidos'&&Number(postulante.atendido)===1)||(estado==='pendientes'&&Number(postulante.atendido)===0));
    tablaPostulantes.innerHTML=postulantes.length
        ?postulantes.map((postulante,indice)=>renderFilaPostulante(postulante,indice+1)).join('')
        :'<tr><td colspan="7" class="empty-state">No hay postulantes para el estado seleccionado.</td></tr>';
}

function comentariosDe(postulante){
    const comentarios=[];
    Object.values(postulante.observacion||{}).forEach(items=>(items||[]).forEach(item=>comentarios.push(item)));
    return comentarios.sort((a,b)=>String(a.fecha).localeCompare(String(b.fecha)));
}

function puedeComentarActual(){
    return !detalleActual.pago_realizado&&catalogos.permisos.comentar&&(detalleActual.editable_profesional||catalogos.permisos.asignar||catalogos.permisos.iniciar||catalogos.permisos.sincronizar||catalogos.permisos.pagar||catalogos.permisos.estado);
}

function renderEstudiosFila(postulante,puedeAtender){
    const estudios=postulante.estudios;
    if(!estudios.length)return '<span class="help-text">Sin estudios</span>';
    if(estudios.length===1){
        const estudio=estudios[0];
        return `<div class="single-study"><label class="inline-study"><input type="checkbox" class="estudio-${postulante.id_postulante_atencion}" value="${estudio.id_estudio}" ${estudio.realizado?'checked':''} ${puedeAtender?'':'disabled'}><span>${esc(estudio.estudio_referencial)}</span></label>${puedeAtender?`<button class="btn-small" type="button" onclick="guardarAtencionDirecta(${postulante.id_postulante_atencion})">Guardar</button>`:''}</div>`;
    }
    const realizados=estudios.filter(e=>Number(e.realizado)).length;
    return `<button class="btn-small btn-secondary" type="button" onclick="abrirEstudios(${postulante.id_postulante_atencion})">Seleccionar estudios (${realizados}/${estudios.length})</button>`;
}

function renderFilaPostulante(postulante,orden){
    const puedeAtender=detalleActual.editable_profesional&&!detalleActual.pago_realizado&&catalogos.permisos.atender;
    const comentarios=comentariosDe(postulante);
    let observaciones='';
    if(comentarios.length)observaciones+=`<button class="btn-small btn-secondary" type="button" onclick="abrirObservaciones(${postulante.id_postulante_atencion})">Observaciones (${comentarios.length})</button>`;
    if(puedeComentarActual())observaciones+=` <button class="btn-small btn-secondary" type="button" onclick="abrirObservaciones(${postulante.id_postulante_atencion},true)">+ Agregar</button>`;
    if(!observaciones)observaciones='<span class="help-text">—</span>';
    const pago=catalogos.permisos.pagar?`<label class="inline-check"><input type="checkbox" class="pago-detalle" value="${postulante.id_postulante_atencion}" ${postulante.pagado?'checked':''}> Pagado</label>`:(postulante.pagado?'<span class="badge badge-success">Pagado</span>':'<span class="help-text">Pendiente</span>');
    const estado=Number(postulante.atendido)?'<span class="badge badge-success">Atendido</span>':'<span class="badge badge-danger">Pendiente</span>';
    return `<tr><td class="attention-order"><strong>${orden}</strong></td><td><strong>${esc(postulante.nombre)}</strong><br><small>${esc(postulante.documento)}</small></td><td>${esc(postulante.escuela||'—')}</td><td>${renderEstudiosFila(postulante,puedeAtender)}</td><td><div class="table-actions">${observaciones}</div></td><td>${pago}</td><td>${estado}</td></tr>`;
}

function renderPieLiquidacion(){
    if(!catalogos.permisos.pagar){pieLiquidacion.innerHTML='';return;}
    pieLiquidacion.innerHTML=`<tr><td colspan="5"><strong>Liquidación total de la jornada</strong><br><small>Al marcarla como realizada, la atención queda en modo de solo lectura.</small></td><td><label class="inline-check"><input type="checkbox" id="pagoCabecera" ${detalleActual.pago_realizado?'checked':''}> Pago total realizado</label></td><td><button class="btn-small" onclick="guardarPago()">Guardar liquidación</button></td></tr>`;
}

function editarCabecera(){
    if(!puedeCrearAsignacion)return;
    const a=detalleActual;formAsignacion.reset();id_asignar_atencion.value=a.id_asignar_atencion;fechaAsignacion.value=a.fecha;tituloForm.textContent=`Editar asignación #${a.id_asignar_atencion}`;mensajeAsignacion.textContent='';
    renderSelectores({usuarios:a.usuarios.map(x=>x.id_usuario),escuelas:a.escuelas.map(x=>x.id_escuela),estudios:a.estudios.map(x=>x.id_estudio)});modalAsignacion.showModal();
}

async function accionCabecera(accion,extra={}){
    try{
        const respuesta=await Salus.json(`backend/atencionesDAO.php?q=${accion}`,{method:'POST',body:Salus.form({id_asignar_atencion:detalleActual.id_asignar_atencion,...extra})});
        Salus.toast(respuesta.mensaje);await cargarAsignaciones();await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

async function cambiarEstadoAsignacion(id,activo){
    if(!puedeCambiarEstadoAsignaciones)return;
    if(!activo&&!confirm('¿Desactivar esta asignación? Dejará de mostrarse y la atención quedará pausada.'))return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=estado',{method:'POST',body:Salus.form({id_asignar_atencion:id,activo})});
        Salus.toast(respuesta.mensaje);
        if(!activo&&detalleActual&&Number(detalleActual.id_asignar_atencion)===Number(id))cerrarDetalle();
        await Promise.all([cargarAsignaciones(),cargarAsignacionesDesactivadas()]);
    }catch(e){Salus.toast(e.message,'error');}
}

async function enviarAtencion(id,estudios){
    const body=Salus.form({id_postulante_atencion:id});
    estudios.forEach(estudio=>body.append('estudios[]',estudio));
    const respuesta=await Salus.json('backend/atencionesDAO.php?q=atender',{method:'POST',body});
    Salus.toast(respuesta.mensaje);await abrirDetalle(detalleActual.id_asignar_atencion,false);
}

async function guardarAtencionDirecta(id){
    const estudios=[...document.querySelectorAll(`.estudio-${id}:checked`)].map(input=>input.value);
    try{await enviarAtencion(id,estudios);}catch(e){Salus.toast(e.message,'error');}
}

function abrirEstudios(id){
    const postulante=detalleActual.postulantes.find(p=>Number(p.id_postulante_atencion)===Number(id));if(!postulante)return;
    postulanteEstudiosActual=postulante;estudiosTitulo.textContent=postulante.nombre;
    const editable=detalleActual.editable_profesional&&!detalleActual.pago_realizado&&catalogos.permisos.atender;
    estudiosContenido.innerHTML=postulante.estudios.map(e=>`<label><input type="checkbox" value="${e.id_estudio}" ${e.realizado?'checked':''} ${editable?'':'disabled'}><span>${esc(e.estudio_referencial)}</span></label>`).join('');
    btnGuardarEstudios.hidden=!editable;modalEstudios.showModal();
}

async function guardarEstudiosMultiples(){
    if(!postulanteEstudiosActual)return;
    const estudios=[...estudiosContenido.querySelectorAll('input:checked')].map(input=>input.value);
    try{await enviarAtencion(postulanteEstudiosActual.id_postulante_atencion,estudios);modalEstudios.close();}catch(e){Salus.toast(e.message,'error');}
}

function inicialesUsuario(nombre){
    return String(nombre||'?').trim().split(/\s+/).slice(0,2).map(parte=>parte.charAt(0)).join('').toUpperCase();
}

function abrirObservaciones(id,enfocar=false){
    const postulante=detalleActual.postulantes.find(p=>Number(p.id_postulante_atencion)===Number(id));if(!postulante)return;
    postulanteObservacionActual=postulante;observacionesTitulo.textContent=postulante.nombre;
    const comentarios=comentariosDe(postulante);
    observacionesContenido.innerHTML=comentarios.map(c=>`<article class="observation-message"><div class="observation-avatar">${esc(inicialesUsuario(c.usuario))}</div><div class="observation-content"><header><strong>${esc(c.usuario)}</strong><time>${esc(new Date(c.fecha).toLocaleString())}</time></header><p>${esc(c.texto)}</p></div></article>`).join('');
    const puede=puedeComentarActual();formObservacionModal.hidden=!puede;btnAgregarObservacion.hidden=!puede;textoObservacion.value='';modalObservaciones.showModal();if(enfocar&&puede)textoObservacion.focus();
}

async function agregarObservacionModal(){
    if(!postulanteObservacionActual)return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=comentar',{method:'POST',body:Salus.form({id_postulante_atencion:postulanteObservacionActual.id_postulante_atencion,texto:textoObservacion.value})});
        Salus.toast(respuesta.mensaje);modalObservaciones.close();await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

async function guardarPago(){
    const body=Salus.form({id_asignar_atencion:detalleActual.id_asignar_atencion,pago_realizado:pagoCabecera.checked?1:0});
    detalleActual.postulantes.forEach(postulante=>{
        const input=document.querySelector(`.pago-detalle[value="${postulante.id_postulante_atencion}"]`);
        if(input?input.checked:Number(postulante.pagado)===1)body.append('postulantes[]',postulante.id_postulante_atencion);
    });
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=pagar',{method:'POST',body});Salus.toast(respuesta.mensaje);await cargarAsignaciones();await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

iniciarAtenciones();
</script>
<?php include 'footer.php'; ?>
