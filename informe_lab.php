<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.imprimir');
$tituloPagina = 'Informe LAB';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Postulantes · Documentos</span><h1>Informe LAB</h1><p>Filtre postulantes y genere informes sin modificar sus plantillas PDF.</p></div>
</div>

<section class="card">
    <div class="field-grid filtros-informe">
        <div class="campo"><label for="txtBuscar">Nombre o documento</label><input type="search" id="txtBuscar" placeholder="Escriba para buscar…"></div>
        <div class="campo"><label for="f_designacion">Escuela</label><select id="f_designacion"><option value="">Cargando…</option></select></div>
        <div class="campo"><label for="f_fecha">Fecha de registro</label><input type="date" id="f_fecha" value="<?= htmlspecialchars((string) $conexion->query('SELECT CURDATE() AS hoy')->fetch_assoc()['hoy'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="campo campo-boton"><button type="button" class="btn-secondary" data-ver-todos>Ver todos</button></div>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header"><div><h2>Resultados</h2><p class="help-text" id="resumen">Use los filtros para comenzar.</p></div>
        <div class="acciones-impresion">
            <button type="button" class="btn-secondary btn-print" onclick="imprimirTodo()">Imprimir selección</button>
            <button type="button" class="btn-secondary btn-print" onclick="imprimirTarjeta()">Tarjetas Rh</button>
        </div>
    </div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Paciente</th><th>Documento</th><th>Edad / sexo</th><th>Escuela</th><th>Rh</th><th></th></tr></thead>
        <tbody id="tabla"><tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
    </table></div>
</section>

<script>
let idsActuales=[];
async function cargarEscuelas(){
    try{const data=await Salus.json('backend/designacionesDAO.php?q=listar');f_designacion.innerHTML='<option value="">Todas las escuelas</option>'+data.map(d=>`<option value="${d.id_escuela}">${Salus.escape(d.nombre)}</option>`).join('');}
    catch(error){Salus.toast(error.message,'error');}
}
async function buscar(){
    const params=new URLSearchParams({q:'buscar',texto:txtBuscar.value,designacion:f_designacion.value,fecha:f_fecha.value});
    try{
        const data=await Salus.json('backend/postulantesDAO.php?'+params);idsActuales=data.map(p=>p.id_postulante);resumen.textContent=`${data.length} postulante(s) encontrado(s)`;
        tabla.innerHTML=data.length?data.map(p=>`<tr>
            <td><strong>${Salus.escape(Salus.ordenNombre(p))}</strong><br><small>${Salus.escape(p.codigo_informe || '')}</small></td><td>${Salus.escape(Salus.cedula(p.ci))}</td>
            <td>${Salus.escape(p.edad)} años<br><small>${Salus.escape(p.sexo)}</small></td><td>${Salus.escape(p.escuela||'—')}</td>
            <td><span class="badge badge-muted">${Salus.escape(formatoGrupoSanguineo(p.grupo_sanguineo)||'—')}</span></td>
            <td><button class="btn-small btn-secondary btn-print" onclick="imprimirUno(${Number(p.id_postulante)})">Imprimir individualmente</button></td>
        </tr>`).join(''):'<tr><td colspan="6" class="empty-state">No hay coincidencias para esos filtros.</td></tr>';
    }catch(error){Salus.toast(error.message,'error');}
}
function obtenerDesignacionTexto(){if(!f_designacion.value)return '';return f_designacion.options[f_designacion.selectedIndex].text.split(' · ')[0].trim();}
function enviarInforme(action,ids,exigirEscuela=true){
    if(!ids.length){Salus.toast('No hay postulantes para imprimir.','error');return;}
    const designacion=obtenerDesignacionTexto();if(exigirEscuela&&!designacion){Salus.toast('Seleccione una escuela.','error');return;}
    const form=document.createElement('form');form.method='POST';form.action=action;form.target='_blank';
    ids.forEach((id,index)=>{const input=document.createElement('input');input.type='hidden';input.name=ids.length===1?'id':'ids[]';input.value=id;form.appendChild(input);});
    const escuela=document.createElement('input');escuela.type='hidden';escuela.name='designacion';escuela.value=designacion;form.appendChild(escuela);
    document.body.appendChild(form);form.submit();form.remove();
}
function imprimirTodo(){enviarInforme('reportes/index.php',idsActuales);}
function imprimirTarjeta(){enviarInforme('reportes/informe_tipi.php',idsActuales);}
function imprimirUno(id){enviarInforme('reportes/index.php',[id],false);}
Salus.filtrosAutomaticos([txtBuscar,f_designacion,f_fecha],buscar);
cargarEscuelas().then(buscar);
</script>
<?php include 'footer.php'; ?>
