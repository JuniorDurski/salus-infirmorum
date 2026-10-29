<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'permisos.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'permisos.editar');
include 'menu.php';
?>
<link rel="stylesheet" href="css/seguridad.css">
<main class="seguridad-grid">
    <section class="panel-seguridad">
        <h2>Permiso</h2>
        <form id="formPermiso">
            <input type="hidden" name="id_permiso" id="id_permiso">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo"><label>Código</label><input name="codigo" id="codigo" required><small>No puede cambiarse después de crear el permiso.</small></div>
            <div class="campo"><label>Nombre</label><input name="nombre" id="nombre" required></div>
            <div class="campo"><label>Descripción</label><textarea name="descripcion" id="descripcion"></textarea></div>
            <?php if ($puedeEditar): ?><button class="boton" type="submit">Guardar</button><?php endif; ?>
            <button class="boton secundario" type="button" onclick="limpiarPermiso()">Cancelar</button>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
    <section class="panel-seguridad">
        <h2>Permisos</h2>
        <table class="tabla-seguridad"><thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="tablaPermisos"></tbody></table>
    </section>
</main>
<script>
const puedeEditarPermisos=<?= $puedeEditar?'true':'false' ?>,csrfPermisos=<?= json_encode(tokenCsrf()) ?>;let permisos=[];
async function cargarPermisos(){permisos=await fetch('backend/permisosDAO.php?q=listar').then(r=>r.json());tablaPermisos.innerHTML=permisos.map(p=>`<tr><td>${esc(p.codigo)}</td><td>${esc(p.nombre)}</td><td>${esc(p.descripcion||'')}</td><td class="${Number(p.activo)?'estado-activo':'estado-inactivo'}">${Number(p.activo)?'ACTIVO':'INACTIVO'}</td><td>${puedeEditarPermisos?`<button onclick="editarPermiso(${p.id_permiso})">Editar</button> <button onclick="estadoPermiso(${p.id_permiso},${Number(p.activo)?0:1})">${Number(p.activo)?'Desactivar':'Activar'}</button>`:''}</td></tr>`).join('');}
function editarPermiso(id){const p=permisos.find(x=>Number(x.id_permiso)===Number(id));id_permiso.value=p.id_permiso;codigo.value=p.codigo;codigo.readOnly=true;nombre.value=p.nombre;descripcion.value=p.descripcion||'';}
function limpiarPermiso(){formPermiso.reset();id_permiso.value='';codigo.readOnly=false;}
formPermiso.addEventListener('submit',async e=>{e.preventDefault();const r=await fetch('backend/permisosDAO.php?q=guardar',{method:'POST',body:new FormData(formPermiso)});const d=await r.json();mensaje.textContent=d.mensaje||(d.status==='ok'?'Permiso guardado.':'Error');if(d.status==='ok'){limpiarPermiso();cargarPermisos();}});
async function estadoPermiso(id,activo){const f=new FormData();f.append('id_permiso',id);f.append('activo',activo);f.append('csrf_token',csrfPermisos);const r=await fetch('backend/permisosDAO.php?q=cambiar_estado',{method:'POST',body:f});const d=await r.json();if(d.status!=='ok')alert(d.mensaje);else cargarPermisos();}
function esc(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;}
cargarPermisos();
</script>
<?php include 'footer.php'; ?>
