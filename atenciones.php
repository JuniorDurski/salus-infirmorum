<?php
include 'conexion.php';
$usuarioAutenticado = requerirAlgunPermiso($conexion, [
    'postulantes_atencion.ver', 'postulantes_atencion.asignar',
    'postulantes_atencion.iniciar', 'postulantes_atencion.sincronizar',
    'postulantes_atencion.atender', 'postulantes_atencion.comentar',
    'postulantes_atencion.pagar', 'postulantes_atencion.exportar',
]);
$puedeCrearAsignacion = tienePermiso($usuarioAutenticado, 'postulantes_atencion.asignar');
$puedeSincronizar = tienePermiso($usuarioAutenticado, 'postulantes_atencion.sincronizar');
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
<section class="card" id="panelAsignacion" hidden>
    <div class="card-header">
        <div><h2 id="tituloForm">Nueva asignación</h2><p class="help-text">Seleccione uno o varios profesionales, escuelas y estudios.</p></div>
    </div>
    <form id="formAsignacion">
        <input type="hidden" name="id_asignar_atencion" id="id_asignar_atencion">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="campo"><label for="fechaAsignacion">Fecha de la jornada *</label><input type="date" name="fecha" id="fechaAsignacion" required></div>
        <div class="attention-selector-grid">
            <div class="campo"><label>Profesionales que atenderán *</label><input type="search" data-filtra="listaUsuarios" placeholder="Buscar profesional…"><div class="checks selection-list" id="listaUsuarios"></div></div>
            <div class="campo"><label>Escuelas *</label><input type="search" data-filtra="listaEscuelas" placeholder="Buscar escuela…"><div class="checks selection-list" id="listaEscuelas"></div></div>
            <div class="campo"><label>Estudios referenciales *</label><input type="search" data-filtra="listaEstudios" placeholder="Buscar estudio…"><div class="checks selection-list" id="listaEstudios"></div></div>
        </div>
        <div class="form-actions"><button type="submit">Guardar y sincronizar</button><button class="btn-secondary" type="button" onclick="cancelarAsignacion()">Cancelar</button></div>
        <div id="mensajeAsignacion" class="mensaje"></div>
    </form>
</section>
<?php endif; ?>

<section class="card">
    <div class="card-header">
        <div><h2>Jornadas</h2><p class="help-text" id="resumenAsignaciones">Cargando…</p></div>
        <div class="header-actions"><input type="date" id="filtroFecha" aria-label="Filtrar por fecha"><button class="btn-secondary" type="button" onclick="filtroFecha.value='';cargarAsignaciones()">Ver todas</button></div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Fecha</th><th>Escuelas</th><th>Profesionales</th><th>Postulantes</th><th>Liquidación</th><th></th></tr></thead>
        <tbody id="tablaAsignaciones"></tbody>
    </table></div>
</section>

<section class="card attention-workspace" id="panelJornada" hidden>
    <div class="card-header">
        <div><span class="eyebrow">Jornada seleccionada</span><h2 id="detalleTitulo">Atención</h2><p class="help-text" id="detalleResumen"></p></div>
        <button class="icon-button" type="button" onclick="cerrarDetalle()" aria-label="Cerrar">×</button>
    </div>
    <div class="attention-toolbar" id="accionesJornada"></div>
    <div class="table-wrap"><table class="data-table attention-table">
        <thead><tr><th>Postulante</th><th>Escuela</th><th>Estudios</th><th>Conversación</th><th>Liquidación</th><th>Acción</th></tr></thead>
        <tbody id="tablaPostulantes"></tbody>
        <tfoot id="pieLiquidacion"></tfoot>
    </table></div>
</section>

<dialog class="modal" id="modalEstudios">
    <div class="modal-header"><div><span class="eyebrow">Atención</span><h2 id="estudiosTitulo">Estudios del postulante</h2></div><button class="icon-button" type="button" onclick="modalEstudios.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body"><div class="checks" id="estudiosContenido"></div></div>
    <div class="modal-footer"><button class="btn-secondary" type="button" onclick="modalEstudios.close()">Cancelar</button><button id="btnGuardarEstudios" type="button" onclick="guardarEstudiosMultiples()">Guardar atención</button></div>
