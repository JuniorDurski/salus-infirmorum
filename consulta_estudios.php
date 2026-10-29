<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/backend/seguridad.php';
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
        <p class="help-text">Ingrese exactamente su documento y la escuela con la que fue registrado. La consulta es de solo lectura.</p>
        <form id="formConsulta">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="field-grid">
                <div class="campo"><label for="documento">Número de documento</label><input id="documento" name="documento" maxlength="30" required autocomplete="off"></div>
                <div class="campo"><label for="id_escuela">Escuela</label><select id="id_escuela" name="id_escuela" required><option value="">Cargando escuelas…</option></select></div>
            </div>
            <div class="form-actions"><button type="submit">Consultar estudios</button><a class="btn btn-secondary" href="login.php">Volver al acceso</a></div>
            <div id="mensajeConsulta" class="mensaje" role="status"></div>
        </form>
        <div id="resultadoConsulta" hidden></div>
    </section>
</main>
<script>
async function cargarEscuelasPublicas(){
    try{const r=await Salus.json('backend/consultaEstudiosPublicaDAO.php?q=escuelas');id_escuela.innerHTML='<option value="">Seleccione su escuela</option>'+r.escuelas.map(e=>`<option value="${e.id_escuela}">${Salus.escape(e.nombre)} · ${Salus.escape(e.periodo)}</option>`).join('');}
    catch(e){mensajeConsulta.textContent=e.message;}
}
formConsulta.addEventListener('submit',async ev=>{ev.preventDefault();mensajeConsulta.textContent='';resultadoConsulta.hidden=true;try{const r=await Salus.json('backend/consultaEstudiosPublicaDAO.php?q=consultar',{method:'POST',body:new FormData(formConsulta)});const pendientes=r.estudios.filter(e=>!Number(e.realizado)).length;resultadoConsulta.innerHTML=`<div class="query-result-header"><div><span class="eyebrow">Postulante encontrado</span><h2>${Salus.escape(r.paciente.nombre)}</h2><p>${Salus.escape(r.paciente.escuela)}</p></div><span class="badge ${pendientes?'badge-danger':'badge-success'}">${r.estudios.length?`${pendientes} pendiente(s)`:'Sin estudios asignados'}</span></div>${r.estudios.length?`<div class="public-study-list">${r.estudios.map(e=>`<div><span class="status-dot ${e.realizado?'done':'pending'}"></span><div><strong>${Salus.escape(e.estudio_referencial)}</strong><small>Asignado: ${Salus.escape(e.fecha_asignada)}</small></div><span class="badge ${e.realizado?'badge-success':'badge-danger'}">${e.realizado?'Realizado':'Pendiente'}</span></div>`).join('')}</div>`:'<div class="notice">Todavía no existen estudios referenciales asignados a este postulante.</div>'};resultadoConsulta.hidden=false;}catch(e){mensajeConsulta.textContent=e.message;}});
cargarEscuelasPublicas();
</script>
</body>
</html>
