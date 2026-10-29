<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'roles.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'roles.editar');
include 'menu.php';
?>
<link rel="stylesheet" href="css/seguridad.css">
<main class="seguridad-grid">
    <section class="panel-seguridad">
        <h2>Rol</h2>
        <form id="formRol">
            <input type="hidden" name="id_rol" id="id_rol">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo"><label>Código</label><input name="codigo" id="codigo" required></div>
            <div class="campo"><label>Nombre</label><input name="nombre" id="nombre" required></div>
            <div class="campo"><label>Descripción</label><textarea name="descripcion" id="descripcion"></textarea></div>
            <div class="campo"><label>Permisos</label><div id="listaPermisos" class="checks"></div></div>
            <?php if ($puedeEditar): ?><button class="boton" type="submit">Guardar</button><?php endif; ?>
            <button class="boton secundario" type="button" onclick="limpiarRol()">Cancelar</button>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
    <section class="panel-seguridad">
        <h2>Roles</h2>
        <table class="tabla-seguridad">
            <thead><tr><th>Código</th><th>Nombre</th><th>Permisos</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody id="tablaRoles"></tbody>
        </table>
    </section>
</main>
<script>
const puedeEditarRoles = <?= $puedeEditar ? 'true' : 'false' ?>;
const csrfRoles = <?= json_encode(tokenCsrf()) ?>;
let roles = [], permisos = [];
async function cargarRoles(){
    [roles, permisos] = await Promise.all([
        fetch('backend/rolesDAO.php?q=listar').then(r=>r.json()),
        fetch('backend/permisosDAO.php?q=listar').then(r=>r.json())
    ]);
    listaPermisos.innerHTML = permisos.filter(p=>Number(p.activo)===1).map(p=>`<label><input type="checkbox" name="permisos[]" value="${p.id_permiso}"> ${esc(p.codigo)} — ${esc(p.nombre)}</label>`).join('');
    tablaRoles.innerHTML = roles.map(r=>`<tr><td>${esc(r.codigo)}</td><td>${esc(r.nombre)}</td><td>${esc(r.permisos)}</td><td class="${Number(r.activo)?'estado-activo':'estado-inactivo'}">${Number(r.activo)?'ACTIVO':'INACTIVO'}</td><td>${puedeEditarRoles?`<button onclick="editarRol(${r.id_rol})">Editar</button> <button onclick="estadoRol(${r.id_rol},${Number(r.activo)?0:1})">${Number(r.activo)?'Desactivar':'Activar'}</button>`:''}</td></tr>`).join('');
}
function editarRol(id){const r=roles.find(x=>Number(x.id_rol)===Number(id));id_rol.value=r.id_rol;codigo.value=r.codigo;nombre.value=r.nombre;descripcion.value=r.descripcion||'';document.querySelectorAll('#listaPermisos input').forEach(c=>c.checked=r.permiso_ids.map(Number).includes(Number(c.value)));}
function limpiarRol(){formRol.reset();id_rol.value='';codigo.disabled=false;}
formRol.addEventListener('submit',async e=>{e.preventDefault();const r=await fetch('backend/rolesDAO.php?q=guardar',{method:'POST',body:new FormData(formRol)});const d=await r.json();mensaje.textContent=d.mensaje||(d.status==='ok'?'Rol guardado.':'Error');if(d.status==='ok'){limpiarRol();cargarRoles();}});
async function estadoRol(id,activo){const f=new FormData();f.append('id_rol',id);f.append('activo',activo);f.append('csrf_token',csrfRoles);const r=await fetch('backend/rolesDAO.php?q=cambiar_estado',{method:'POST',body:f});const d=await r.json();if(d.status!=='ok')alert(d.mensaje);else cargarRoles();}
function esc(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;}
cargarRoles();
</script>
<?php include 'footer.php'; ?>
