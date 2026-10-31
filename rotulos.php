<?php
include 'conexion.php';
$usuarioAutenticado = requerirAutenticacion($conexion, 'reportes.rotulos.imprimir');
$tituloPagina = 'Imprimir rótulos';
include 'menu.php';
?>

<style>
.rotulos-filtros { grid-template-columns: 1.4fr 1.2fr 1fr; }
.rotulos-acciones { align-items: center; display: flex; flex-wrap: wrap; gap: 9px; }
.rotulos-seleccion { color: var(--muted); font-size: .9rem; font-weight: 700; margin-left: auto; }
.rotulos-check { text-align: center !important; width: 58px; }
.rotulos-check input { cursor: pointer; }
@media (max-width: 820px) {
    .rotulos-filtros { grid-template-columns: 1fr; }
    .rotulos-seleccion { margin-left: 0; width: 100%; }
}
</style>

<div class="page-header">
    <div>
        <span class="eyebrow">Postulantes · Documentos</span>
        <h1>Imprimir rótulos</h1>
        <p>Filtre los postulantes y genere un rótulo individual, varios seleccionados o todos los resultados.</p>
    </div>
</div>

<section class="card">
    <div class="field-grid rotulos-filtros">
        <div class="campo">
            <label for="rotuloEscuela">Escuela</label>
            <select id="rotuloEscuela"><option value="">Cargando…</option></select>
        </div>
        <div class="campo">
            <label for="rotuloFecha">Fecha de alta</label>
            <input type="date" id="rotuloFecha">
        </div>
        <div class="campo">
            <label for="rotuloTexto">Nombre o cédula</label>
            <input type="search" id="rotuloTexto" placeholder="Búsqueda opcional">
        </div>
    </div>
    <div class="form-actions">
        <button type="button" id="rotuloBuscar">Buscar postulantes</button>
        <button type="button" class="btn-secondary" id="rotuloLimpiar">Limpiar filtros</button>
    </div>
</section>

<section class="card" style="margin-top:20px">
    <div class="card-header" style="align-items:flex-end; flex-wrap:wrap">
        <div>
            <h2>Postulantes</h2>
            <p class="help-text" id="rotuloResumen">Use los filtros para buscar postulantes.</p>
        </div>
        <div class="rotulos-acciones">
            <button type="button" class="btn-secondary btn-small" id="rotuloImprimirSeleccion" disabled>Imprimir seleccionados</button>
            <button type="button" class="btn-secondary btn-small" id="rotuloImprimirTodos" disabled>Imprimir todos</button>
            <span class="rotulos-seleccion" id="rotuloCantidadSeleccionada">0 seleccionados</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="rotulos-check"><input type="checkbox" id="rotuloSeleccionarTodos" aria-label="Seleccionar todos los postulantes" disabled></th>
                    <th>Postulante</th>
                    <th>Cédula</th>
                    <th>Escuela</th>
                    <th>Fecha de alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="rotuloTabla"><tr><td colspan="6" class="empty-state">No se realizó ninguna búsqueda.</td></tr></tbody>
        </table>
    </div>
</section>

<script>
let postulantesRotulos = [];
const idsRotulosSeleccionados = new Set();

async function iniciarRotulos() {
    try {
        const escuelas = await Salus.json('backend/rotulosDAO.php?q=escuelas');
        rotuloEscuela.innerHTML = '<option value="">Todas las escuelas</option>' + escuelas.map(escuela =>
            `<option value="${Number(escuela.id_escuela)}">${Salus.escape(escuela.nombre)} · ${Salus.escape(escuela.periodo)}</option>`
        ).join('');
    } catch (error) {
        rotuloEscuela.innerHTML = '<option value="">No fue posible cargar las escuelas</option>';
        Salus.toast(error.message, 'error');
    }
}

async function buscarRotulos() {
    const params = new URLSearchParams({
        q: 'buscar',
        escuela: rotuloEscuela.value,
        fecha: rotuloFecha.value,
        texto: rotuloTexto.value.trim()
    });
    rotuloBuscar.disabled = true;
    try {
        postulantesRotulos = await Salus.json('backend/rotulosDAO.php?' + params);
        idsRotulosSeleccionados.clear();
        renderRotulos();
    } catch (error) {
        postulantesRotulos = [];
        idsRotulosSeleccionados.clear();
        renderRotulos();
        Salus.toast(error.message, 'error');
    } finally {
        rotuloBuscar.disabled = false;
    }
}