</dialog>

<dialog class="modal" id="modalConversacion">
    <div class="modal-header"><div><span class="eyebrow">Observaciones</span><h2 id="conversacionTitulo">Conversación</h2></div><button class="icon-button" type="button" onclick="modalConversacion.close()" aria-label="Cerrar">×</button></div>
    <div class="modal-body"><div class="comments" id="conversacionContenido"></div><div id="formComentarioModal" class="comment-form" hidden><select id="categoriaComentario"><option value="general">General</option><option value="clinica">Clínica</option><option value="doctor">Doctor</option></select><textarea id="textoComentario" maxlength="1500" placeholder="Escriba una observación…"></textarea></div></div>
    <div class="modal-footer"><button class="btn-secondary" type="button" onclick="modalConversacion.close()">Cerrar</button><button id="btnAgregarComentario" type="button" onclick="agregarComentarioModal()" hidden>Agregar comentario</button></div>
</dialog>

<script>
const puedeCrearAsignacion = <?= $puedeCrearAsignacion ? 'true' : 'false' ?>;
const puedeSincronizarAsignaciones = <?= $puedeSincronizar ? 'true' : 'false' ?>;
let catalogos={escuelas:[],usuarios:[],estudios:[],permisos:{},hoy:''};
let asignaciones=[], detalleActual=null, postulanteEstudiosActual=null, postulanteConversacionActual=null;
const esc=v=>Salus.escape(v??'');

async function iniciarAtenciones(){
    try{
        catalogos=await Salus.json('backend/atencionesDAO.php?q=catalogos');
        filtroFecha.value=catalogos.hoy;
        if(puedeCrearAsignacion){fechaAsignacion.value=catalogos.hoy;renderSelectores();}
        await cargarAsignaciones();
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
    tablaAsignaciones.innerHTML=asignaciones.length?asignaciones.map(a=>`<tr class="${detalleActual&&Number(detalleActual.id_asignar_atencion)===Number(a.id_asignar_atencion)?'selected-row':''}"><td><strong>${esc(a.fecha)}</strong><br><small>#${a.id_asignar_atencion}</small></td><td>${esc(a.escuelas||'—')}</td><td>${esc(a.profesionales||'—')}</td><td><strong>${a.total}</strong></td><td>${a.pago_realizado?'<span class="badge badge-success">Pago total realizado</span>':`<span class="badge badge-muted">${a.pagados}/${a.total} pagados</span>`}</td><td><button class="btn-small btn-secondary" onclick="abrirDetalle(${a.id_asignar_atencion})">Abrir jornada</button></td></tr>`).join(''):'<tr><td colspan="6" class="empty-state">No hay asignaciones para este filtro.</td></tr>';
}

function nuevaAsignacion(){
    if(!puedeCrearAsignacion)return;
    panelAsignacion.hidden=false;formAsignacion.reset();id_asignar_atencion.value='';fechaAsignacion.value=catalogos.hoy;tituloForm.textContent='Nueva asignación';renderSelectores();panelAsignacion.scrollIntoView({behavior:'smooth'});
}
function cancelarAsignacion(){if(!puedeCrearAsignacion)return;panelAsignacion.hidden=true;mensajeAsignacion.textContent='';}

if(puedeCrearAsignacion)formAsignacion.addEventListener('submit',async evento=>{
    evento.preventDefault();mensajeAsignacion.textContent='';
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=guardar',{method:'POST',body:new FormData(formAsignacion)});
        Salus.toast(respuesta.mensaje);const fechaGuardada=fechaAsignacion.value;cancelarAsignacion();filtroFecha.value=fechaGuardada||catalogos.hoy;await cargarAsignaciones();await abrirDetalle(respuesta.id_asignar_atencion);
    }catch(e){mensajeAsignacion.textContent=e.message;}
});

filtroFecha.addEventListener('change',cargarAsignaciones);

