<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/backend/seguridad.php';
$fechaHoy = (string) $conexion->query('SELECT CURDATE()')->fetch_row()[0];
?>
<!doctype html>
<html lang="es" data-csrf="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta de estudios | Salus Infirmorum</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js"></script>
</head>
<body class="public-query-page">
<main class="public-query-shell">
    <section class="card public-query-card">
        <div class="public-query-brand"><img src="images/icono_menu.png" alt=""><div><span class="eyebrow">Consulta pública</span><h1>Mis estudios de postulación</h1></div></div>
        <p class="help-text">Ingrese su documento y seleccione la fecha que desea consultar. La fecha inicial es el día de hoy y la información es de solo lectura.</p>
        <form id="formConsulta" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="field-grid">
                <div class="campo"><label for="documento">Número de documento</label><input id="documento" name="documento" maxlength="30" required autocomplete="off" autocapitalize="characters"></div>
                <div class="campo"><label for="fecha">Fecha de atención</label><input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaHoy, ENT_QUOTES, 'UTF-8') ?>" required></div>
            </div>
            <div class="form-actions public-query-actions"><button id="botonConsultar" type="submit">Consultar estudios</button><a class="btn btn-secondary" href="login.php">Volver al acceso</a></div>
            <div id="mensajeConsulta" class="mensaje" role="status" aria-live="polite"></div>
        </form>
        <div id="resultadoConsulta" hidden></div>
    </section>
</main>
<script>
const formConsulta=document.getElementById('formConsulta');
const mensajeConsulta=document.getElementById('mensajeConsulta');
const resultadoConsulta=document.getElementById('resultadoConsulta');
const botonConsultar=document.getElementById('botonConsultar');

function formatearFecha(fecha){
    const partes=String(fecha||'').split('-');
    return partes.length===3?`${partes[2]}/${partes[1]}/${partes[0]}`:fecha;
}

function renderResultadoConsulta(respuesta){
    const estudios=respuesta.estudios||[];
    const pendientes=estudios.filter(estudio=>!Number(estudio.realizado)).length;
    const realizados=estudios.length-pendientes;
    const resumen=estudios.length
        ?`<div class="query-result-summary"><span class="badge badge-success">${realizados} realizado(s)</span><span class="badge ${pendientes?'badge-danger':'badge-success'}">${pendientes} pendiente(s)</span></div>`
        :'<span class="badge badge-muted">Sin estudios asignados</span>';
    const listado=estudios.length
        ?`<div class="public-study-list">${estudios.map(estudio=>`<article><span class="status-dot ${estudio.realizado?'done':'pending'}" aria-hidden="true"></span><div><strong>${Salus.escape(estudio.estudio_referencial)}</strong><small>Asignado: ${Salus.escape(formatearFecha(estudio.fecha_asignada))}</small></div><span class="badge ${estudio.realizado?'badge-success':'badge-danger'}">${estudio.realizado?'Realizado':'Pendiente'}</span></article>`).join('')}</div>`
        :'<div class="notice">Todavía no existen estudios referenciales asignados a este postulante.</div>';

    resultadoConsulta.innerHTML=`<div class="query-result-header"><div><span class="eyebrow">Atención del ${Salus.escape(formatearFecha(respuesta.fecha))}</span><h2>${Salus.escape(respuesta.paciente.nombre)}</h2><p>${Salus.escape(respuesta.paciente.escuela)}</p></div>${resumen}</div>${listado}`;
    resultadoConsulta.hidden=false;
}

formConsulta.addEventListener('submit',async evento=>{
    evento.preventDefault();
    mensajeConsulta.textContent='';
    resultadoConsulta.hidden=true;
    if(!formConsulta.reportValidity())return;
    botonConsultar.disabled=true;
    botonConsultar.textContent='Consultando…';
    try{
        const respuesta=await Salus.json('backend/consultaEstudiosPublicaDAO.php?q=consultar',{method:'POST',body:new FormData(formConsulta)});
        renderResultadoConsulta(respuesta);
    }catch(error){
        mensajeConsulta.textContent=error.message;
    }finally{
        botonConsultar.disabled=false;
        botonConsultar.textContent='Consultar estudios';
    }
});
</script>
</body>
</html>
