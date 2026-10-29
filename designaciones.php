<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'escuelas.ver');
$puedeEditar = tienePermiso($usuarioAutenticado, 'escuelas.editar');
$tituloPagina = 'Escuelas';
include 'menu.php';
?>

<div class="page-header">
    <div><span class="eyebrow">Referenciales</span><h1>Escuelas</h1><p>Organice las instituciones por país y periodo académico.</p></div>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-header"><div><h2>Datos de la escuela</h2><p class="help-text">El periodo se almacena como año y mes.</p></div></div>
        <form id="frmDesignacion">
            <input type="hidden" name="id_escuela" id="id_escuela">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo"><label for="nombre">Nombre</label><input name="nombre" id="nombre" data-uppercase maxlength="150" required></div>
            <div class="field-grid">
                <div class="campo"><label for="periodo_anio">Año</label><input type="number" name="periodo_anio" id="periodo_anio" min="1900" max="2200" value="<?= date('Y') ?>" required></div>
                <div class="campo"><label for="periodo_mes">Mes</label><select name="periodo_mes" id="periodo_mes" required>
                    <option value="">Seleccione</option><option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                </select></div>
            </div>
            <div class="campo"><label for="id_pais">País</label><select name="id_pais" id="id_pais"><option value="">Cargando…</option></select></div>
            <?php if ($puedeEditar): ?><div class="form-actions"><button type="submit">Guardar</button><button type="button" class="btn-secondary" onclick="limpiar()">Cancelar</button></div><?php endif; ?>
            <div id="mensaje" class="mensaje" role="status"></div>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><div><h2>Escuelas activas</h2><p class="help-text" id="resumen">Cargando registros…</p></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Escuela</th><th>Periodo</th><th>País</th><th>Acciones</th></tr></thead>
            <tbody id="tabla"><tr><td colspan="4" class="empty-state">Cargando…</td></tr></tbody>
        </table></div>
    </section>
</div>

<script>
const puedeEditarEscuelas = <?= $puedeEditar ? 'true' : 'false' ?>;
let escuelas = [], paises = [];
const periodoPredeterminado = {anio: new Date().getFullYear(), mes: new Date().getMonth() + 1};
async function cargar() {
    try {
        escuelas = await Salus.json('backend/designacionesDAO.php?q=listar');
        resumen.textContent = `${escuelas.length} registro(s) activo(s)`;
        tabla.innerHTML = escuelas.length ? escuelas.map(d => `<tr>
            <td><strong>${Salus.escape(d.nombre)}</strong></td><td><span class="badge badge-muted">${Salus.escape(d.periodo)}</span></td>
            <td>${Salus.escape(paises.find(p => Number(p.id_pais) === Number(d.id_pais))?.nombre || '—')}</td>
            <td><div class="table-actions">${puedeEditarEscuelas ? `<button class="btn-small btn-secondary" onclick="editar(${d.id_escuela})">Editar</button><button class="btn-small btn-danger" onclick="eliminar(${d.id_escuela})">Desactivar</button>` : ''}</div></td>
        </tr>`).join('') : '<tr><td colspan="4" class="empty-state">Todavía no hay escuelas.</td></tr>';
    } catch (error) { Salus.toast(error.message, 'error'); }
}
function editar(id) {
    const d = escuelas.find(item => Number(item.id_escuela) === Number(id)); if (!d) return;
    id_escuela.value=d.id_escuela; nombre.value=d.nombre; periodo_anio.value=d.periodo_anio; periodo_mes.value=d.periodo_mes; id_pais.value=d.id_pais||'';
    nombre.focus();
}
async function eliminar(id) {
    if (!confirm('¿Desactivar esta escuela?')) return;
    try { await Salus.json('backend/designacionesDAO.php?q=eliminar', {method:'POST', body:Salus.form({id})}); Salus.toast('Escuela desactivada.'); cargar(); }
    catch (error) { Salus.toast(error.message, 'error'); }
}
function aplicarPredeterminados() {
    periodo_anio.value=periodoPredeterminado.anio;
    periodo_mes.value=periodoPredeterminado.mes;
    const paraguay=paises.find(p=>p.codigo_iso2==='PY');
    id_pais.value=paraguay?.id_pais||'';
}
function limpiar() { frmDesignacion.reset(); id_escuela.value=''; aplicarPredeterminados(); mensaje.textContent=''; }
frmDesignacion.addEventListener('submit', async event => {
    event.preventDefault();
    try { await Salus.json('backend/designacionesDAO.php?q=guardar', {method:'POST', body:new FormData(frmDesignacion)}); Salus.toast('Escuela guardada.'); limpiar(); cargar(); }
    catch(error) { mensaje.textContent=error.message; }
});
(async function iniciar(){
    try { paises=await Salus.json('backend/referencialesDAO.php?q=paises'); id_pais.innerHTML='<option value="">Sin especificar</option>'+paises.map(p=>`<option value="${p.id_pais}">${Salus.escape(p.nombre)}</option>`).join(''); aplicarPredeterminados(); await cargar(); }
    catch(error){ Salus.toast(error.message,'error'); }
})();
</script>
<?php include 'footer.php'; ?>