async function sincronizarTodoHoy(){
    if(!puedeSincronizarAsignaciones)return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=sincronizar_dia',{method:'POST',body:Salus.form({fecha:catalogos.hoy})});
        Salus.toast(respuesta.mensaje);await cargarAsignaciones();if(detalleActual)await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

async function abrirDetalle(id,desplazar=true){
    try{
        const respuesta=await Salus.json(`backend/atencionesDAO.php?q=detalle&id=${id}`);
        detalleActual=respuesta.asignacion;renderAsignaciones();renderDetalle();panelJornada.hidden=false;
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
    ['xls','csv','txt','json'].forEach(formato=>acciones+=`<a class="btn btn-secondary btn-small" href="backend/atencionesExportar.php?id=${a.id_asignar_atencion}&formato=${formato}">${formato==='xls'?'Excel':formato.toUpperCase()}</a>`);
    acciones+=a.pago_realizado?'<span class="badge badge-success">Jornada pagada · solo lectura</span>':a.empieza_atender?'<span class="badge badge-success">Atención habilitada</span>':'<span class="badge badge-muted">En espera</span>';
    accionesJornada.innerHTML=acciones;
    tablaPostulantes.innerHTML=a.postulantes.length?a.postulantes.map(renderFilaPostulante).join(''):'<tr><td colspan="6" class="empty-state">No hay postulantes creados hoy para las escuelas seleccionadas.</td></tr>';
    renderPieLiquidacion();
}

function comentariosDe(postulante){
    const comentarios=[];
    Object.entries(postulante.observacion||{}).forEach(([categoria,items])=>(items||[]).forEach(item=>comentarios.push({...item,categoria})));
    return comentarios.sort((a,b)=>String(a.fecha).localeCompare(String(b.fecha)));
}

function puedeComentarActual(){
    return !detalleActual.pago_realizado&&catalogos.permisos.comentar&&(detalleActual.editable_profesional||catalogos.permisos.asignar||catalogos.permisos.iniciar||catalogos.permisos.sincronizar||catalogos.permisos.pagar);
}

function renderEstudiosFila(postulante,puedeAtender){
    const estudios=postulante.estudios;
    if(!estudios.length)return '<span class="help-text">Sin estudios</span>';
    if(estudios.length===1){
        const estudio=estudios[0];
        return `<label class="inline-study"><input type="checkbox" class="estudio-${postulante.id_postulante_atencion}" value="${estudio.id_estudio}" ${estudio.realizado?'checked':''} ${puedeAtender?'':'disabled'}><span>${esc(estudio.estudio_referencial)}</span></label>`;
    }
    const realizados=estudios.filter(e=>Number(e.realizado)).length;
    return `<button class="btn-small btn-secondary" type="button" onclick="abrirEstudios(${postulante.id_postulante_atencion})">Seleccionar estudios (${realizados}/${estudios.length})</button>`;
}

function renderFilaPostulante(postulante){
    const puedeAtender=detalleActual.editable_profesional&&!detalleActual.pago_realizado&&catalogos.permisos.atender;
    const comentarios=comentariosDe(postulante);
    let conversacion='';
    if(comentarios.length)conversacion+=`<button class="btn-small btn-secondary" type="button" onclick="abrirConversacion(${postulante.id_postulante_atencion})">Ver (${comentarios.length})</button>`;
    if(puedeComentarActual())conversacion+=` <button class="btn-small btn-secondary" type="button" onclick="abrirConversacion(${postulante.id_postulante_atencion},true)">+ Comentario</button>`;
    if(!conversacion)conversacion='<span class="help-text">—</span>';
    const pago=catalogos.permisos.pagar?`<label class="inline-check"><input type="checkbox" class="pago-detalle" value="${postulante.id_postulante_atencion}" ${postulante.pagado?'checked':''}> Pagado</label>`:(postulante.pagado?'<span class="badge badge-success">Pagado</span>':'<span class="help-text">Pendiente</span>');
    const accion=puedeAtender?(postulante.estudios.length>1?'<span class="help-text">Guardar desde Estudios</span>':`<button class="btn-small" onclick="guardarAtencionDirecta(${postulante.id_postulante_atencion})">Guardar atención</button>`):'<span class="help-text">Solo lectura</span>';
    return `<tr><td><strong>${esc(postulante.nombre)}</strong><br><small>${esc(postulante.documento)}</small></td><td>${esc(postulante.escuela||'—')}</td><td>${renderEstudiosFila(postulante,puedeAtender)}</td><td><div class="table-actions">${conversacion}</div></td><td>${pago}</td><td>${accion}</td></tr>`;
}

function renderPieLiquidacion(){
    if(!catalogos.permisos.pagar){pieLiquidacion.innerHTML='';return;}
    pieLiquidacion.innerHTML=`<tr><td colspan="4"><strong>Liquidación total de la jornada</strong><br><small>Al marcarla como realizada, la atención queda en modo de solo lectura.</small></td><td><label class="inline-check"><input type="checkbox" id="pagoCabecera" ${detalleActual.pago_realizado?'checked':''}> Pago total realizado</label></td><td><button class="btn-small" onclick="guardarPago()">Guardar liquidación</button></td></tr>`;
}

function editarCabecera(){
    if(!puedeCrearAsignacion)return;
    const a=detalleActual;panelAsignacion.hidden=false;id_asignar_atencion.value=a.id_asignar_atencion;fechaAsignacion.value=a.fecha;tituloForm.textContent=`Editar asignación #${a.id_asignar_atencion}`;
    renderSelectores({usuarios:a.usuarios.map(x=>x.id_usuario),escuelas:a.escuelas.map(x=>x.id_escuela),estudios:a.estudios.map(x=>x.id_estudio)});panelAsignacion.scrollIntoView({behavior:'smooth'});
}

async function accionCabecera(accion,extra={}){
    try{
        const respuesta=await Salus.json(`backend/atencionesDAO.php?q=${accion}`,{method:'POST',body:Salus.form({id_asignar_atencion:detalleActual.id_asignar_atencion,...extra})});
        Salus.toast(respuesta.mensaje);await cargarAsignaciones();await abrirDetalle(detalleActual.id_asignar_atencion,false);
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

function abrirConversacion(id,enfocar=false){
    const postulante=detalleActual.postulantes.find(p=>Number(p.id_postulante_atencion)===Number(id));if(!postulante)return;
    postulanteConversacionActual=postulante;conversacionTitulo.textContent=postulante.nombre;
    const comentarios=comentariosDe(postulante);
    conversacionContenido.innerHTML=comentarios.map(c=>`<div class="comment"><span class="badge badge-muted">${esc(c.categoria)}</span><strong>${esc(c.usuario)}</strong><small>${esc(new Date(c.fecha).toLocaleString())}</small><p>${esc(c.texto)}</p></div>`).join('');
    const puede=puedeComentarActual();formComentarioModal.hidden=!puede;btnAgregarComentario.hidden=!puede;textoComentario.value='';modalConversacion.showModal();if(enfocar&&puede)textoComentario.focus();
}

async function agregarComentarioModal(){
    if(!postulanteConversacionActual)return;
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=comentar',{method:'POST',body:Salus.form({id_postulante_atencion:postulanteConversacionActual.id_postulante_atencion,categoria:categoriaComentario.value,texto:textoComentario.value})});
        Salus.toast(respuesta.mensaje);modalConversacion.close();await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

async function guardarPago(){
    const body=Salus.form({id_asignar_atencion:detalleActual.id_asignar_atencion,pago_realizado:pagoCabecera.checked?1:0});
    document.querySelectorAll('.pago-detalle:checked').forEach(input=>body.append('postulantes[]',input.value));
    try{
        const respuesta=await Salus.json('backend/atencionesDAO.php?q=pagar',{method:'POST',body});Salus.toast(respuesta.mensaje);await cargarAsignaciones();await abrirDetalle(detalleActual.id_asignar_atencion,false);
    }catch(e){Salus.toast(e.message,'error');}
}

iniciarAtenciones();
</script>
<?php include 'footer.php'; ?>