function renderRotulos() {
    rotuloResumen.textContent = `${postulantesRotulos.length} postulante(s) encontrado(s)`;
    rotuloTabla.innerHTML = postulantesRotulos.length ? postulantesRotulos.map(postulante => {
        const id = Number(postulante.id_postulante);
        return `<tr>
            <td class="rotulos-check"><input type="checkbox" data-rotulo-id="${id}" aria-label="Seleccionar a ${Salus.escape(postulante.nombre)}"></td>
            <td><strong>${Salus.escape(postulante.nombre)}</strong></td>
            <td>${Salus.escape(formatearCedulaRotulo(postulante.cedula))}</td>
            <td>${Salus.escape(postulante.escuela || '—')}</td>
            <td>${Salus.escape(formatearFechaRotulo(postulante.fecha_alta))}</td>
            <td><button type="button" class="btn-secondary btn-small" data-imprimir-rotulo="${id}">Imprimir este</button></td>
        </tr>`;
    }).join('') : '<tr><td colspan="6" class="empty-state">No hay postulantes para esos filtros.</td></tr>';
    actualizarSeleccionRotulos();
}

function formatearCedulaRotulo(valor) {
    const original = String(valor || '').trim();
    return /^\d+$/.test(original) ? original.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : original;
}

function formatearFechaRotulo(valor) {
    const partes = String(valor || '').split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : String(valor || '');
}

function actualizarSeleccionRotulos() {
    const total = postulantesRotulos.length;
    const seleccionados = idsRotulosSeleccionados.size;
    rotuloCantidadSeleccionada.textContent = `${seleccionados} seleccionado(s)`;
    rotuloImprimirSeleccion.disabled = seleccionados === 0;
    rotuloImprimirSeleccion.textContent = seleccionados ? `Imprimir seleccionados (${seleccionados})` : 'Imprimir seleccionados';
    rotuloImprimirTodos.disabled = total === 0;
    rotuloImprimirTodos.textContent = total ? `Imprimir todos (${total})` : 'Imprimir todos';
    rotuloSeleccionarTodos.disabled = total === 0;
    rotuloSeleccionarTodos.checked = total > 0 && seleccionados === total;
    rotuloSeleccionarTodos.indeterminate = seleccionados > 0 && seleccionados < total;
}

function imprimirRotulos(ids) {
    const normalizados = [...new Set(ids.map(Number).filter(id => id > 0))];
    if (!normalizados.length) {
        Salus.toast('Seleccione al menos un postulante.', 'warn');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reportes/rotulos.php';
    form.target = '_blank';
    const campos = {ids: normalizados.join(','), csrf_token: Salus.csrf};
    Object.entries(campos).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

rotuloTabla.addEventListener('change', event => {
    const control = event.target.closest('[data-rotulo-id]');
    if (!control) return;
    const id = Number(control.dataset.rotuloId);
    if (control.checked) idsRotulosSeleccionados.add(id);
    else idsRotulosSeleccionados.delete(id);
    actualizarSeleccionRotulos();
});

rotuloTabla.addEventListener('click', event => {
    const boton = event.target.closest('[data-imprimir-rotulo]');
    if (boton) imprimirRotulos([Number(boton.dataset.imprimirRotulo)]);
});

rotuloSeleccionarTodos.addEventListener('change', () => {
    idsRotulosSeleccionados.clear();
    if (rotuloSeleccionarTodos.checked) postulantesRotulos.forEach(postulante => idsRotulosSeleccionados.add(Number(postulante.id_postulante)));
    rotuloTabla.querySelectorAll('[data-rotulo-id]').forEach(control => { control.checked = rotuloSeleccionarTodos.checked; });
    actualizarSeleccionRotulos();
});

rotuloBuscar.addEventListener('click', buscarRotulos);
rotuloImprimirSeleccion.addEventListener('click', () => imprimirRotulos(
    postulantesRotulos.filter(postulante => idsRotulosSeleccionados.has(Number(postulante.id_postulante))).map(postulante => postulante.id_postulante)
));
rotuloImprimirTodos.addEventListener('click', () => imprimirRotulos(postulantesRotulos.map(postulante => postulante.id_postulante)));
rotuloLimpiar.addEventListener('click', () => {
    rotuloEscuela.value = '';
    rotuloFecha.value = '';
    rotuloTexto.value = '';
    buscarRotulos();
});
rotuloTexto.addEventListener('keydown', event => { if (event.key === 'Enter') buscarRotulos(); });

iniciarRotulos();
</script>
<?php include 'footer.php'; ?>
