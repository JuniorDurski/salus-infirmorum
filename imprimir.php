<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.imprimir');
$tituloPagina = 'Informes';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Documentos</span><h1>Imprimir informes</h1><p>Filtre pacientes y genere informes sin modificar sus plantillas PDF.</p></div>
</div>

<section class="card">
    <div class="field-grid" style="grid-template-columns:2fr 1.4fr 1fr">
        <div class="campo"><label for="txtBuscar">Nombre o documento</label><input type="search" id="txtBuscar" placeholder="Escriba para buscar…"></div>
        <div class="campo"><label for="f_designacion">Escuela</label><select id="f_designacion"><option value="">Cargando…</option></select></div>
        <div class="campo"><label for="f_fecha">Fecha de alta</label><input type="date" id="f_fecha"></div>
    </div>
    <div class="form-actions">
        <button type="button" onclick="buscar()">Buscar pacientes</button>
        <button type="button" class="btn-secondary" onclick="imprimirTodo()">Imprimir selección</button>
        <button type="button" class="btn-secondary" onclick="imprimirTarjeta()">Tarjetas Rh</button>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header"><div><h2>Resultados</h2><p class="help-text" id="resumen">Use los filtros para comenzar.</p></div></div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Paciente</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Rh</th><th>Alta</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="7" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
    </table></div>
</section>

<script>
let idsActuales=[];
async function cargarEscuelas(){
    try{const data=await Salus.json('backend/designacionesDAO.php?q=listar');f_designacion.innerHTML='<option value="">Todas las escuelas</option>'+data.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)} · ${Salus.escape(d.periodo)}</option>`).join('');}
    catch(error){Salus.toast(error.message,'error');}
}
async function buscar(){
    const params=new URLSearchParams({q:'buscar',texto:txtBuscar.value,designacion:f_designacion.value,fecha:f_fecha.value});
    try{
        const data=await Salus.json('backend/postulantesDAO.php?'+params);idsActuales=data.map(p=>p.id_postulante);resumen.textContent=`${data.length} paciente(s) encontrado(s)`;
        tabla.innerHTML=data.length?data.map(p=>`<tr>
            <td><strong>${Salus.escape(p.nombre)}</strong><br><small>${Salus.escape(p.codigo_generado||p.codigo||'')}</small></td><td>${Salus.escape(p.ci)}</td>
            <td>${Salus.escape(p.edad)} años<br><small>${Salus.escape(p.sexo)}</small></td><td>${Salus.escape(p.escuela||'—')}</td>
            <td><span class="badge badge-muted">${Salus.escape(formatoGrupoSanguineo(p.grupo_sanguineo)||'—')}</span></td><td>${Salus.escape(p.fecha)}</td>
            <td><button class="btn-small btn-secondary" onclick="imprimirUno(${Number(p.id_postulante)})">Imprimir</button></td>
        </tr>`).join(''):'<tr><td colspan="7" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
    }catch(error){Salus.toast(error.message,'error');}
}
function obtenerDesignacionTexto(){if(!f_designacion.value)return '';return f_designacion.options[f_designacion.selectedIndex].text.split(' · ')[0].trim();}
function enviarInforme(action,ids,exigirEscuela=true){
    if(!ids.length){Salus.toast('No hay pacientes para imprimir.','error');return;}
    const designacion=obtenerDesignacionTexto();if(exigirEscuela&&!designacion){Salus.toast('Seleccione una escuela.','error');return;}
    const form=document.createElement('form');form.method='POST';form.action=action;form.target='_blank';
    ids.forEach((id,index)=>{const input=document.createElement('input');input.type='hidden';input.name=ids.length===1?'id':'ids[]';input.value=id;form.appendChild(input);});
    const escuela=document.createElement('input');escuela.type='hidden';escuela.name='designacion';escuela.value=designacion;form.appendChild(escuela);
    document.body.appendChild(form);form.submit();form.remove();
}
function imprimirTodo(){enviarInforme('reportes/index.php',idsActuales);}
function imprimirTarjeta(){enviarInforme('reportes/informe_tipi.php',idsActuales);}
function imprimirUno(id){enviarInforme('reportes/index.php',[id],false);}
txtBuscar.addEventListener('keydown',event=>{if(event.key==='Enter')buscar();});
cargarEscuelas();
</script>
<?php include 'footer.php'; ?>
