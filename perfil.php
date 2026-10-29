<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'perfil.editar');
$tituloPagina = 'Mi perfil';
include 'menu.php';
?>
<div class="page-header"><div><span class="eyebrow">Cuenta personal</span><h1>Mi perfil</h1><p>Configure sus credenciales profesionales, apariencia, firma y sello.</p></div></div>
<main class="seguridad-grid">
    <section class="panel-seguridad">
        <h2>Mi configuración</h2>
        <p><strong><?= htmlspecialchars($usuarioAutenticado['nombre']) ?></strong><br>@<?= htmlspecialchars($usuarioAutenticado['usuario']) ?></p>
        <form id="formPerfil">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES) ?>">
            <div class="campo"><label>Título</label><select name="id_titulo" id="id_titulo"></select></div>
            <div class="campo"><label>Universidad</label><select name="id_universidad" id="id_universidad"></select></div>
            <div class="campo"><label>Número de registro</label><input name="numero_registro" id="numero_registro"></div>
            <div class="campo"><label><input type="checkbox" name="modo_oscuro" id="modo_oscuro" value="1"> Modo oscuro</label></div>
            <button class="boton" type="submit">Guardar configuración</button>
            <a class="boton secundario" href="cambiar_clave.php">Cambiar contraseña</a>
            <div id="mensaje" class="mensaje"></div>
        </form>
    </section>
    <section class="panel-seguridad">
        <h2>Firma y sello</h2>
        <p>Seleccione una imagen JPG, PNG o WEBP. La ruta se guardará automáticamente.</p>
        <div class="campo"><label>Firma</label><img id="img_firma" class="previsualizacion" alt="Firma"><input type="file" id="firma" accept="image/jpeg,image/png,image/webp"><button class="boton" onclick="subir('firma')">Subir firma</button></div>
        <div class="campo"><label>Firma y sello</label><img id="img_firma_sello" class="previsualizacion" alt="Firma y sello"><input type="file" id="firma_sello" accept="image/jpeg,image/png,image/webp"><button class="boton" onclick="subir('firma_sello')">Subir firma y sello</button></div>
        <div class="campo"><label>Sello</label><img id="img_sello" class="previsualizacion" alt="Sello"><input type="file" id="sello" accept="image/jpeg,image/png,image/webp"><button class="boton" onclick="subir('sello')">Subir sello</button></div>
        <div id="mensajeUpload" class="mensaje"></div>
    </section>
</main>
<script>
const csrfPerfil=<?= json_encode(tokenCsrf()) ?>;let configuracion={};
async function cargarPerfil(){
    const [c,t,u]=await Promise.all([
        fetch('backend/usuariosDAO.php?q=configuracion').then(r=>r.json()),
        fetch('backend/referencialesDAO.php?q=titulos').then(r=>r.json()),
        fetch('backend/referencialesDAO.php?q=universidades').then(r=>r.json())
    ]);configuracion=c;
    id_titulo.innerHTML='<option value="">-- Sin título --</option>'+t.map(x=>`<option value="${x.id_titulo}">${esc(x.abreviatura)} ${esc(x.titulo)}</option>`).join('');
    id_universidad.innerHTML='<option value="">-- Sin universidad --</option>'+u.map(x=>`<option value="${x.id_universidad}">${esc(x.nombre)} (${esc(x.pais)})</option>`).join('');
    id_titulo.value=c.id_titulo||'';id_universidad.value=c.id_universidad||'';numero_registro.value=c.numero_registro||'';modo_oscuro.checked=Number(c.modo_oscuro)===1;
    mostrarImagen('img_firma',c.url_firma);mostrarImagen('img_firma_sello',c.url_firma_sello);mostrarImagen('img_sello',c.url_sello);
}
formPerfil.addEventListener('submit',async e=>{e.preventDefault();const r=await fetch('backend/usuariosDAO.php?q=guardar_configuracion',{method:'POST',body:new FormData(formPerfil)});const d=await r.json();mensaje.textContent=d.mensaje||(d.status==='ok'?'Configuración guardada.':'Error');mensaje.classList.toggle('ok',d.status==='ok');if(d.status==='ok')document.body.classList.toggle('theme-dark',modo_oscuro.checked);});
async function subir(tipo){const input=document.getElementById(tipo);if(!input.files[0]){mensajeUpload.textContent='Seleccione una imagen.';return;}const f=new FormData();f.append('tipo',tipo);f.append('archivo',input.files[0]);f.append('csrf_token',csrfPerfil);const r=await fetch('backend/uploadDAO.php',{method:'POST',body:f});const d=await r.json();mensajeUpload.textContent=d.mensaje||(d.status==='ok'?'Imagen guardada.':'Error');if(d.status==='ok'){mostrarImagen('img_'+tipo,d.url);input.value='';}}
function mostrarImagen(id,url){const img=document.getElementById(id);img.src=url||'';img.hidden=!url;}
function esc(v){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;}
cargarPerfil();
</script>
<?php include 'footer.php'; ?>
